<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;

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
        $yesterday = Carbon::yesterday()->toDateString();
        
        $this->info("Starting attendance cleanup for date: $yesterday");

        // Find all users who have a check-in but no check-out for yesterday
        $incomplete = Attendance::whereDate('date', $yesterday)
            ->whereNotNull('check_in')
            ->whereNull('check_out')
            ->get();

        foreach ($incomplete as $record) {
            $record->update([
                'status' => 'absent',
                'notes' => 'Automatic: Missing check-out (Sapu Bersih)'
            ]);
            $this->line("Marked {$record->user->name} as absent (Missing check-out)");
        }

        // Optional: Ensure all users have a record for yesterday (mark as absent if missing)
        // This might be redundant depending on the system design, but the user asked for "sapu bersih"
        // Let's stick to the specific "missing checkout" instruction first.
        
        $this->info("Cleanup completed.");
    }
}
