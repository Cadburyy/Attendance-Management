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
                'in_start' => $settings['attendance_in_start'] ?? '06:00',
                'in_end' => $settings['attendance_in_end'] ?? '08:00',
                'out_start' => $settings['attendance_out_start'] ?? '14:00',
                'out_end' => $settings['attendance_out_end'] ?? '16:00',
            ];
        }

        // Apply defaults for other shifts if not set
        $defaults = [
            2 => [
                'in_start' => '14:00', 'in_end' => '15:00',
                'out_start' => '21:00', 'out_end' => '23:00',
            ],
            3 => [
                'in_start' => '21:00', 'in_end' => '22:00',
                'out_start' => '05:00', 'out_end' => '07:00',
            ],
        ];

        for ($i = 2; $i <= 3; $i++) {
            if (empty($shifts[$i]['in_start'])) {
                $shifts[$i] = $defaults[$i];
            }
        }

        $compareTime = $now->format('H:i');
        $activeShift = null;

        foreach ($shifts as $num => $shift) {
            if (!$shift['in_start'] || !$shift['out_start']) continue;

            $inStart = $shift['in_start'];
            $outStart = $shift['out_start'];

            // Match active shift based on its working period (from in_start to out_start)
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
        // 1. Try to find today's incomplete attendance record (checked in but not checked out yet)
        $attendance = Attendance::where('user_id', $userId)
            ->where('date', $today)
            ->whereNotNull('check_in')
            ->whereNull('check_out')
            ->first();
        if ($attendance) {
            return $attendance;
        }

        // 2. If no today's record, check yesterday's record for a cross-day shift
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
                $defaults = [
                    'shift_1_in_start' => '06:00', 'shift_1_in_end' => '08:00',
                    'shift_1_out_start' => '14:00', 'shift_1_out_end' => '16:00',
                    'shift_2_in_start' => '14:00', 'shift_2_in_end' => '15:00',
                    'shift_2_out_start' => '21:00', 'shift_2_out_end' => '23:00',
                    'shift_3_in_start' => '21:00', 'shift_3_in_end' => '22:00',
                    'shift_3_out_start' => '05:00', 'shift_3_out_end' => '07:00',
                ];
                $settings = array_merge($defaults, $settings);
                
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
                    
                    // Check if user has permission to bypass uniform detection
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
                        $defaults = [
                            'shift_1_in_start' => '06:00', 'shift_1_in_end' => '08:00',
                            'shift_1_out_start' => '14:00', 'shift_1_out_end' => '16:00',
                            'shift_2_in_start' => '14:00', 'shift_2_in_end' => '15:00',
                            'shift_2_out_start' => '21:00', 'shift_2_out_end' => '23:00',
                            'shift_3_in_start' => '21:00', 'shift_3_in_end' => '22:00',
                            'shift_3_out_start' => '05:00', 'shift_3_out_end' => '07:00',
                        ];
                        $settings = array_merge($defaults, $settings);

                        // If shift is empty in DB, fallback to matching check_in time against check-in windows
                        if (!$activeShiftNum) {
                            $checkInTimeStr = substr($attendance->check_in, 0, 5); // HH:MM
                            for ($i = 1; $i <= 3; $i++) {
                                $inStart = $settings["shift_{$i}_in_start"];
                                $outStart = $settings["shift_{$i}_out_start"];
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
                        $outStart = $settings["shift_{$activeShiftNum}_out_start"];
                        
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

        // Look up today's attendance record
        $attendance = $this->getActiveAttendance($user->id, $now);

        $status = '';
        $message = '';

        if ($attendance && $attendance->check_in) {
            // CHECK-OUT FLOW (using the locked shift from check-in)
            $activeShiftNum = $attendance->shift;
            $settings = Setting::pluck('value', 'key')->toArray();
            $defaults = [
                'shift_1_in_start' => '06:00', 'shift_1_in_end' => '08:00',
                'shift_1_out_start' => '14:00', 'shift_1_out_end' => '16:00',
                'shift_2_in_start' => '14:00', 'shift_2_in_end' => '15:00',
                'shift_2_out_start' => '21:00', 'shift_2_out_end' => '23:00',
                'shift_3_in_start' => '21:00', 'shift_3_in_end' => '22:00',
                'shift_3_out_start' => '05:00', 'shift_3_out_end' => '07:00',
            ];
            $settings = array_merge($defaults, $settings);
            
            // If shift is empty in DB, fallback to matching check_in time against check-in windows
            if (!$activeShiftNum) {
                $checkInTimeStr = substr($attendance->check_in, 0, 5); // HH:MM
                for ($i = 1; $i <= 3; $i++) {
                    $inStart = $settings["shift_{$i}_in_start"];
                    $outStart = $settings["shift_{$i}_out_start"];
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

            $inStart = $settings["shift_{$activeShiftNum}_in_start"];
            $inEnd = $settings["shift_{$activeShiftNum}_in_end"];
            $outStart = $settings["shift_{$activeShiftNum}_out_start"];
            $outEnd = $settings["shift_{$activeShiftNum}_out_end"];

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
            // CHECK-IN FLOW (detecting shift based on current time)
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

        // 1. Fetch recent records from today and yesterday
        $attendances = Attendance::with('user')
            ->whereIn('date', [$today, $yesterday])
            ->whereNotNull('check_in')
            ->orderBy('updated_at', 'desc')
            ->get();

        // 2. Load shift settings to determine checkout windows
        $settings = Setting::pluck('value', 'key')->toArray();
        $defaults = [
            'shift_1_in_start' => '06:00', 'shift_1_in_end' => '08:00',
            'shift_1_out_start' => '14:00', 'shift_1_out_end' => '16:00',
            'shift_2_in_start' => '14:00', 'shift_2_in_end' => '15:00',
            'shift_2_out_start' => '21:00', 'shift_2_out_end' => '23:00',
            'shift_3_in_start' => '21:00', 'shift_3_in_end' => '22:00',
            'shift_3_out_start' => '05:00', 'shift_3_out_end' => '07:00',
        ];
        $settings = array_merge($defaults, $settings);

        // 3. Filter the records
        $filtered = $attendances->filter(function($att) use ($now, $settings) {
            $shiftNum = $att->shift ?? 1;
            $inStart = $settings["shift_{$shiftNum}_in_start"] ?? '06:00';
            $outStart = $settings["shift_{$shiftNum}_out_start"] ?? '14:00';
            $outEnd = $settings["shift_{$shiftNum}_out_end"] ?? '16:00';

            $checkInDate = Carbon::parse($att->date);
            $checkOutDate = $checkInDate->copy();
            
            // If checkout spans to the next day (cross-day shift)
            if ($outStart < $inStart || $outEnd < $outStart) {
                $checkOutDate->addDay();
            }
            
            $checkoutEndThreshold = Carbon::parse($checkOutDate->toDateString() . ' ' . $outEnd, 'Asia/Jakarta');

            // CASE 1: If they have NOT checked out yet
            if (!$att->check_out) {
                // Only show them if the checkout window has not passed yet
                return $now->lessThanOrEqualTo($checkoutEndThreshold);
            }

            // CASE 2: If they have already checked out
            // Show them if the checkout was recent (within the last 2 hours)
            $updatedAt = Carbon::parse($att->updated_at)->timezone('Asia/Jakarta');
            if ($updatedAt->diffInMinutes($now) <= 120) {
                return true;
            }

            return false;
        });

        // 4. Return the top 10 recent records
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
}
