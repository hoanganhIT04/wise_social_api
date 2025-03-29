<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\User;
use App\Models\Like;
use App\Models\Comment;
use App\Models\Experience;
use App\Models\Skill;
use Illuminate\Support\Facades\Auth;

class TimelineController extends Controller
{
    private $apiResponse;

    public function __construct()
    {
        $this->apiResponse = new ApiResponse();
    }

    /**
     * Display posts on timeline
     * Order by timeline_orders and id, DESC
     * @param \Illuminate\Http\Request $request
     * @return string|mixed String JSON for REST API
     */
    public function timeline(Request $request)
    {
        $param = $request->all();

        // Fetch posts with related author information.
        $posts = Post::with(
            [
                // Load author information (id, name, avatar).
                'author' => function ($authorQuery) {
                    return $authorQuery->select('id', 'name', 'avatar');
                },
                // Load the author's experience.
                'author.experiences' => function ($authorExpQuery) {
                    return $authorExpQuery->orderBy('id', 'DESC')->get();
                },
                // Load author's skills
                'author.skills' => function ($authorSkillQuery) {
                    return $authorSkillQuery->select('id', 'skill');
                },
                // Load likes and comments
                'likes',
                'comments'
            ]
        )
            // Select the necessary columns from the posts table.
            ->select(
                'posts.id',
                'posts.content',
                'posts.timeline_orders',
                'posts.view_count',
                'posts.images',
                'posts.user_id',
                // 'users.id as author_id',
                // 'users.name as author_name',
                // 'users.avatar as author_avatar',
                // 'users.location'

            )
            // Order the posts by timeline_orders (descending) and then by id (descending).
            ->orderBy('posts.timeline_orders', 'DESC')
            ->orderBy('posts.id', 'DESC')
            // Apply pagination using offset and limit.
            ->skip($param['offset'])
            ->take($param['limit'])
            // Retrieve the posts.
            ->get();

        // Return HTTP 204 if no data found
        if (empty($posts)) {
            return $this->apiResponse->dataNotfound();
        }

        // Loop through each post.
        foreach ($posts as $post) {
            // Assign the title of the author's first experience to the post.
            // If the author has no experiences, assign an empty string.
            $post->experiences = $post->author->experiences[0]->title ?? "";
            // Assign the author's skills to the post.
            $post->skills = $post->author->skills;
            // Count the number of likes and comments for the current post.
            $post->total_like = count($post->likes);
            $post->total_comment = count(value: $post->comments);

            // Initialize a flag to indicate whether the current user has liked the post.
            $isLike = Post::UN_LIKE;
            // Check if the post has any likes.
            if (count($post->likes) > 0) {
                foreach ($post->likes as $like) {
                    // Check if the currently authenticated user's ID matches the user ID of the like.
                    if (Auth::user()->id == $like->user_id) {
                        // If the user has liked the post, set the flag to 1.
                        $isLike = Post::LIKE;
                        break;
                    }
                }
            }
            // Assign the value of $isLike to is_like attributes of post object
            $post->is_like = $isLike;

            // Truncate the post content to a maximum of 100 characters for display purposes.
            $shortContent = "";
            if (strlen($post->content) > 100) {
                $shortContent = mb_substr(
                    $post->content,
                    0,
                    100,
                    "UTF-8"
                );
            }
            $post->short_content = $shortContent;

            // Unset the original instances from the author object to clean up the response.
            unset(
                $post->author->experiences,
                $post->author->skills,
                $post->likes,
                $post->comments
            );
        }

        // Return the posts in a successful API response.
        return $this->apiResponse->success($posts);
    }
}
