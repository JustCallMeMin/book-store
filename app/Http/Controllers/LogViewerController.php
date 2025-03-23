<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

class LogViewerController extends Controller
{
    /**
     * Hiển thị danh sách các file log
     */
    public function index()
    {
        $logs = File::files(storage_path('logs'));
        $logFiles = [];

        foreach ($logs as $log) {
            if (pathinfo($log, PATHINFO_EXTENSION) === 'log') {
                $logFiles[] = [
                    'name' => pathinfo($log, PATHINFO_BASENAME),
                    'size' => File::size($log),
                    'modified' => File::lastModified($log),
                ];
            }
        }

        return view('logs.index', compact('logFiles'));
    }

    /**
     * Hiển thị nội dung file log
     */
    public function show($filename)
    {
        $logPath = storage_path('logs/' . $filename);

        if (!File::exists($logPath)) {
            return redirect()->route('logs.index')->with('error', 'File log không tồn tại');
        }

        $content = File::get($logPath);
        $lines = explode("\n", $content);
        $logs = [];

        foreach ($lines as $line) {
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (\w+)\.(\w+): (.*)$/', $line, $matches)) {
                $logs[] = [
                    'date' => $matches[1],
                    'level' => $matches[3],
                    'message' => $matches[4],
                    'full' => $line
                ];
            } else {
                // Nếu không khớp với mẫu, thêm vào log trước đó nếu có
                if (!empty($logs)) {
                    $lastIndex = count($logs) - 1;
                    $logs[$lastIndex]['full'] .= "\n" . $line;
                }
            }
        }

        return view('logs.show', compact('logs', 'filename'));
    }

    /**
     * Tải xuống file log
     */
    public function download($filename)
    {
        $logPath = storage_path('logs/' . $filename);

        if (!File::exists($logPath)) {
            return redirect()->route('logs.index')->with('error', 'File log không tồn tại');
        }

        return Response::download($logPath);
    }

    /**
     * Xóa file log
     */
    public function destroy($filename)
    {
        $logPath = storage_path('logs/' . $filename);

        if (File::exists($logPath)) {
            File::delete($logPath);
            return redirect()->route('logs.index')->with('success', 'File log đã được xóa');
        }

        return redirect()->route('logs.index')->with('error', 'File log không tồn tại');
    }
} 