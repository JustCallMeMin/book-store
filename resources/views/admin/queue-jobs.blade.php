@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">Queue Monitoring</h1>
    
    <div class="mb-4">
        <h2 class="h4">Active Jobs</h2>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Queue</th>
                                <th>Attempts</th>
                                <th>Created At</th>
                                <th>Available At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($jobs as $job)
                                <tr>
                                    <td>{{ $job->id }}</td>
                                    <td>{{ $job->queue }}</td>
                                    <td>{{ $job->attempts }}</td>
                                    <td>{{ date('Y-m-d H:i:s', $job->created_at) }}</td>
                                    <td>{{ date('Y-m-d H:i:s', $job->available_at) }}</td>
                                </tr>
                            @endforeach
                            
                            @if (count($jobs) === 0)
                                <tr>
                                    <td colspan="5" class="text-center">No active jobs</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3">
                    {{ $jobs->links() }}
                </div>
            </div>
        </div>
    </div>
    
    <div class="mb-4">
        <h2 class="h4">Job Batches</h2>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Total Jobs</th>
                                <th>Pending Jobs</th>
                                <th>Failed Jobs</th>
                                <th>Created At</th>
                                <th>Finished At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($batches as $batch)
                                <tr>
                                    <td>{{ $batch->id }}</td>
                                    <td>{{ $batch->name }}</td>
                                    <td>{{ $batch->total_jobs }}</td>
                                    <td>{{ $batch->pending_jobs }}</td>
                                    <td>{{ $batch->failed_jobs }}</td>
                                    <td>{{ date('Y-m-d H:i:s', $batch->created_at) }}</td>
                                    <td>{{ $batch->finished_at ? date('Y-m-d H:i:s', $batch->finished_at) : 'In Progress' }}</td>
                                </tr>
                            @endforeach
                            
                            @if (count($batches) === 0)
                                <tr>
                                    <td colspan="7" class="text-center">No job batches</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3">
                    {{ $batches->links() }}
                </div>
            </div>
        </div>
    </div>
    
    <div class="mb-4">
        <h2 class="h4">Failed Jobs</h2>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>UUID</th>
                                <th>Connection</th>
                                <th>Queue</th>
                                <th>Failed At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($failedJobs as $failedJob)
                                <tr>
                                    <td>{{ $failedJob->id }}</td>
                                    <td>{{ $failedJob->uuid }}</td>
                                    <td>{{ $failedJob->connection }}</td>
                                    <td>{{ $failedJob->queue }}</td>
                                    <td>{{ $failedJob->failed_at }}</td>
                                    <td>
                                        <form action="{{ route('horizon.retry-jobs', ['id' => $failedJob->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to retry this job?')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">Retry</button>
                                        </form>
                                        <form action="{{ route('horizon.forget-failed-jobs', ['id' => $failedJob->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this failed job?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                            
                            @if (count($failedJobs) === 0)
                                <tr>
                                    <td colspan="6" class="text-center">No failed jobs</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3">
                    {{ $failedJobs->links() }}
                </div>
            </div>
        </div>
    </div>
    
    <div class="mb-4">
        <h2 class="h4">Queue Management</h2>
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <a href="{{ route('admin.gutendex.import-all') }}" class="btn btn-primary btn-block">Import All Books (Queue)</a>
                    </div>
                    <div class="col-md-4 mb-3">
                        <form action="{{ route('horizon.retry-all-jobs') }}" method="POST" onsubmit="return confirm('Are you sure you want to retry all failed jobs?')">
                            @csrf
                            <button type="submit" class="btn btn-success btn-block">Retry All Failed Jobs</button>
                        </form>
                    </div>
                    <div class="col-md-4 mb-3">
                        <form action="{{ route('horizon.forget-all-failed-jobs') }}" method="POST" onsubmit="return confirm('Are you sure you want to delete all failed jobs?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-block">Clear All Failed Jobs</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 