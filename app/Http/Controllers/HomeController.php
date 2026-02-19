<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Absence;
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
        
        $monthlyStats = [
            'present' => Attendance::forDateRange($startOfMonth, $endOfMonth)
                ->byStatus('present')->count(),
            'absent' => Attendance::forDateRange($startOfMonth, $endOfMonth)
                ->byStatus('absent')->count(),
            'late' => Attendance::forDateRange($startOfMonth, $endOfMonth)
                ->byStatus('late')->count(),
            'leave' => Attendance::forDateRange($startOfMonth, $endOfMonth)
                ->byStatus('leave')->count(),
            'sick' => Attendance::forDateRange($startOfMonth, $endOfMonth)
                ->byStatus('sick')->count(),
        ];
        
        $totalAttendanceToday = Attendance::where('date', $today)->count();
        $totalAbsenceToday = Absence::where('date', $today)->count();
        
        $labels = [];
        $presentData = [];
        $absentData = [];
        $lateData = [];
        $absenceData = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $labels[] = Carbon::parse($date)->format('M d');
            
            $presentData[] = Attendance::where('date', $date)
                ->where('status', 'present')->count();
            $absentData[] = Attendance::where('date', $date)
                ->where('status', 'absent')->count();
            $lateData[] = Attendance::where('date', $date)
                ->where('status', 'late')->count();
            $absenceData[] = Absence::where('date', $date)->count();
        }
        
        $userStats = User::withCount([
            'attendances' => function ($query) use ($startOfMonth, $endOfMonth) {
                $query->forDateRange($startOfMonth, $endOfMonth)->byStatus('present');
            },
            'attendances as absent_count' => function ($query) use ($startOfMonth, $endOfMonth) {
                $query->forDateRange($startOfMonth, $endOfMonth)->byStatus('absent');
            },
        ])->get();
        
        $recentAbsences = Absence::orderBy('date', 'desc')->limit(5)->get();
        
        $todayAttendance = Attendance::where('date', $today)->get();
        
        $pendingAbsences = Absence::where('status', 'pending')->count();
        
        $anomalies = $this->detectAnomalies($user);

        return view('home', compact(
            'monthlyStats',
            'labels',
            'presentData',
            'absentData',
            'lateData',
            'absenceData',
            'userStats',
            'recentAbsences',
            'todayAttendance',
            'pendingAbsences',
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
        
        $recentLogins = \DB::table('sessions')
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
        
        $monthAbsenceCount = Absence::forUser($user->id)
            ->forDateRange(now()->startOfMonth(), now()->endOfMonth())
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
