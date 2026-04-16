<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name'))</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: @yield('font-size', '10px');
            margin: 5px;
            color: #000;
        }

        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .text-start { text-align: left; }
        .bold { font-weight: bold; }
        .underline { text-decoration: underline; }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 10px;
        }

        .table td,
        .table th {
            border: 1px solid #000;
            padding: 4px;
        }

        th {
            background-color: #f1c206;
        }

        .footer {
            text-align: center;
            margin-top: 50px;
            font-size: 9px;
            color: #555;
        }

        .page-break {
            page-break-after: always;
        }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 8px;
            font-weight: bold;
            margin-top: 2px;
        }

        .badge-success { background: #28a745; color: #fff; }
        .badge-danger { background: #dc3545; color: #fff; }

        @yield('extra-style')
    </style>
</head>

<body>

    @section('header')
        @include('partials.pdf-header', [
            'reportTitle' => $__env->yieldContent('report-title')
        ])
    @show

    <div class="content">
        @yield('content')
    </div>

    @section('footer')
    <div class="footer">
        Rapport généré le {{ now()->format('d/m/Y H:i') }} - {{ config('app.name') }}
    </div>
    @show

</body>

</html>
