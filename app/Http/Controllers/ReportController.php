<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function like(Report $report)
    {
      if(!auth()->user()) abort(401);
      if(empty($report)) abort(404);
      if(empty(auth()->user())) abort(403);
      
      auth()->user()->toggleUpVote($report);

      $like = auth()->user()->hasUpVoted($report) ? true : false;

      return response()->json([
        'success' => true,
        'message' => $like ? 'Comment has liked' : 'Comment has unliked',
        'report' => $report,
      ]);
    }

    public function dislike(Report $report)
    {
      if(!auth()->user()) abort(401);
      if(empty($report)) abort(404);
      if(empty(auth()->user())) abort(403);
      
      auth()->user()->toggleDownVote($report);

      $dislike = auth()->user()->hasDownVoted($report) ? true : false;

      return response()->json([
        'success' => true,
        'message' => $dislike ? 'Comment has disliked' : 'Comment has undisliked',
        'report' => $report,
      ]);
    }
}
