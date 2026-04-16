<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 5px;
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: @yield('font-size', '12px');
            margin: 0;
            padding: 5px;
            width: 100%;
            color: #000;
        }

        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .bold { font-weight: bold; }

        .header {
            text-align: center;
            margin-bottom: 5px;
        }

        .divider {
            border-top: 1px dashed black;
            margin: 5px 0;
        }

        .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
        }

        .footer {
            text-align: center;
            margin-top: 10px;
            font-size: 10px;
        }

        @yield('extra-style')
    </style>
</head>

<body>
    <div class="header">
        <div class="bold">{{ strtoupper(config('app.name')) }}</div>
        <div>{{ env('APP_ADRESS') }}</div>
        <div>Tel: {{ env('APP_PHONE') }}</div>
    </div>

    <div class="divider"></div>
    @hasSection('receipt-title')
        <div class="text-center bold">@yield('receipt-title')</div>
        <div class="divider"></div>
    @endif

    <div class="content">
        @yield('content')
    </div>

    @section('footer')
    <div class="divider"></div>
    <div class="footer">
        Merci de votre confiance.<br>
        Généré le {{ now()->format('d/m/Y H:i') }}
        @yield('footer-extra')
    </div>
    @show
</body>

</html>
