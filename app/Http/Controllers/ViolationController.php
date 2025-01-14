<?php

namespace App\Http\Controllers;

use App\Jobs\DetectViolationJob;
use App\Models\Video;
use Illuminate\Http\Request;

class ViolationController extends Controller
{
    public function showForm()
    {
        return view('upload-video');
    }
    public function store(Request $request)
    {
        $request->validate([
            'video' => 'required|mimes:mp4',
        ]);

        $path = $request->file('video')->store('videos', 'public');
        $video = Video::create([
            'source_path' => $path,
        ]);
        dispatch(new DetectViolationJob($video));

        return $this->status($video);
    }
    public function status(Video $video)
    {
        $video->load('violations');

        return response()->json(['video' => $video]);
    }
}
