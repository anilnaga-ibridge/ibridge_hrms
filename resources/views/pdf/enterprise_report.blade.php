<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            font-size: 13px;
            line-height: 1.5;
        }
        .header {
            border-bottom: 2px solid {{ $company->letterhead_header_color ?? '#3b82f6' }};
            padding-bottom: 20px;
            margin-bottom: 25px;
        }
        .header table {
            width: 100%;
        }
        .logo {
            max-height: 50px;
        }
        .title {
            font-size: 22px;
            font-weight: bold;
            color: {{ $company->letterhead_header_color ?? '#3b82f6' }};
            margin: 0;
        }
        .company-name {
            font-size: 16px;
            font-weight: bold;
            margin-top: 5px;
            color: #555;
        }
        .meta-info {
            text-align: right;
            font-size: 11px;
            color: #777;
        }
        table.report-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        table.report-table th {
            background-color: {{ $company->letterhead_header_color ?? '#3b82f6' }};
            color: #ffffff;
            font-weight: bold;
            text-align: left;
            padding: 10px;
            font-size: 12px;
            border: 1px solid {{ $company->letterhead_header_color ?? '#3b82f6' }};
        }
        table.report-table td {
            padding: 10px;
            border: 1px solid #e5e7eb;
            font-size: 12px;
        }
        table.report-table tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 500;
        }
        .badge-success {
            background-color: #d1fae5;
            color: #065f46;
        }
        .badge-info {
            background-color: #dbeafe;
            color: #1e40af;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 10px;
            text-align: center;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }
    </style>
</head>
<body>

    <div class="header">
        <table>
            <tr>
                <td>
                    @if($company->light_logo_url)
                        <img src="{{ $company->light_logo_url }}" class="logo" alt="Logo" />
                    @endif
                    <div class="company-name">{{ $company->name }}</div>
                </td>
                <td class="meta-info">
                    <div class="title">{{ $title }}</div>
                    <div>Generated: {{ date('d M Y h:i A') }}</div>
                </td>
            </tr>
        </table>
    </div>

    @if($type === 'productivity')
        <table class="report-table">
            <thead>
                <tr>
                    <th>Employee Name</th>
                    <th>Department</th>
                    <th>Productivity Score</th>
                    <th>Total Tasks</th>
                    <th>Completed Tasks</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData as $row)
                    <tr>
                        <td><strong>{{ $row['name'] }}</strong></td>
                        <td>{{ $row['department'] }}</td>
                        <td>
                            <span class="badge badge-success">{{ $row['score'] }}%</span>
                        </td>
                        <td>{{ $row['total_tasks'] }}</td>
                        <td>{{ $row['completed_tasks'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if($type === 'progress')
        <table class="report-table">
            <thead>
                <tr>
                    <th>Project Name</th>
                    <th>Owner</th>
                    <th>Total Tasks</th>
                    <th>Completed Tasks</th>
                    <th>Progress</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData as $row)
                    <tr>
                        <td><strong>{{ $row['name'] }}</strong></td>
                        <td>{{ $row['owner'] }}</td>
                        <td>{{ $row['total_tasks'] }}</td>
                        <td>{{ $row['completed_tasks'] }}</td>
                        <td>
                            <span class="badge badge-info">{{ $row['progress'] }}%</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if($type === 'overdue')
        <table class="report-table">
            <thead>
                <tr>
                    <th>Task Number</th>
                    <th>Task Title</th>
                    <th>Project</th>
                    <th>Due Date</th>
                    <th>Assignees</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData as $row)
                    <tr>
                        <td><code>{{ $row['task_number'] }}</code></td>
                        <td><strong>{{ $row['title'] }}</strong></td>
                        <td>{{ $row['project'] }}</td>
                        <td style="color: #dc2626;">{{ $row['due_date'] }}</td>
                        <td>{{ $row['assignees'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if($type === 'timelog')
        <table class="report-table">
            <thead>
                <tr>
                    <th>Employee Name</th>
                    <th>Total Logged Hours</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData as $row)
                    <tr>
                        <td><strong>{{ $row['name'] }}</strong></td>
                        <td>{{ $row['total_hours'] }} hrs</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        {{ $company->name }} &copy; {{ date('Y') }}. All rights reserved.
    </div>

</body>
</html>
