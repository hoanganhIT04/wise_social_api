<?php

namespace App\Console\Commands;
use Illuminate\Support\Facades\Log;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;



class CheckInfringe extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check-infringe';

    /**
     * The console command description.
     *
     * 
     * 
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $detectWarning = DB::table('violence_warnings')->get();
        if(empty($detectWarning)) {
            Log::info("Detect user done!");
        }
        $arrUserBanned = [];
        foreach($detectWarning as $warning) {
            if ($warning->infringe > 5) {
                DB::table('users')->where('id', $warning->user_id)
                ->update([
                    'status' => User::STATUS_BANNED
                ]);
                $arrUserBanned[] = $warning->user_id;
            }
        }
        Log::info("Banner for user: " . json_encode($arrUserBanned));
    }
}
    
