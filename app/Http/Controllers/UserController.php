<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Arr;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Http;
use App\Services\KmsClient;

class UserController extends Controller
{
    private const PICTURE_KMS_KEY = 'picture-kek';

    public function __construct()
    {
        $this->middleware('permission:user');
    }

    private function protectSettingUser(User $targetUser): void
    {
        $authUser = Auth::user();
        if (!$authUser->can('setting') && $targetUser->can('setting')) {
            throw ValidationException::withMessages(['error' => ["Can't edit this user."]]);
        }
    }

    private function checkAdminITProtection(User $targetUser): ?RedirectResponse
    {
        $authUser = Auth::user();
        if ($authUser->hasRole('User') && $targetUser->hasRole('AdminIT')) {
            return redirect()->route('users.index')->with('error', 'The Admin role is not permitted to modify users with the AdminIT role.');
        }
        return null;
    }

    private function getFaceEmbedding($imageFile)
    {
        try {
            $base64Image = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($imageFile->getRealPath()));
            
            $response = Http::post('http://127.0.0.1:5000/represent', [
                'image' => $base64Image
            ]);

            if ($response->successful() && isset($response->json()['embedding'])) {
                return $response->json()['embedding'];
            }
        } catch (\Exception $e) {
            \Log::error("Face Embedding Error: " . $e->getMessage());
        }
        return null;
    }

    public function index(Request $request): View
    {
        $roles = Role::pluck('name', 'name')->all();
        $data = User::query()
            ->when($request->name, function ($query, $name) {
                return $query->where('name', 'like', "%{$name}%");
            })
            ->when($request->role, function ($query, $role) {
                return $query->role($role);
            })
            ->latest()->paginate(5)->appends($request->query());

        return view('users.index', compact('data', 'roles'));
    }

    public function create(): View
    {
        $roles = Role::pluck('name', 'name')->all();
        return view('users.create', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|unique:users,name',
            'email' => ['required', 'email', 'unique:users,email', 'regex:/^[^\s@]+@[^\s@]+\.(com|id|net|org|co\.id|ac\.id)$/i'],
            'password' => ['required', 'same:confirm-password', Password::min(12)->mixedCase()->numbers()->symbols()],
            'roles' => 'required|array',
            'picture' => 'required|image|mimes:jpeg,png,jpg|max:2048'
        ], [
            'email.regex' => 'A valid email domain is required (e.g., .com, .id, .net, .org, .co.id, .ac.id).'
        ]);

        $authUser = Auth::user();
        if (!$authUser->can('setting')) {
            $assignedRoles = Role::whereIn('name', Arr::wrap($request->input('roles')))->get();
            foreach ($assignedRoles as $role) {
                if ($role->hasPermissionTo('setting')) {
                    throw ValidationException::withMessages(['roles' => ["Unauthorized role assignment."]]);
                }
            }
        }

        $input = $request->except(['picture', 'confirm-password']);

        $input['salt'] = bin2hex(random_bytes(16)); 
        $input['password'] = Hash::make($request->input('password'));

        if ($request->hasFile('picture')) {
            $rawImage = file_get_contents($request->file('picture')->getRealPath());
            $mimeType = $request->file('picture')->getMimeType();
            $base64Image = 'data:' . $mimeType . ';base64,' . base64_encode($rawImage);

            // Data Integrity Hashing (Process 5): reject duplicate biometric enrollment
            $pictureHash = hash('sha256', $rawImage);
            if (User::where('picture_hash', $pictureHash)->exists()) {
                throw ValidationException::withMessages([
                    'picture' => ['This face scan is already registered to another user.']
                ]);
            }
            $input['picture_hash'] = $pictureHash;

            $input['face_embedding'] = $this->getFaceEmbedding($request->file('picture'));

            // Envelope Encryption (KEK/DEK via KMS)
            $input['picture'] = $this->encryptWithDEK($base64Image);
        }

        $input['role'] = $request->input('roles')[0];

        try {
            $user = User::create($input);
            $user->assignRole($request->input('roles'));
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '08S01' || str_contains($e->getMessage(), 'max_allowed_packet')) {
                throw ValidationException::withMessages([
                    'picture' => ['The uploaded picture is too large for the database system. Please upload a smaller image file.']
                ]);
            }
            throw $e;
        }

        return redirect()->route('users.index')->with('success', 'User created successfully with secured AI face data.');
    }

    public function showPicture($id)
    {
        $user = User::findOrFail($id);
        
        if (!$user->picture) {
            abort(404, 'Image not found.');
        }

        $payload = json_decode($user->picture, true);

        // Updated validation to check for the new KMS/GCM payload keys
        if (!$payload || !isset($payload['data'], $payload['iv'], $payload['tag'], $payload['edek'], $payload['dek_iv'], $payload['dek_tag'], $payload['kek_version'])) {
            abort(400, 'Invalid encrypted image payload.');
        }

        // Call the new KMS decryption method instead of the old local one
        $decryptedBase64 = $this->decryptWithDEK($payload);

        $cleanBase64 = preg_replace('#^data:image/[^;]+;base64,#', '', $decryptedBase64);
        $cleanBase64 = str_replace(' ', '+', $cleanBase64);
        
        $imageBinary = base64_decode($cleanBase64);

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($imageBinary) ?: 'image/jpeg';

        if (ob_get_length()) {
            ob_end_clean();
        }

        return response($imageBinary)->header('Content-Type', $mimeType);
    }

    public function show($id): View
    {
        $user = User::findOrFail($id);
        return view('users.show', compact('user'));
    }

    public function edit($id): View|RedirectResponse
    {
        $user = User::findOrFail($id);
        $this->protectSettingUser($user);
        if ($response = $this->checkAdminITProtection($user)) return $response;
        
        $roles = Role::pluck('name', 'name')->all();
        $userRole = $user->roles->pluck('name', 'name')->all();
        return view('users.edit', compact('user', 'roles', 'userRole'));
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $user = User::findOrFail($id);
        $this->protectSettingUser($user);
        if ($response = $this->checkAdminITProtection($user)) return $response;

        $request->validate([
            'name' => 'required|unique:users,name,' . $id,
            'email' => ['required', 'email', 'unique:users,email,' . $id, 'regex:/^[^\s@]+@[^\s@]+\.(com|id|net|org|co\.id|ac\.id)$/i'],
            'password' => ['nullable', 'same:confirm-password', Password::min(12)->mixedCase()->numbers()->symbols()],
            'roles' => 'required|array',
            'picture' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ], [
            'email.regex' => 'A valid email domain is required (e.g., .com, .id, .net, .org, .co.id, .ac.id).'
        ]);

        $input = $request->except(['picture', 'confirm-password']);
        
        if (!empty($request->input('password'))) {
            $input['salt'] = bin2hex(random_bytes(16)); 
            $input['password'] = Hash::make($request->input('password'));
        } else {
            $input = Arr::except($input, ['password']);
        }

        if ($request->hasFile('picture')) {
            $rawImage = file_get_contents($request->file('picture')->getRealPath());
            $mimeType = $request->file('picture')->getMimeType();
            $base64Image = 'data:' . $mimeType . ';base64,' . base64_encode($rawImage);

            // Data Integrity Hashing (Process 5)
            $pictureHash = hash('sha256', $rawImage);
            if (User::where('picture_hash', $pictureHash)->where('id', '!=', $id)->exists()) {
                throw ValidationException::withMessages([
                    'picture' => ['This face scan is already registered to another user.']
                ]);
            }
            $input['picture_hash'] = $pictureHash;

            $input['face_embedding'] = $this->getFaceEmbedding($request->file('picture'));

            // Envelope Encryption (KEK/DEK via KMS)
            $input['picture'] = $this->encryptWithDEK($base64Image);
        }

        $input['role'] = $request->input('roles')[0];

        try {
            $user->update($input);
            $user->syncRoles($request->input('roles'));
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '08S01' || str_contains($e->getMessage(), 'max_allowed_packet')) {
                throw ValidationException::withMessages([
                    'picture' => ['The uploaded picture is too large for the database system. Please upload a smaller image file.']
                ]);
            }
            throw $e;
        }
        
        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function destroy($id): RedirectResponse
    {
        $user = User::findOrFail($id);
        $this->protectSettingUser($user);
        if ($response = $this->checkAdminITProtection($user)) return $response;
        
        $user->delete();
        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }

    private function encryptWithDEK($data)
    {
        $kms = new KmsClient();

        $dek = random_bytes(32);
        $dataIv = random_bytes(12);
        $dataTag = '';
        $encryptedData = openssl_encrypt(
            $data, 'aes-256-gcm', $dek, OPENSSL_RAW_DATA, $dataIv, $dataTag
        );

        // Ask the KMS service to wrap the DEK. The KEK itself never enters this process.
        $wrapped = $kms->encrypt(self::PICTURE_KMS_KEY, $dek);

        sodium_memzero($dek);

        return json_encode([
            'data'        => base64_encode($encryptedData),
            'iv'          => base64_encode($dataIv),
            'tag'         => base64_encode($dataTag),
            'edek'        => $wrapped['ciphertext'],
            'dek_iv'      => $wrapped['iv'],
            'dek_tag'     => $wrapped['tag'],
            'kek_version' => $wrapped['keyVersion'],
        ]);
    }

    private function decryptWithDEK(array $payload)
    {
        $kms = new KmsClient();

        // Ask the KMS service to unwrap the DEK.
        try {
            $dek = $kms->decrypt(
                self::PICTURE_KMS_KEY,
                (int) $payload['kek_version'],
                $payload['edek'],
                $payload['dek_iv'],
                $payload['dek_tag']
            );
        } catch (\RuntimeException $e) {
            abort(403, 'Failed to unwrap DEK via KMS. Incorrect key version or tampered data.');
        }

        $decrypted = openssl_decrypt(
            base64_decode($payload['data']), 'aes-256-gcm', $dek,
            OPENSSL_RAW_DATA, base64_decode($payload['iv']), base64_decode($payload['tag'])
        );

        sodium_memzero($dek);

        if ($decrypted === false) {
            abort(403, 'Failed to decrypt picture data — data may be tampered.');
        }

        return $decrypted;
    }
}