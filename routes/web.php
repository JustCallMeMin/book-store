<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

Route::get('/', function () {
    return view('welcome');
});

// Log Viewer - Chỉ hiển thị trong môi trường phát triển
if (app()->environment('local')) {
    Route::get('logs', [\App\Http\Controllers\LogViewerController::class, 'index'])->name('logs.index');
    Route::get('logs/{filename}', [\App\Http\Controllers\LogViewerController::class, 'show'])->name('logs.show');
    Route::get('logs/{filename}/download', [\App\Http\Controllers\LogViewerController::class, 'download'])->name('logs.download');
    Route::delete('logs/{filename}', [\App\Http\Controllers\LogViewerController::class, 'destroy'])->name('logs.destroy');
}

// Queue monitoring routes - protected by admin role
Route::middleware(['auth', \App\Http\Middleware\CheckRole::class.':admin'])->prefix('admin')->group(function () {
    Route::get('/queue-jobs', function () {
        $jobs = DB::table('jobs')->paginate(20);
        $failedJobs = DB::table('failed_jobs')->paginate(20);
        $batches = DB::table('job_batches')->paginate(20);
        
        return view('admin.queue-jobs', compact('jobs', 'failedJobs', 'batches'));
    })->name('admin.queue-jobs');
    
    // Retry failed job
    Route::post('/queue/retry/{id}', function ($id) {
        Artisan::call('queue:retry', ['id' => $id]);
        return back()->with('success', 'Job queued for retry');
    })->name('horizon.retry-jobs');
    
    // Retry all failed jobs
    Route::post('/queue/retry-all', function () {
        Artisan::call('queue:retry', ['id' => 'all']);
        return back()->with('success', 'All failed jobs queued for retry');
    })->name('horizon.retry-all-jobs');
    
    // Forget (delete) a failed job
    Route::delete('/queue/forget/{id}', function ($id) {
        Artisan::call('queue:forget', ['id' => $id]);
        return back()->with('success', 'Failed job deleted');
    })->name('horizon.forget-failed-jobs');
    
    // Forget (delete) all failed jobs
    Route::delete('/queue/forget-all', function () {
        Artisan::call('queue:flush');
        return back()->with('success', 'All failed jobs deleted');
    })->name('horizon.forget-all-failed-jobs');
    
    // Import all books - shortcut route
    Route::get('/gutendex/import-all', function () {
        return redirect()->route('api.gutendex.import-all-books');
    })->name('admin.gutendex.import-all');
});
