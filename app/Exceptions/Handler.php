<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // Ghi log chi tiết các lỗi
            Log::channel('daily')->error(
                $e->getMessage(),
                [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'previous' => $e->getPrevious() ? $e->getPrevious()->getMessage() : null,
                    'request' => request()->all(),
                    'url' => request()->fullUrl(),
                    'method' => request()->method(),
                ]
            );
        });
    }
} 