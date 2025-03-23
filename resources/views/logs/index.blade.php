<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Viewer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding-top: 20px; }
        .log-table { margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h2>Log Files</h2>
                        <a href="{{ url('/') }}" class="btn btn-primary">Back to Home</a>
                    </div>
                    <div class="card-body">
                        @if (session('success'))
                            <div class="alert alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger">
                                {{ session('error') }}
                            </div>
                        @endif

                        <div class="table-responsive log-table">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>File Name</th>
                                        <th>Size</th>
                                        <th>Last Modified</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($logFiles as $log)
                                        <tr>
                                            <td>{{ $log['name'] }}</td>
                                            <td>{{ round($log['size'] / 1024, 2) }} KB</td>
                                            <td>{{ date('Y-m-d H:i:s', $log['modified']) }}</td>
                                            <td class="d-flex">
                                                <a href="{{ route('logs.show', $log['name']) }}" class="btn btn-sm btn-info me-2">View</a>
                                                <a href="{{ route('logs.download', $log['name']) }}" class="btn btn-sm btn-success me-2">Download</a>
                                                <form action="{{ route('logs.destroy', $log['name']) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this log file?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center">No log files found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 