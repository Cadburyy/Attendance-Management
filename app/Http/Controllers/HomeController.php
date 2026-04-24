<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();
        
        $today = now()->timezone('Asia/Jakarta')->toDateString();
        $startOfMonth = now()->timezone('Asia/Jakarta')->startOfMonth()->toDateString();
        $endOfMonth = now()->timezone('Asia/Jakarta')->endOfMonth()->toDateString();

        $queryBase = Attendance::query();
        if (!$user->can('override')) {
            $queryBase->where('user_id', $user->id);
        }
        
        $monthlyRawStats = (clone $queryBase)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $monthlyStats = [
            'present' => $monthlyRawStats['present'] ?? 0,
            'absent'  => $monthlyRawStats['absent'] ?? 0,
            'late'    => $monthlyRawStats['late'] ?? 0,
            'leave'   => $monthlyRawStats['leave'] ?? 0,
            'sick'    => $monthlyRawStats['sick'] ?? 0,
        ];
        
        $totalAttendanceToday = (clone $queryBase)->where('date', $today)->whereIn('status', ['present', 'late'])->count();
        $totalAbsenceToday = (clone $queryBase)->where('date', $today)->whereIn('status', ['absent', 'sick', 'leave'])->count();

        $startDate7Days = now()->timezone('Asia/Jakarta')->subDays(6)->toDateString();

        $sevenDaysRawData = (clone $queryBase)
            ->where('date', '>=', $startDate7Days)
            ->where('date', '<=', $today)
            ->select('date', 'status', DB::raw('count(*) as total'))
            ->groupBy('date', 'status')
            ->get();

        $overrideRawData = (clone $queryBase)
            ->whereDate('updated_at', '>=', $startDate7Days)
            ->whereIn('override_status', ['pending', 'approved', 'rejected']) // Exclude 'machine'
            ->select(DB::raw('DATE(updated_at) as date'), DB::raw('count(*) as total'))
            ->groupBy(DB::raw('DATE(updated_at)'))
            ->pluck('total', 'date')
            ->toArray();

        $labels = [];
        $presentData = [];
        $absentData = [];
        $lateData = [];
        $overrideRequestData = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->timezone('Asia/Jakarta')->subDays($i)->toDateString();
            $labels[] = Carbon::parse($date)->format('M d');
            
            // Filter the single collection rather than querying the DB repeatedly
            // Using a callback to ensure correct date comparison with Carbon objects
            $dayRecords = $sevenDaysRawData->filter(function($record) use ($date) {
                return $record->date->toDateString() == $date;
            });
            
            $presentData[] = $dayRecords->where('status', 'present')->sum('total');
            $absentData[] = $dayRecords->whereIn('status', ['absent', 'sick', 'leave'])->sum('total');
            $lateData[] = $dayRecords->where('status', 'late')->sum('total');
            
            $overrideRequestData[] = $overrideRawData[$date] ?? 0;
        }

        $userStats = []; 

        $recentOverrides = (clone $queryBase)
            ->with('user:id,name')
            ->where('override_status', 'pending')
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();
        
        $todayAttendance = []; 

        $pendingOverrides = (clone $queryBase)->where('override_status', 'pending')->count();
        
        $anomalies = $this->detectAnomalies($user);

        if (!$user->can('override')) {
            $todayRecord = Attendance::where('user_id', $user->id)->where('date', $today)->first();
            if ($todayRecord && $todayRecord->check_in && !$todayRecord->check_out && now()->timezone('Asia/Jakarta')->hour >= 17) {
                 $anomalies[] = [
                     'type' => 'warning', 'icon' => 'fa-exclamation-triangle',
                     'title' => 'Missing Check-Out', 'message' => "You haven't checked out today."
                 ];
            }
        } else {
             if ($pendingOverrides > 0) {
                 $anomalies[] = [
                     'type' => 'info', 'icon' => 'fa-info-circle',
                     'title' => 'Pending Requests', 'message' => "There are $pendingOverrides status change requests pending your approval."
                 ];
             }
        }

        return view('home', compact(
            'monthlyStats',
            'labels',
            'presentData',
            'absentData',
            'lateData',
            'overrideRequestData',
            'userStats',
            'recentOverrides',
            'todayAttendance',
            'pendingOverrides',
            'totalAttendanceToday',
            'totalAbsenceToday',
            'anomalies',
            'user'
        ));
    }

    private function detectAnomalies($user)
    {
        $anomalies = [];
        
        $currentHour = now()->timezone('Asia/Jakarta')->hour;
        $currentDay = now()->timezone('Asia/Jakarta')->dayOfWeek;
        
        $workingHourStart = 8;
        $workingHourEnd = 17;
        
        if ($currentHour < $workingHourStart || $currentHour >= $workingHourEnd) {
            $anomalies[] = [
                'type' => 'warning',
                'title' => 'Outside Working Hours',
                'message' => 'You are accessing the system outside normal working hours (' . 
                    sprintf('%02d:00', $workingHourStart) . ' - ' . sprintf('%02d:00', $workingHourEnd) . ')',
                'icon' => 'fa-clock'
            ];
        }
        
        if ($currentDay == 0 || $currentDay == 6) {
            $anomalies[] = [
                'type' => 'warning',
                'title' => 'Weekend Access',
                'message' => 'You are accessing the system on a weekend. Contact administrator if unauthorized.',
                'icon' => 'fa-calendar'
            ];
        }
        
        $recentLogins = DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('last_activity', '>=', now()->subMinutes(5)->timestamp)
            ->count();
            
        if ($recentLogins > 1) {
            $anomalies[] = [
                'type' => 'info',
                'title' => 'Multiple Active Sessions',
                'message' => 'You have ' . $recentLogins . ' active sessions. For security, ensure only authorized devices are accessing your account.',
                'icon' => 'fa-shield'
            ];
        }

        $monthAbsenceCount = Attendance::where('user_id', $user->id)
            ->whereBetween('date', [
                now()->timezone('Asia/Jakarta')->startOfMonth()->toDateString(), 
                now()->timezone('Asia/Jakarta')->endOfMonth()->toDateString()
            ])
            ->whereIn('status', ['absent', 'sick', 'leave'])
            ->count();

        $workingDaysInMonth = $this->getWorkingDaysCount(
            now()->timezone('Asia/Jakarta')->startOfMonth(), 
            now()->timezone('Asia/Jakarta')->endOfMonth()
        );
        
        if ($workingDaysInMonth > 0 && ($monthAbsenceCount / $workingDaysInMonth) > 0.3) {
            $anomalies[] = [
                'type' => 'danger',
                'title' => 'High Absence Rate',
                'message' => 'Your absence rate this month is ' . round(($monthAbsenceCount / $workingDaysInMonth) * 100) . '%. Contact HR if this is unexpected.',
                'icon' => 'fa-exclamation-triangle'
            ];
        }

        return $anomalies;
    }

    private function getWorkingDaysCount($startDate, $endDate)
    {
        $count = 0;
        $current = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        while ($current <= $end) {
            if ($current->isWeekday()) {
                $count++;
            }
            $current->addDay();
        }

        return $count;
    }
}