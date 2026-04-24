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

    public function proxyAnalyze(Request $request)
    {
        try {
            // Cache user embeddings for 10 minutes to reduce DB load
            $users = Cache::remember('user_face_embeddings', 600, function() {
                return User::whereNotNull('face_embedding')
                    ->select('id', 'name', 'face_embedding')
                    ->get()
                    ->map(function($user) {
                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'embedding' => $user->face_embedding
                        ];
                    });
            });

            // Proxy the request to AI Server with 10s timeout
            $response = Http::timeout(10)->post('http://127.0.0.1:5000/analyze', [
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
                    $currentTime = $now->format('H:i'); // Consistent with record()
                    
                    // Fetch settings to know current session
                    $settings = Setting::pluck('value', 'key')->toArray();
                    $outStart = $settings['attendance_out_start'] ?? '16:00';
                    
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

        // Fetch settings
        $settings = Setting::pluck('value', 'key')->toArray();
        $inStart = $settings['attendance_in_start'] ?? '07:00';
        $inEnd = $settings['attendance_in_end'] ?? '09:00';
        $outStart = $settings['attendance_out_start'] ?? '16:00';
        $outEnd = $settings['attendance_out_end'] ?? '18:00';

        // Simplify time for comparison (H:i)
        $compareTime = $now->format('H:i');

        // Find existing or create new attendance record for today
        $attendance = Attendance::firstOrCreate(
            ['user_id' => $user->id, 'date' => $today],
            ['status' => 'present', 'override_status' => 'machine']
        );

        $status = '';
        $message = '';

        // 1. Check-in Logic (Jendela Masuk: inStart s/d outStart)
        if ($compareTime >= $inStart && $compareTime < $outStart) {
            if (!$attendance->check_in) {
                $finalStatus = ($compareTime <= $inEnd) ? 'present' : 'late';
                $attendance->update([
                    'check_in' => $currentTime,
                    'status' => $finalStatus
                ]);
                
                $status = 'check-in';
                $message = ($finalStatus == 'late') 
                    ? 'Terlambat! Absensi berhasil dicatat untuk ' . $user->name 
                    : 'Check-in berhasil untuk ' . $user->name;
            } else {
                $status = 'already';
                $message = $user->name . ' sudah melakukan check-in.';
            }
        } 
        // 2. Check-out Logic (Jendela Pulang: outStart s/d outEnd)
        // Handle midnight span (e.g., 16:00 to 00:00)
        else {
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
                    $attendance->update(['check_out' => $currentTime]);
                    $status = 'check-out';
                    $message = 'Check-out berhasil untuk ' . $user->name . '. Selamat beristirahat!';
                } else {
                    $status = 'already';
                    $message = $user->name . ' sudah melakukan check-out.';
                }
            } 
            // 3. Diluar Jam Kerja
            else {
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

        return response()->json([
            'status' => 'success',
            'type' => $status,
            'message' => $message,
            'user' => $user->name,
            'time' => $currentTime
        ]);
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
