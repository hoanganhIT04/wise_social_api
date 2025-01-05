<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Follow;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
                $user->experience = $txtExperience;
                unset($user->experiences);
            }
        }

        // Return success response with suggested friends
        return $this->apiResponse->success($suggest);
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
