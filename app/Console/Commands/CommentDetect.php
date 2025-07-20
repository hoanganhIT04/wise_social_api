<?php

namespace App\Console\Commands;

use App\Models\Comment;
use Illuminate\Console\Command;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class CommentDetect extends Command
{
    const DELECT_URL="http://127.0.0.1:5000/detect";
    const TAKE_RECORD = 100;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'comment-detect';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $client = new Client();
        $violentLogs = DB::table('violence_logs')->first();
        $commentId = 1;
        if (!is_null($violentLogs)) {
            if (isset($violentLogs->comment_id) && $violentLogs->comment_id > 0) {
                $commentId = $violentLogs->comment_id;
            }
        }
        // Get comments
        $comments = Comment::select('id', 'comment', 'user_id')
            ->orderBy('id', 'ASC')
            ->where('id', '>', $commentId)
            ->take(self::TAKE_RECORD)->get();
        $recordIndex = $commentId;
        if (empty($comments)) {
            Log::info("Detect comment done!");
            return null;
        }
        foreach ($comments as $comment) {
            $detect = $client->request('POST',self::DELECT_URL, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => [
                    'text' => $comment->comment,
                ],
            ]);
            $response = json_decode($detect->getBody(), true);
            if ($response['is_bad'] == true || $response['is_bad'] == "True") {
                $detectWarning = DB::table('violence_warnings')
                    ->where('user_id', $comment->user_id)->first();
                $warning = 0;
                if (empty($detectWarning)) {
                    $warning++;
                } else {
                    $warning = $detectWarning->infringe++;
                }
                DB::table('violence_warnings')->updateOrInsert(
                    [
                        'user_id'=> $comment->user_id
                    ], [
                        'user_id'=> $comment->user_id,
                        'infringe' => $warning
                    ]);
            }
            $recordIndex = $comment->id;
        }
        DB::table('violence_logs')->updateOrInsert(
            ['id' => 1],
            ['comment_id' => $recordIndex]
        );
        Log::info("Detect comment " . $commentId . " to " . $recordIndex . " done!");
        return null;
    }
}
