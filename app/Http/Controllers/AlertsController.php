<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Alert;
use App\Post;
use App\Vote;
use App\Thread;

class AlertsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }


    public function index($code, Request $request, Alert $alert, Post $post, Vote $vote, Thread $thread)
    {
        $user = $request->user();

        $alert = $alert->where('code', $code)->first();
        if (!$alert) {
            flash("This alert does not exist", 'danger');
            return Redirect('/');
        }
        if ($alert->user_id !== $user->id) {
            return Redirect('/');
        }

        $parent_comment = $post->where('id', $alert->post_id)->first();
        $reply = $post->where('id', $alert->reply_post_id)->first();
        $thread = $thread->select('id', 'code', 'title')->where('id', $parent_comment->thread_id)->first();
        $user_reply = $post->where('parent_id', $reply->id)->where('user_id', $user->id)->first();

        if (!$parent_comment || !$reply || !$thread) {
            return Redirect('/');
        }

        if ($user_reply) {
            $voted = $vote->orWhere('post_id', $parent_comment->id)->orWhere('post_id', $reply->id)->orWhere('post_id', $user_reply->id)->get();
        } else {
            $voted = $vote->orWhere('post_id', $parent_comment->id)->orWhere('post_id', $reply->id)->get();
        }

        $alert->active = false;
        $alert->save();

        return view('alert')->with([
            'parent' => $parent_comment,
            'reply' => $reply,
            'thread' => $thread,
            'user_reply' => $user_reply,
            'voted' => $voted
        ]);
    }
}