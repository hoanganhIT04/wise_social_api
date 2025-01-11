<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Follow;
use App\Models\Friend;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{

    private $apiResponse;

    public function __construct()
    {
        $this->apiResponse = new ApiResponse();
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * 
     */
    public function show(Request $request)
    {
        // Get authenticated user ID
        $userId = Auth::id();
        // Return unauthorized response if no user is authenticated
        if (!$userId) {
            return $this->apiResponse->UnAuthorization();
        };

        // Get user with follower and follows relationships, selecting specific fields
        $user = User::with('follower', 'follows')
            ->select('id', 'name', 'email', 'avatar', 'overview')
            ->where('id', $userId)->first();

        // Count followers and following
        $user->followers = count($user->follower);
        $user->following = count($user->follows);
        // Remove relationship data after counting
        unset($user->follower, $user->follows);

        // Initialize avatar folder variable
        $folderAvatar = null;
        // If user has avatar, generate full avatar URL
        if (!is_null($user->avatar)) {
            $folderAvatar = explode('@', $user->email);
            $user->avatar = url('avatars/' . $folderAvatar[0] . '/' . $user->avatar);
        }

        // Return success response with user data
        return $this->apiResponse->success($user);
    }

    /**
     * Controller for suggesting friends
     * @param \Illuminate\Http\Request $request
     * @return bool|string
     */
    public function suggestFriend(Request $request)
    {
        // Get authenticated user ID
        $userId = Auth::id();

        // Get array of friend IDs from friends table for current user
        $listFriendIds = DB::table('friends')
            ->where('user_id', $userId)
            ->select('friend_id')
            ->pluck('friend_id')
            ->toArray();

        // Add current user ID to exclude from suggestions
        $listFriendIds[] = $userId;

        // Query users table for suggested friends
        $suggest = User::with([
            // Load experiences relationship with selected fields
            'experiences' => function ($experienceQuery) {
                return $experienceQuery->select('id', 'user_id', 'title');
            }
        ])
            ->whereNotIn('id', $listFriendIds) // Exclude current friends and self
            ->where('status', User::STATUS_ACTIVE) // Only active users
            ->select('id', 'name', 'avatar', 'created_at') // Select needed fields
            ->orderBy('created_at', 'ASC') // Sort by join date
            ->limit(config('constant.limit')) // Limit results
            ->get();

        // Process each suggested user if any found
        if (count($suggest) > 0) {
            foreach ($suggest as $user) {
                // Initialize avatar folder variable
                $folderAvatar = null;

                // Generate full avatar URL if user has avatar
                if (!is_null($user->avatar)) {
                    $folderAvatar = explode('@', $user->email);
                    $user->avatar = url('avatars/' . $folderAvatar[0] . '/' . $user->avatar);
                }

                // Initialize experience text and counter
                $txtExperience = '';
                $i = 1;

                // Build comma-separated list of experience titles
                foreach ($user->experiences as $experience) {
                    if ($i < count($user->experiences)) {
                        $txtExperience .= $experience->title . ', ';
                    } else {
                        $txtExperience .= $experience->title;
                    }
                    $i++;
                }

                // Replace experiences array with formatted string
                $user->experience = $this->truncateString($txtExperience, 20);
                unset($user->experiences);
            }
        }

        // Return success response with suggested friends
        return $this->apiResponse->success($suggest);
    }
    // Function to truncate if title is too long
    private function truncateString($string, $length, $append = '...')
    {
        if (mb_strlen($string) > $length) {
            return mb_substr($string, 0, $length) . $append;
        }
        return $string;
    }

    /**
     * Controller for Add Friend function
     * @param \Illuminate\Http\Request $request
     * @return bool|string
     */
    public function addFriend(Request $request)
    {
        // Get all request parameters
        $param = $request->all();
        
        try {
            // Start database transaction
            DB::beginTransaction();
            
            // Create new friend request record
            $friend = new Friend();
            $friend->user_id = Auth::id(); // Current authenticated user
            $friend->friend_id = $param['friend_id']; // Target friend ID
            $friend->approved = Friend::UNAPPROVED; // Set initial unapproved status
            $friend->created_at = Carbon::now();
            $friend->save();
            
            // Commit transaction if successful
            DB::commit();

            return $this->apiResponse->success();
        } catch (\Exception $e) {
            // Rollback transaction and log error if failed
            DB::rollback();
            Log::error($e->getMessage());

            return $this->apiResponse->InternalServerError();
        }
    }

    public function listFriendRequest(Request $request)
    {
        // Get authenticated user ID
        $userId = Auth::id();

        // Query users table for suggested friends
        $requests = User::with([
            // Load experiences relationship with selected fields
            'experiences' => function ($experienceQuery) {
                return $experienceQuery->select('id', 'user_id', 'title');
            }
        ])
            ->join('friends', 'users.id', 'friends.friend_id')
            ->where('friend_id', $userId) // Exclude current friends and self
            ->where('status', User::STATUS_ACTIVE) // Only active users
            ->select('friends.id', 'users.name', 'users.avatar', 'users.created_at') // Select needed fields
            ->orderBy('friends.created_at', 'ASC') // Sort by join date
            ->limit(config('constant.limit')) // Limit results
            ->get();

        // dd($requests);

        // Process each suggested user if any found
        if (count($requests) > 0) {
            foreach ($requests as $user) {
                // Initialize avatar folder variable
                $folderAvatar = null;

                // Generate full avatar URL if user has avatar
                if (!is_null($user->avatar)) {
                    $folderAvatar = explode('@', $user->email);
                    $user->avatar = url('avatars/' . $folderAvatar[0] . '/' . $user->avatar);
                }

                // Initialize experience text and counter
                $txtExperience = '';
                $i = 1;

                // Build comma-separated list of experience titles
                foreach ($user->experiences as $experience) {
                    if ($i < count($user->experiences)) {
                        $txtExperience .= $experience->title . ', ';
                    } else {
                        $txtExperience .= $experience->title;
                    }
                    $i++;
                }

                // Replace experiences array with formatted string
                $user->experience = $this->truncateString($txtExperience, 20);
                unset($user->experiences);
            }
        }

        // Return success response with suggested friends
        return $this->apiResponse->success($requests);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
