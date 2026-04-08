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
        $today = now()->toDateString();
        
        $startOfMonth = now()->startOfMonth()->toDateString();
        $endOfMonth = now()->endOfMonth()->toDateString();

        $queryBase = Attendance::query();
        if (!$user->can('override')) {
            $queryBase->where('user_id', $user->id);
        }
        
        $monthlyStats = [
            'present' => (clone $queryBase)->forDateRange($startOfMonth, $endOfMonth)->byStatus('present')->count(),
            'absent' => (clone $queryBase)->forDateRange($startOfMonth, $endOfMonth)->byStatus('absent')->count(),
            'late' => (clone $queryBase)->forDateRange($startOfMonth, $endOfMonth)->byStatus('late')->count(),
            'leave' => (clone $queryBase)->forDateRange($startOfMonth, $endOfMonth)->byStatus('leave')->count(),
            'sick' => (clone $queryBase)->forDateRange($startOfMonth, $endOfMonth)->byStatus('sick')->count(),
        ];
        
        $totalAttendanceToday = (clone $queryBase)->where('date', $today)->where('status', 'present')->count();
        $totalAbsenceToday = (clone $queryBase)->where('date', $today)->whereIn('status', ['absent', 'sick', 'leave'])->count();
        
        $labels = [];
        $presentData = [];
        $absentData = [];
        $lateData = [];
        $overrideRequestData = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $labels[] = Carbon::parse($date)->format('M d');
            
            $presentData[] = (clone $queryBase)->where('date', $date)->where('status', 'present')->count();
            $absentData[] = (clone $queryBase)->where('date', $date)->whereIn('status', ['absent', 'sick', 'leave'])->count();
            $lateData[] = (clone $queryBase)->where('date', $date)->where('status', 'late')->count();
            
            $overrideRequestData[] = (clone $queryBase)->whereDate('updated_at', $date)->whereNotNull('override_status')->count();
        }
        
        $userStats = User::withCount([
            'attendances as present_count' => function ($query) use ($startOfMonth, $endOfMonth) {
                $query->forDateRange($startOfMonth, $endOfMonth)->byStatus('present');
            },
            'attendances as absent_count' => function ($query) use ($startOfMonth, $endOfMonth) {
                $query->forDateRange($startOfMonth, $endOfMonth)->whereIn('status', ['absent', 'sick', 'leave']);
            },
        ])->get();
        
        $recentOverrides = (clone $queryBase)->with('user')
            ->where('override_status', 'pending')
            ->orderBy('updated_at', 'desc')
            ->limit(5)->get();
        
        $todayAttendance = (clone $queryBase)->where('date', $today)->get();
        
        $pendingOverrides = (clone $queryBase)->where('override_status', 'pending')->count();
        
        $anomalies = $this->detectAnomalies($user);

        if (!$user->can('override')) {
            $todayRecord = Attendance::where('user_id', $user->id)->where('date', $today)->first();
            if ($todayRecord && $todayRecord->check_in && !$todayRecord->check_out && now()->hour >= 17) {
                 $anomalies[] = [
                     'type' => 'warning', 'icon' => 'fa-exclamation-triangle',
                     'title' => 'Lupa Check-Out', 'message' => 'Anda belum melakukan check-out hari ini.'
                 ];
            }
        } else {
             if ($pendingOverrides > 0) {
                 $anomalies[] = [
                     'type' => 'info', 'icon' => 'fa-info-circle',
                     'title' => 'Permintaan Menunggu', 'message' => "Ada $pendingOverrides permintaan perubahan status yang perlu persetujuan Anda."
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
        $currentHour = now()->hour;
        $currentDay = now()->dayOfWeek;
        
        $workingHourStart = 8;
        $workingHourEnd = 17;
        
        if ($currentHour < $workingHourStart || $currentHour >= $workingHourEnd) {
            $anomalies[] = [
                'type' => 'warning',
                'title' => 'Outside Working Hours',
                'message' => 'You are accessing the system outside normal working hours (' . 
                    $workingHourStart . ':00 - ' . $workingHourEnd . ':00)',
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

        $monthAbsenceCount = Attendance::forUser($user->id)
            ->forDateRange(now()->startOfMonth(), now()->endOfMonth())
            ->whereIn('status', ['absent', 'sick', 'leave'])
            ->count();

        $workingDaysInMonth = $this->getWorkingDaysCount(now()->startOfMonth(), now()->endOfMonth());
        
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