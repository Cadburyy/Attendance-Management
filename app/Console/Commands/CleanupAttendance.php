<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use App\Models\Setting;

class CleanupAttendance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:cleanup';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Marks missing check-outs as absent at midnight (Sapu Bersih)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now('Asia/Jakarta');
        $today = $now->toDateString();
        
        $this->info("Starting attendance cleanup for all dates before today: $today");
        
        // Find all users who have a check-in but no check-out for any date before today
        $incomplete = Attendance::where('date', '<', $today)
            ->whereNotNull('check_in')
            ->whereNull('check_out')
            ->get();
            
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

        foreach ($incomplete as $record) {
            $shiftNum = $record->shift;
            if (!$shiftNum) {
                $shiftNum = 1;
            }

            $inStart = $settings["shift_{$shiftNum}_in_start"] ?? '06:00';
            $outStart = $settings["shift_{$shiftNum}_out_start"] ?? '14:00';
            $outEnd = $settings["shift_{$shiftNum}_out_end"] ?? '16:00';

            $checkInDate = Carbon::parse($record->date);
            $checkOutDate = $checkInDate->copy();
            
            // If check-out spans to the next day
            if ($outStart < $inStart || $outEnd < $outStart) {
                $checkOutDate->addDay();
            }
            
            $checkoutEndThreshold = Carbon::parse($checkOutDate->toDateString() . ' ' . $outEnd, 'Asia/Jakarta');

            if ($now->greaterThan($checkoutEndThreshold)) {
                $record->update([
                    'status' => 'absent',
                    'notes' => 'Automatic: Missing check-out (Sapu Bersih)'
                ]);
                $this->line("Marked {$record->user->name} on " . $record->date->toDateString() . " as absent (Missing check-out)");
            } else {
                $this->line("Skipped {$record->user->name} on " . $record->date->toDateString() . " - Shift {$shiftNum} checkout window has not passed yet (Ends: " . $checkoutEndThreshold->toDateTimeString() . ")");
            }
        }

        $this->info("Cleanup completed.");
    }
}
