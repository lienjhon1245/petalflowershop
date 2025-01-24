<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Reports</title>
    <link rel="stylesheet" href="{{ asset('css/manager/sales-reports.css') }}">
</head>
<body>
    <div class="container">
        <h1>Sales Reports</h1>
        <ul>
            <li><a href="{{ route('manager.sales-reports.view') }}">View Sales Reports</a></li>
            <li><a href="{{ route('manager.sales-reports.print') }}">Print Sales Reports</a></li>
            <li><a href="{{ route('manager.sales-reports.delete') }}">Delete Sales Reports</a></li>
        </ul>
    </div>
</body>
</html>
