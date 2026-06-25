<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class AbsenceController extends Controller
{
    public function index()
    {
        $settings = Cache::remember('app_settings', 60, function() {
            return Setting::pluck('value', 'key')->toArray();
        });
        return view('absence', compact('settings'));
    }

    private function getActiveShiftDetails($now)
    {
        $settings = Setting::pluck('value', 'key')->toArray();
        $totalShifts = (int)($settings['total_shifts'] ?? 1);

        $shifts = [];
        for ($i = 1; $i <= $totalShifts; $i++) {
            $shifts[$i] = [
                'in_start' => $settings["shift_{$i}_in_start"] ?? null,
                'in_end' => $settings["shift_{$i}_in_end"] ?? null,
                'out_start' => $settings["shift_{$i}_out_start"] ?? null,
                'out_end' => $settings["shift_{$i}_out_end"] ?? null,
            ];
        }

        if (empty($shifts[1]['in_start'])) {
            $shifts[1] = [
                'in_start' => $settings['attendance_in_start'] ?? '06:00',
                'in_end' => $settings['attendance_in_end'] ?? '08:00',
                'out_start' => $settings['attendance_out_start'] ?? '14:00',
                'out_end' => $settings['attendance_out_end'] ?? '16:00',
            ];
        }

        $compareTime = $now->format('H:i');
        $activeShift = null;

        foreach ($shifts as $num => $shift) {
            if (!$shift['in_start'] || !$shift['out_start']) continue;

            $inStart = $shift['in_start'];
            $outStart = $shift['out_start'];

            if ($outStart < $inStart) {
                if ($compareTime >= $inStart || $compareTime <= $outStart) {
                    $activeShift = $num;
                    break;
                }
            } else {
                if ($compareTime >= $inStart && $compareTime <= $outStart) {
                    $activeShift = $num;
                    break;
                }
            }
        }

        if (!$activeShift) {
            return null;
        }

        return array_merge($shifts[$activeShift], ['shift_number' => $activeShift]);
    }

    private function getActiveAttendance($userId, $now)
    {
        $today = $now->toDateString();
        $attendance = Attendance::where('user_id', $userId)
            ->where('date', $today)
            ->whereNotNull('check_in')
            ->whereNull('check_out')
            ->first();
            
        if ($attendance) {
            return $attendance;
        }

        $yesterday = $now->copy()->subDay()->toDateString();
        $yesterdayAttendance = Attendance::where('user_id', $userId)
            ->where('date', $yesterday)
            ->whereNotNull('check_in')
            ->whereNull('check_out')
            ->orderBy('id', 'desc')
            ->first();
        
        if ($yesterdayAttendance) {
            $shiftNum = $yesterdayAttendance->shift;
            if ($shiftNum) {
                $settings = Setting::pluck('value', 'key')->toArray();
                
                $outStart = $settings["shift_{$shiftNum}_out_start"] ?? null;
                $outEnd = $settings["shift_{$shiftNum}_out_end"] ?? null;
                
                if ($outStart && $outEnd) {
                    $compareTime = $now->format('H:i');
                    $isCheckoutTime = ($outEnd < $outStart) 
                        ? ($compareTime >= $outStart || $compareTime <= $outEnd)
                        : ($compareTime >= $outStart && $compareTime <= $outEnd);
                        
                    if ($isCheckoutTime) {
                        return $yesterdayAttendance;
                    }
                }
            }
        }
        
        return null;
    }

    public function proxyAnalyze(Request $request)
    {
        try {
            $users = Cache::remember('user_face_embeddings', 600, function() {
                return User::whereNotNull('face_embedding')
                    ->select('id', 'name', 'face_embedding')
                    ->with(['faceReferences' => function($query) {
                        $query->orderBy('created_at', 'desc')->limit(5);
                    }])
                    ->get()
                    ->map(function($user) {
                        $allEmbeddings = [];
                        if ($user->face_embedding) {
                            $allEmbeddings[] = $user->face_embedding;
                        }
                        foreach ($user->faceReferences as $ref) {
                            if ($ref->embedding) {
                                $allEmbeddings[] = $ref->embedding;
                            }
                        }
                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'embeddings' => $allEmbeddings,
                        ];
                    });
            });

            $response = Http::timeout(30)->post('http://127.0.0.1:5000/analyze', [
                'image' => $request->image,
                'user_embeddings' => $users
            ]);

            $aiData = $response->json();

            if (isset($aiData['status']) && $aiData['status'] === 'success' && isset($aiData['user_id'])) {
                $detectedUser = User::find($aiData['user_id']); 
                if ($detectedUser) {
                    $now = Carbon::now('Asia/Jakarta');
                    $today = $now->toDateString();
                    $currentTime = $now->format('H:i');
                    
                    if (isset($aiData['has_uniform']) && $aiData['has_uniform'] === false) {
                        if (!$detectedUser->can('bypass-uniform')) {
                            $aiData['status'] = 'no_uniform';
                            $aiData['message'] = 'Uniform not valid for ' . $now->format('l');
                        }
                    }

                    $attendance = $this->getActiveAttendance($detectedUser->id, $now);
                    
                    $already = false;
                    if ($attendance && $attendance->check_in) {
                        $activeShiftNum = $attendance->shift;
                        $settings = Setting::pluck('value', 'key')->toArray();
                        $totalShifts = (int)($settings['total_shifts'] ?? 1);

                        if (!$activeShiftNum) {
                            $checkInTimeStr = substr($attendance->check_in, 0, 5);
                            for ($i = 1; $i <= $totalShifts; $i++) {
                                $inStart = $settings["shift_{$i}_in_start"] ?? '00:00';
                                $outStart = $settings["shift_{$i}_out_start"] ?? '00:00';
                                if ($outStart < $inStart) {
                                    if ($checkInTimeStr >= $inStart || $checkInTimeStr < $outStart) {
                                        $activeShiftNum = $i;
                                        break;
                                    }
                                } else {
                                    if ($checkInTimeStr >= $inStart && $checkInTimeStr < $outStart) {
                                        $activeShiftNum = $i;
                                        break;
                                    }
                                }
                            }
                        }

                        if (!$activeShiftNum) {
                            $fallbackShift = $this->getActiveShiftDetails($now);
                            $activeShiftNum = $fallbackShift ? $fallbackShift['shift_number'] : 1;
                        }
                        
                        $outStart = $settings["shift_{$activeShiftNum}_out_start"] ?? '00:00';
                        
                        if ($currentTime < $outStart) {
                            $already = true;
                        } else {
                            $already = (bool)$attendance->check_out;
                        }
                    } else {
                        $shift = $this->getActiveShiftDetails($now);
                        if (!$shift) {
                            $already = false;
                        } else {
                            $already = $attendance ? (bool)$attendance->check_in : false;
                        }
                    }
                    $aiData['already_attended'] = $already;
                }
            }

            return response()->json($aiData, $response->status());

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'AI Proxy Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function record(Request $request)
    {
        $request->validate([
            'user_id' => 'required_without:name|integer',
            'name' => 'required_without:user_id|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        if ($request->user_id) {
            $user = User::find($request->user_id);
        } else {
            $user = User::where('name', $request->name)->first();
        }

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found: ' . $request->name
            ], 404);
        }

        $now = Carbon::now('Asia/Jakarta');
        $today = $now->toDateString();
        $currentTime = $now->format('H:i:s');
        $compareTime = $now->format('H:i');

        $attendance = $this->getActiveAttendance($user->id, $now);

        $status = '';
        $message = '';

        if ($attendance && $attendance->check_in) {
            $activeShiftNum = $attendance->shift;
            $settings = Setting::pluck('value', 'key')->toArray();
            $totalShifts = (int)($settings['total_shifts'] ?? 1);
            
            if (!$activeShiftNum) {
                $checkInTimeStr = substr($attendance->check_in, 0, 5);
                for ($i = 1; $i <= $totalShifts; $i++) {
                    $inStart = $settings["shift_{$i}_in_start"] ?? '00:00';
                    $outStart = $settings["shift_{$i}_out_start"] ?? '00:00';
                    if ($outStart < $inStart) {
                        if ($checkInTimeStr >= $inStart || $checkInTimeStr < $outStart) {
                            $activeShiftNum = $i;
                            break;
                        }
                    } else {
                        if ($checkInTimeStr >= $inStart && $checkInTimeStr < $outStart) {
                            $activeShiftNum = $i;
                            break;
                        }
                    }
                }
            }

            if (!$activeShiftNum) {
                $fallbackShift = $this->getActiveShiftDetails($now);
                $activeShiftNum = $fallbackShift ? $fallbackShift['shift_number'] : 1;
            }

            $inStart = $settings["shift_{$activeShiftNum}_in_start"] ?? '00:00';
            $inEnd = $settings["shift_{$activeShiftNum}_in_end"] ?? '00:00';
            $outStart = $settings["shift_{$activeShiftNum}_out_start"] ?? '00:00';
            $outEnd = $settings["shift_{$activeShiftNum}_out_end"] ?? '00:00';

            $isCheckoutTime = ($outEnd < $outStart) 
                ? ($compareTime >= $outStart || $compareTime <= $outEnd)
                : ($compareTime >= $outStart && $compareTime <= $outEnd);

            if ($isCheckoutTime) {
                if (!$attendance->check_out) {
                    $attendance->update([
                        'check_out' => $currentTime,
                        'latitude' => $request->latitude ?? $attendance->latitude,
                        'longitude' => $request->longitude ?? $attendance->longitude,
                    ]);
                    $status = 'check-out';
                    $message = 'Check-out berhasil untuk ' . $user->name . '. Selamat beristirahat!';
                } else {
                    $status = 'already';
                    $message = $user->name . ' sudah melakukan check-out.';
                }
            } else {
                if ($compareTime >= $inStart && $compareTime < $outStart) {
                    $status = 'already';
                    $message = $user->name . ' sudah melakukan check-in.';
                } else {
                    if ($compareTime < $outStart) {
                        $errMessage = "Belum masuk jam pulang shift {$activeShiftNum}. (Mulai: $outStart)";
                    } else {
                        $errMessage = "Sudah melewati batas jam pulang shift {$activeShiftNum}. (Batas: $outEnd)";
                    }
                    return response()->json([
                        'status' => 'outside_hours',
                        'message' => $errMessage . " [Sekarang: $compareTime]"
                    ]);
                }
            }
        } else {
            $shift = $this->getActiveShiftDetails($now);
            if (!$shift) {
                return response()->json([
                    'status' => 'outside_hours',
                    'message' => "Diluar jadwal operasional. [Sekarang: $compareTime]"
                ]);
            }

            $inStart = $shift['in_start'];
            $inEnd = $shift['in_end'];
            $outStart = $shift['out_start'];

            $isCheckinTime = ($outStart < $inStart)
                ? ($compareTime >= $inStart || $compareTime <= $outStart)
                : ($compareTime >= $inStart && $compareTime <= $outStart);

            if ($isCheckinTime) {
                if (!$attendance) {
                    $attendance = Attendance::create([
                        'user_id' => $user->id,
                        'date' => $today,
                        'status' => 'present',
                        'override_status' => 'machine'
                    ]);
                }

                if (!$attendance->check_in) {
                    $isOnTime = ($inEnd < $inStart)
                        ? ($compareTime >= $inStart || $compareTime <= $inEnd)
                        : ($compareTime >= $inStart && $compareTime <= $inEnd);
                    $finalStatus = $isOnTime ? 'present' : 'late';
                    $attendance->update([
                        'check_in' => $currentTime,
                        'status' => $finalStatus,
                        'shift' => $shift['shift_number'],
                        'latitude' => $request->latitude,
                        'longitude' => $request->longitude,
                    ]);
                    
                    $status = 'check-in';
                    $message = ($finalStatus == 'late') 
                        ? 'Terlambat! Absensi berhasil dicatat untuk ' . $user->name 
                        : 'Check-in berhasil untuk ' . $user->name;
                } else {
                    $status = 'already';
                    $message = $user->name . ' sudah melakukan check-in.';
                }
            } else {
                return response()->json([
                    'status' => 'outside_hours',
                    'message' => "Belum masuk jam check-in. [Sekarang: $compareTime]"
                ]);
            }
        }

        if ($request->filled('image')) {
            // Encrypt the base64 image using the KEK/DEK architecture
            $secureImagePayload = $this->encryptWithDEK($request->image);
            $attendance->update(['image' => $secureImagePayload]);
        }

        if ($request->filled('image') && $user && in_array($status, ['check-in', 'check-out'])) {
            try {
                $embeddingResponse = Http::timeout(15)->post('http://127.0.0.1:5000/represent', [
                    'image' => $request->image,
                ]);

                if ($embeddingResponse->successful() && isset($embeddingResponse->json()['embedding'])) {
                    $source = $request->user_id ? 'ai_attendance' : 'manual_attendance';
                    \App\Models\FaceReference::create([
                        'user_id' => $user->id,
                        'embedding' => $embeddingResponse->json()['embedding'],
                        'source' => $source,
                        'image_path' => null, 
                    ]);

                    $oldRefs = \App\Models\FaceReference::where('user_id', $user->id)
                        ->orderBy('created_at', 'desc')
                        ->skip(5)
                        ->take(100)
                        ->pluck('id');

                    if ($oldRefs->isNotEmpty()) {
                        \App\Models\FaceReference::whereIn('id', $oldRefs)->delete();
                    }

                    Cache::forget('user_face_embeddings');
                }
            } catch (\Exception $e) {
                \Log::warning("Face reference extraction failed: " . $e->getMessage());
            }
        }

        return response()->json([
            'status' => 'success',
            'type' => $status,
            'message' => $message,
            'user' => $user->name,
            'time' => $currentTime
        ]);
    }

    public function verifyGeolocation(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $settings = Setting::pluck('value', 'key')->toArray();

        if (empty($settings['geolocation_enabled']) || $settings['geolocation_enabled'] == '0') {
            return response()->json(['status' => 'allowed', 'message' => 'Geolocation check is disabled.']);
        }

        $officeLat = (float)($settings['office_latitude'] ?? 0);
        $officeLng = (float)($settings['office_longitude'] ?? 0);
        $maxRadius = (int)($settings['office_radius'] ?? 100);

        $earthRadius = 6371000;
        $dLat = deg2rad($request->latitude - $officeLat);
        $dLng = deg2rad($request->longitude - $officeLng);
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($officeLat)) * cos(deg2rad($request->latitude)) * sin($dLng/2) * sin($dLng/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distance = $earthRadius * $c;

        if ($distance <= $maxRadius) {
            return response()->json([
                'status' => 'allowed',
                'distance' => round($distance),
                'message' => 'You are within office range.'
            ]);
        } else {
            return response()->json([
                'status' => 'denied',
                'distance' => round($distance),
                'message' => "You are " . round($distance) . "m away from office. Maximum allowed: {$maxRadius}m."
            ]);
        }
    }

    public function proxyLiveness(Request $request)
    {
        try {
            $response = Http::timeout(30)->post('http://127.0.0.1:5000/liveness', [
                'frames' => $request->frames,
                'challenge' => $request->challenge,
                'flash_active' => $request->flash_active,
            ]);
            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Liveness Proxy Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getRecent()
    {
        $now = Carbon::now('Asia/Jakarta');
        $today = $now->toDateString();
        $yesterday = $now->copy()->subDay()->toDateString();

        $attendances = Attendance::with('user')
            ->whereIn('date', [$today, $yesterday])
            ->whereNotNull('check_in')
            ->orderBy('updated_at', 'desc')
            ->get();

        $settings = Setting::pluck('value', 'key')->toArray();

        $filtered = $attendances->filter(function($att) use ($now, $settings) {
            $shiftNum = $att->shift ?? 1;
            $inStart = $settings["shift_{$shiftNum}_in_start"] ?? '06:00';
            $outStart = $settings["shift_{$shiftNum}_out_start"] ?? '14:00';
            $outEnd = $settings["shift_{$shiftNum}_out_end"] ?? '16:00';

            $checkInDate = Carbon::parse($att->date);
            $checkOutDate = $checkInDate->copy();
            
            if ($outStart < $inStart || $outEnd < $outStart) {
                $checkOutDate->addDay();
            }
            
            $checkoutEndThreshold = Carbon::parse($checkOutDate->toDateString() . ' ' . $outEnd, 'Asia/Jakarta');

            if (!$att->check_out) {
                return $now->lessThanOrEqualTo($checkoutEndThreshold);
            }

            $updatedAt = Carbon::parse($att->updated_at)->timezone('Asia/Jakarta');
            if ($updatedAt->diffInMinutes($now) <= 120) {
                return true;
            }

            return false;
        });

        return response()->json($filtered->take(10)->values());
    }

    public function settings()
    {
        $settings = Setting::pluck('value', 'key')->toArray();
        $defaults = [
            'attendance_in_start' => '07:00',
            'attendance_in_end' => '09:00',
            'attendance_out_start' => '16:00',
            'attendance_out_end' => '18:00',
        ];
        $settings = array_merge($defaults, $settings);

        return view('absence-settings', compact('settings'));
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'attendance_in_start' => 'required|date_format:H:i',
            'attendance_in_end' => 'required|date_format:H:i',
            'attendance_out_start' => 'required|date_format:H:i',
            'attendance_out_end' => 'required|date_format:H:i',
        ]);

        Setting::updateOrCreate(['key' => 'attendance_in_start'], ['value' => $request->attendance_in_start]);
        Setting::updateOrCreate(['key' => 'attendance_in_end'], ['value' => $request->attendance_in_end]);
        Setting::updateOrCreate(['key' => 'attendance_out_start'], ['value' => $request->attendance_out_start]);
        Setting::updateOrCreate(['key' => 'attendance_out_end'], ['value' => $request->attendance_out_end]);

        cache()->forget('app_settings');

        return redirect()->back()->with('success', 'Attendance settings updated successfully!');
    }

    public function getAllUsers()
    {
        $users = User::select('name')->orderBy('name', 'asc')->get();
        return response()->json($users);
    }

    public function aiQuery(Request $request)
    {
        $ip = $request->ip();
        \Illuminate\Support\Facades\Log::info('aiQuery requested from IP: ' . $ip);
        if (!in_array($ip, ['127.0.0.1', '::1'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $action = $request->query('action');
        $today = now()->timezone('Asia/Jakarta')->toDateString();
        $startOfMonth = now()->timezone('Asia/Jakarta')->startOfMonth()->toDateString();
        $endOfMonth = now()->timezone('Asia/Jakarta')->endOfMonth()->toDateString();

        switch ($action) {
            case 'get_today_summary':
                $records = Attendance::where('date', $today)->get();
                $totalEmployees = User::count();
                $presentCount = $records->whereIn('status', ['present', 'late'])->count();
                return response()->json([
                    'date' => $today,
                    'total_employees' => $totalEmployees,
                    'present' => $records->where('status', 'present')->count(),
                    'late' => $records->where('status', 'late')->count(),
                    'sick' => $records->where('status', 'sick')->count(),
                    'leave' => $records->where('status', 'leave')->count(),
                    'absent' => $records->where('status', 'absent')->count(),
                    'not_checked_in' => $totalEmployees - $presentCount,
                ]);

            case 'get_employees_by_status':
                $status = $request->query('status', 'present');
                $records = Attendance::with('user:id,name')
                    ->where('date', $today)
                    ->where('status', $status)
                    ->get()
                    ->map(fn($r) => [
                        'name' => $r->user->name ?? 'Unknown',
                        'check_in' => $r->check_in,
                        'check_out' => $r->check_out,
                        'shift' => $r->shift,
                    ]);
                return response()->json($records);

            case 'get_employee_monthly_recap':
                $employeeName = $request->query('employee_name');
                $user = User::where('name', 'LIKE', "%{$employeeName}%")->first();
                if (!$user) {
                    return response()->json(['error' => 'Employee not found']);
                }
                $records = Attendance::where('user_id', $user->id)
                    ->whereBetween('date', [$startOfMonth, $endOfMonth])
                    ->get();
                $grouped = $records->groupBy('status')->map->count();
                return response()->json([
                    'employee_name' => $user->name,
                    'month' => now()->timezone('Asia/Jakarta')->format('F Y'),
                    'present' => $grouped['present'] ?? 0,
                    'late' => $grouped['late'] ?? 0,
                    'sick' => $grouped['sick'] ?? 0,
                    'leave' => $grouped['leave'] ?? 0,
                    'absent' => $grouped['absent'] ?? 0,
                    'recent_logs' => $records->sortByDesc('date')->take(5)->map(fn($r) => [
                        'date' => ($r->date instanceof Carbon) ? $r->date->format('Y-m-d') : Carbon::parse($r->date)->format('Y-m-d'),
                        'status' => $r->status,
                        'check_in' => $r->check_in,
                        'check_out' => $r->check_out,
                    ])->values(),
                ]);

            case 'get_shifts':
                $settings = Setting::pluck('value', 'key')->toArray();
                $totalShifts = (int)($settings['total_shifts'] ?? 1);
                $shifts = [];
                for ($i = 1; $i <= $totalShifts; $i++) {
                    $shifts["shift_{$i}"] = [
                        'in_start' => $settings["shift_{$i}_in_start"] ?? null,
                        'in_end' => $settings["shift_{$i}_in_end"] ?? null,
                        'out_start' => $settings["shift_{$i}_out_start"] ?? null,
                        'out_end' => $settings["shift_{$i}_out_end"] ?? null,
                    ];
                }
                return response()->json($shifts);

            default:
                return response()->json(['error' => 'Unknown action'], 400);
        }
    }

    public function proxyChat(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->can('override')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $response = Http::timeout(120)->post('http://127.0.0.1:5000/chat', [
                'message' => $request->input('message'),
                'conversation_history' => $request->input('conversation_history', []),
                'user_name' => $user->name,
                'is_admin' => true,
                'current_date' => now()->timezone('Asia/Jakarta')->format('Y-m-d'),
            ]);
            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json([
                'reply' => 'Maaf, server AI sedang tidak aktif. Silakan coba lagi nanti.'
            ]);
        }
    }

    public function showPicture($id)
    {
        $attendance = Attendance::findOrFail($id);

        if (!$attendance->image) {
            abort(404, 'Image not found.');
        }

        $payload = json_decode($attendance->image, true);

        // Pastikan JSON memiliki semua data yang dibutuhkan
        if (!$payload || !isset($payload['data'], $payload['iv'], $payload['edek'], $payload['dek_iv'])) {
            abort(400, 'Invalid encrypted image payload.');
        }

        // 1. Setup KEK (Master Key) menggunakan AM2026
        $secretString = env('CUSTOM_DECRYPTION_KEY', 'AM2026');
        $kek = hash('sha256', $secretString, true);

        // 2. Buka Brankas Master untuk mengambil Kunci Loker (DEK)
        $dekIv = base64_decode($payload['dek_iv']);
        $dek = openssl_decrypt($payload['edek'], 'aes-256-cbc', $kek, 0, $dekIv);

        if ($dek === false) {
            abort(403, 'Failed to decrypt KEK. Incorrect master key.');
        }

        // 3. Buka Gembok Gambar menggunakan Kunci Loker (DEK)
        $iv = base64_decode($payload['iv']);
        $decryptedBase64 = openssl_decrypt($payload['data'], 'aes-256-cbc', $dek, 0, $iv);

        if ($decryptedBase64 === false) {
            abort(403, 'Failed to decrypt picture data.');
        }

        // Bersihkan prefix base64 jika ada (karena dikirim lewat API kamera)
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

    /**
     * KEK / DEK Encryption Architecture
     * Generates a unique DEK per transaction, encrypts the data with AES-256-CBC,
     * and encrypts the DEK with the application's Master KEK.
     */
    private function encryptWithDEK($data)
    {
        // 1. Setup KEK (Master Key) menggunakan AM2026
        $secretString = env('CUSTOM_DECRYPTION_KEY', 'AM2026');
        $kek = hash('sha256', $secretString, true);

        // 2. Generate Kunci Loker (DEK) - 32 bytes
        $dek = random_bytes(32); 
        
        // 3. Generate Bumbu Acak (IV) untuk Gambar
        $iv = random_bytes(openssl_cipher_iv_length('aes-256-cbc'));

        // 4. Gembok Gambar menggunakan DEK
        $encryptedData = openssl_encrypt($data, 'aes-256-cbc', $dek, 0, $iv);

        // 5. Generate Bumbu Acak (IV) untuk DEK
        $dekIv = random_bytes(openssl_cipher_iv_length('aes-256-cbc'));

        // 6. Gembok Kunci Loker (DEK) menggunakan KEK
        $encryptedDek = openssl_encrypt($dek, 'aes-256-cbc', $kek, 0, $dekIv);

        // 7. Bungkus semuanya jadi satu paket JSON
        return json_encode([
            'data' => $encryptedData,
            'iv' => base64_encode($iv),
            'edek' => $encryptedDek, 
            'dek_iv' => base64_encode($dekIv)
        ]);
    }
}