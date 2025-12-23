<h4 class="mb-3">Machine Status</h4>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-center border-success">
            <div class="card-body">
                <h6 class="text-success">ONLINE</h6>
                <h2>{{ $machineSummary['ONLINE'] }}</h2>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card text-center border-warning">
            <div class="card-body">
                <h6 class="text-warning">STALE</h6>
                <h2>{{ $machineSummary['STALE'] }}</h2>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card text-center border-danger">
            <div class="card-body">
                <h6 class="text-danger">OFFLINE</h6>
                <h2>{{ $machineSummary['OFFLINE'] }}</h2>
            </div>
        </div>
    </div>
</div>

<table class="table table-bordered">
    <thead>
        <tr>
            <th>Machine</th>
            <th>Department</th>
            <th>Status Runtime</th>
            <th>Last Seen</th>
        </tr>
    </thead>
    <tbody>
        @foreach($machines as $m)
        <tr>
            <td>{{ $m->code }}</td>
            <td>{{ $m->department_code }}</td>
            <td>
                @if ($m->runtime_status === 'ONLINE')
                    <span class="text-success fw-bold">ONLINE</span>
                @elseif ($m->runtime_status === 'STALE')
                    <span class="text-warning fw-bold">STALE</span>
                @else
                    <span class="text-danger fw-bold">OFFLINE</span>
                @endif
            </td>
            <td>{{ $m->last_seen_at }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
