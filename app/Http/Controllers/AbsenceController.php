<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class AbsenceController extends Controller
{
    public function index()
    {
        return view('absence');
    }

    private function getActiveShiftDetails($now)
    {
        $settings = Setting::pluck('value', 'key')->toArray();

        // Build shifts array
        $shifts = [];
        for ($i = 1; $i <= 3; $i++) {
            $shifts[$i] = [
                'in_start' => $settings["shift_{$i}_in_start"] ?? null,
                'in_end' => $settings["shift_{$i}_in_end"] ?? null,
                'out_start' => $settings["shift_{$i}_out_start"] ?? null,
                'out_end' => $settings["shift_{$i}_out_end"] ?? null,
            ];
        }

        // Fallback: use old keys if shift 1 keys don't exist
        if (empty($shifts[1]['in_start'])) {
            $shifts[1] = [
                'in_start' => $settings['attendance_in_start'] ?? '07:00',
                'in_end' => $settings['attendance_in_end'] ?? '09:00',
                'out_start' => $settings['attendance_out_start'] ?? '16:00',
                'out_end' => $settings['attendance_out_end'] ?? '18:00',
            ];
        }

        $compareTime = $now->format('H:i');
        $activeShift = null;

        foreach ($shifts as $num => $shift) {
            if (!$shift['in_start']) continue;

            $inStart = $shift['in_start'];
            $outEnd = $shift['out_end'];

            // Handle midnight-spanning shifts
            if ($outEnd < $inStart) {
                if ($compareTime >= $inStart || $compareTime <= $outEnd) {
                    $activeShift = $num;
                    break;
                }
            } else {
                if ($compareTime >= $inStart && $compareTime <= $outEnd) {
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

    public function proxyAnalyze(Request $request)
    {
        try {
            // Cache user embeddings (registration + 5 recent references) to reduce DB load
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

            // Proxy the request to AI Server with 30s timeout
            $response = Http::timeout(30)->post('http://127.0.0.1:5000/analyze', [
                'image' => $request->image,
                'user_embeddings' => $users
            ]);

            $aiData = $response->json();

            // Check if user already attended for the current session
            if (isset($aiData['status']) && $aiData['status'] === 'success' && isset($aiData['user_id'])) {
                $detectedUser = User::find($aiData['user_id']); 
                if ($detectedUser) {
                    $now = Carbon::now('Asia/Jakarta');
                    $today = $now->toDateString();
                    $currentTime = $now->format('H:i');
                    
                    $shift = $this->getActiveShiftDetails($now);
                    $outStart = $shift ? $shift['out_start'] : '16:00';
                    
                    $attendance = Attendance::where('user_id', $detectedUser->id)->where('date', $today)->first();
                    
                    $already = false;
                    if ($attendance) {
                        if ($currentTime < $outStart) {
                            $already = (bool)$attendance->check_in; // Already checked in
                        } else {
                            $already = (bool)$attendance->check_out; // Already checked out
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
        $outEnd = $shift['out_end'];

        // Find existing or create new attendance record for today
        $attendance = Attendance::firstOrCreate(
            ['user_id' => $user->id, 'date' => $today],
            ['status' => 'present', 'override_status' => 'machine']
        );

        $status = '';
        $message = '';

        // Check-in vs Check-out
        if ($compareTime >= $inStart && $compareTime < $outStart) {
            if (!$attendance->check_in) {
                $finalStatus = ($compareTime <= $inEnd) ? 'present' : 'late';
                $attendance->update([
                    'check_in' => $currentTime,
                    'status' => $finalStatus,
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
            $isCheckoutTime = ($outEnd < $outStart) 
                ? ($compareTime >= $outStart || $compareTime <= $outEnd)
                : ($compareTime >= $outStart && $compareTime <= $outEnd);

            if ($isCheckoutTime) {
                if (!$attendance->check_in) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Anda belum melakukan Check-in pagi ini!'
                    ], 403);
                }

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
                if ($compareTime < $inStart) {
                    $errMessage = "Belum masuk jam absensi. (Mulai: $inStart)";
                } elseif ($compareTime >= $outEnd && $outEnd > $outStart) {
                    $errMessage = "Sudah melewati batas jam pulang. (Batas: $outEnd)";
                } else {
                    $errMessage = "Diluar jadwal operasional.";
                }

                return response()->json([
                    'status' => 'outside_hours',
                    'message' => $errMessage . " [Sekarang: $compareTime]"
                ]);
            }
        }

        // Simpan foto bukti jika ada
        $imagePath = null;
        if ($request->filled('image')) {
            $image = $request->image; // Base64 string
            $image = str_replace('data:image/webp;base64,', '', $image);
            $image = str_replace(' ', '+', $image);
            
            $fileName = 'attendance_' . $user->id . '_' . time() . '.webp';
            $imagePath = 'attendance_photos/' . $fileName;
            
            \Illuminate\Support\Facades\Storage::disk('public')->put($imagePath, base64_decode($image));
            $attendance->update(['image' => $imagePath]);
        }

        // Extract face embedding from attendance photo and save as reference
        if ($request->filled('image') && $user && in_array($status, ['check-in', 'check-out'])) {
            try {
                $embeddingResponse = Http::timeout(15)->post('http://127.0.0.1:5000/represent', [
                    'image' => $request->image,
                ]);

                if ($embeddingResponse->successful() && isset($embeddingResponse->json()['embedding'])) {
                    $source = $request->user_id ? 'ai_attendance' : 'manual_attendance';
                    // Save as face reference
                    \App\Models\FaceReference::create([
                        'user_id' => $user->id,
                        'embedding' => $embeddingResponse->json()['embedding'],
                        'source' => $source,
                        'image_path' => $imagePath,
                    ]);

                    // Prune: keep only the 5 most recent references per user
                    $oldRefs = \App\Models\FaceReference::where('user_id', $user->id)
                        ->orderBy('created_at', 'desc')
                        ->skip(5)
                        ->take(100)
                        ->pluck('id');

                    if ($oldRefs->isNotEmpty()) {
                        \App\Models\FaceReference::whereIn('id', $oldRefs)->delete();
                    }

                    // Clear the embedding cache so next scan uses updated data
                    Cache::forget('user_face_embeddings');
                }
            } catch (\Exception $e) {
                \Log::warning("Face reference extraction failed: " . $e->getMessage());
                // Non-critical — don't fail the attendance recording
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

        // If geolocation is disabled, always allow
        if (empty($settings['geolocation_enabled']) || $settings['geolocation_enabled'] == '0') {
            return response()->json(['status' => 'allowed', 'message' => 'Geolocation check is disabled.']);
        }

        $officeLat = (float)($settings['office_latitude'] ?? 0);
        $officeLng = (float)($settings['office_longitude'] ?? 0);
        $maxRadius = (int)($settings['office_radius'] ?? 100);

        // Haversine formula to calculate distance in meters
        $earthRadius = 6371000; // meters
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
            $response = Http::timeout(15)->post('http://127.0.0.1:5000/liveness', [
                'frames' => $request->frames,
                'challenge' => $request->challenge,
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
        $today = Carbon::today('Asia/Jakarta')->toDateString();
        $recent = Attendance::with('user')
            ->whereDate('date', $today)
            ->whereNotNull('check_in')
            ->orderBy('updated_at', 'desc')
            ->take(10)
            ->get();

        return response()->json($recent);
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
}
