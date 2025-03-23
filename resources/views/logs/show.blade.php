<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Viewer - {{ $filename }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding-top: 20px; }
        .log-entry { margin-bottom: 10px; padding: 10px; border-radius: 5px; }
        .log-debug { background-color: #d1ecf1; color: #0c5460; }
        .log-info { background-color: #d1ecf1; color: #0c5460; }
        .log-notice { background-color: #d1ecf1; color: #0c5460; }
        .log-warning { background-color: #fff3cd; color: #856404; }
        .log-error { background-color: #f8d7da; color: #721c24; }
        .log-critical { background-color: #f8d7da; color: #721c24; }
        .log-alert { background-color: #f8d7da; color: #721c24; }
        .log-emergency { background-color: #f8d7da; color: #721c24; }
        pre { white-space: pre-wrap; word-break: break-all; }
        .filter-controls { margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h2>Log File: {{ $filename }}</h2>
                        <div>
                            <a href="{{ route('logs.download', $filename) }}" class="btn btn-success me-2">Download</a>
                            <a href="{{ route('logs.index') }}" class="btn btn-primary">Back to Logs</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="filter-controls">
                            <div class="form-group">
                                <label>Filter by Level:</label>
                                <select id="levelFilter" class="form-select">
                                    <option value="all">All Levels</option>
                                    <option value="debug">Debug</option>
                                    <option value="info">Info</option>
                                    <option value="notice">Notice</option>
                                    <option value="warning">Warning</option>
                                    <option value="error">Error</option>
                                    <option value="critical">Critical</option>
                                    <option value="alert">Alert</option>
                                    <option value="emergency">Emergency</option>
                                </select>
                            </div>
                            <div class="form-group mt-2">
                                <label>Search in Logs:</label>
                                <input type="text" id="searchFilter" class="form-control" placeholder="Type to search...">
                            </div>
                        </div>

                        <div id="logEntries">
                            @forelse ($logs as $log)
                                <div class="log-entry log-{{ strtolower($log['level']) }}" data-level="{{ strtolower($log['level']) }}">
                                    <div class="d-flex justify-content-between">
                                        <strong>{{ $log['date'] }} [{{ $log['level'] }}]</strong>
                                        <button class="btn btn-sm btn-outline-secondary toggle-details">Details</button>
                                    </div>
                                    <div class="log-message">{{ $log['message'] }}</div>
                                    <div class="log-details" style="display: none;">
                                        <pre>{{ $log['full'] }}</pre>
                                    </div>
                                </div>
                            @empty
                                <div class="alert alert-info">No log entries found.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle log details
            document.querySelectorAll('.toggle-details').forEach(button => {
                button.addEventListener('click', function() {
                    const details = this.closest('.log-entry').querySelector('.log-details');
                    if (details.style.display === 'none') {
                        details.style.display = 'block';
                        this.textContent = 'Hide';
                    } else {
                        details.style.display = 'none';
                        this.textContent = 'Details';
                    }
                });
            });

            // Filter by level
            document.getElementById('levelFilter').addEventListener('change', function() {
                filterLogs();
            });

            // Search filter
            document.getElementById('searchFilter').addEventListener('input', function() {
                filterLogs();
            });

            function filterLogs() {
                const level = document.getElementById('levelFilter').value;
                const search = document.getElementById('searchFilter').value.toLowerCase();

                document.querySelectorAll('.log-entry').forEach(entry => {
                    const entryLevel = entry.getAttribute('data-level');
                    const entryText = entry.textContent.toLowerCase();
                    
                    const levelMatch = level === 'all' || entryLevel === level;
                    const searchMatch = search === '' || entryText.includes(search);
                    
                    entry.style.display = levelMatch && searchMatch ? 'block' : 'none';
                });
            }
        });
    </script>
</body>
</html> 