<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>


<body>
    <div class="container mt-4">
        <h1 class="text-center mb-4">Report</h1>
        <form method="GET" action="{{ route('report.index') }}" class="row g-3">
            @csrf
            <div class="col-md-4">
                <label for="user_id" class="form-label">User <span class="text-danger">*</span></label>
                <select name="user_id" id="user_id" class="form-select" required>
                    <option value="" disabled selected>Choose a user</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" {{request('user_id') == $user->id ? "selected" : ''}}>#{{ $user->id}}
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="date_from" class="form-label">Date From</label>
                <input type="date" name="date_from" id="date_from" class="form-control" max="{{ date('Y-m-d') }}"
                    value="{{ now()->format('Y-m-d') }}">
            </div>
            <div class="col-md-3">
                <label for="date_to" class="form-label">Date To</label>
                <input type="date" name="date_to" id="date_to" class="form-control" max="{{ date('Y-m-d') }}">
            </div>
            <div class="col-md-2 align-self-end">
                <button type="submit" class="btn btn-primary w-100">Generate Report</button>
            </div>
        </form>

        @if (!empty($results))

            <h2 class="text-center mt-5">Results <small>{{$date_from}} - {{$date_to}}</small></h2>
            <table class="table table-bordered mt-3">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>User</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Difference</th>
                        <th>Images Count</th>
                        <th>Objects Count</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($results as $row)
                        <tr>
                            <td>{{ $row->date }}</td>
                            <td>{{ $row->name }}</td>
                            <td>{{ $row->start_time }}</td>
                            <td>{{ $row->end_time }}</td>
                            <td>{{ $row->diff }}</td>
                            <td>{{ $row->images_count }}</td>
                            <td>{{ $row->objects_count }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5">Total count</td>
                        <td>{{ $results->sum('images_count') }}</td>
                        <td>{{ $results->sum('objects_count') }}</td>
                    </tr>

                    <tr>
                        <td colspan="6">Total money</td>
                        <td>{{ number_format($results->sum('objects_count') / env('ANNOTATION_COUNT', 12) * 1000, 0, "", " ")}}
                            UZS
                        </td>
                    </tr>
                </tfoot>
            </table>

        @endif
    </div>
</body>

</html>