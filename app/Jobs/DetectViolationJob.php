<?php

namespace App\Jobs;

use App\Models\Video;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Log;

class DetectViolationJob implements ShouldQueue
{

    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected Video $video)
    {

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $command = config('app.violation_detector_command');
        try {
            $this->video->status = 'in_progress';
            $this->video->started_at = now();
            $this->video->save();
            $path = public_path('output');
            $video_path = storage_path("app/public/{$this->video->source_path}");
            $result = shell_exec("$command  $video_path --output-dir={$path} --json_file_name={$this->video->id}");
            \Log::info($result);
            $jsonResult = json_decode(file_get_contents("$path/{$this->video->id}.json"), true);

            if (!$jsonResult)
                $this->video->status = 'violation_not_detected';
            else {
                $this->video->violations()->createMany($jsonResult);
                $this->video->status = "violation_found";
            }


        } catch (\Throwable $th) {
            $this->video->status = 'error';
            Log::error($th);
        }
        $this->video->save();
    }
}
