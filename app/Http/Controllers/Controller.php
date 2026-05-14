<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function showFileLog($file, $ext)
    {
        $fullName = "{$file}.{$ext}";
        return showLogOnWeb($fullName);
    }

    public function listFileLog()
    {
        $logPath = storage_path('logs');
        $files = File::files($logPath);

        $logFiles = collect($files)->filter(function ($file) {
            return Str::endsWith($file->getFilename(), ['.log', '.txt']);
        })->map(function ($file) {
            return $file->getFilename();
        });

        return view('frontend.log.log', compact('logFiles'));
    }
}
