<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Follow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{

    private $apiResponse;

    public function __construct() {
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
     */
    public function show(Request $request)
    {
        $myInfo = Auth::user();
        if (!$myInfo) {
            return $this->apiResponse->UnAuthorization();
        };

        // Get following
        $following = Follow::where('user_id', $myInfo->id)->count();
        // Get follower
        $follower = Follow::where('user_id', '<>', $myInfo->id)->where('follow_id', $myInfo->id)->count();
        // Unset unnecessary 
        unset(
            $myInfo['email_verified_at'], 
            $myInfo['location'], 
            $myInfo['city'], 
            $myInfo['online_status'], 
            $myInfo['status'], 
            $myInfo['login_fail']
        );
        $myInfo->following = $following;
        $myInfo->follower = $follower;
        return $this->apiResponse->success($myInfo);
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
