<div class="header">
    <table class="header-table" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="width: 15%;">
                @php
                    $logoPath = public_path('logo.jpg');
                    $logoData = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : '';
                @endphp
                @if($logoData)
                    <img src="data:image/png;base64,{{ $logoData }}" style="width: 80px;" alt="Logo">
                @endif
            </td>
            <td style="width: 60%; text-align:center;">
                <h2 style="margin: 0; font-size: 14px;">{{ strtoupper(config('app.name')) }}</h2>
                <p style="margin: 0;">Adresse : {{ env('APP_ADRESS') }}</p>
                <p style="margin: 0;">Tel : {{ env('APP_PHONE') }} – Email : {{ env('APP_EMAIL') }}</p>
                @if(env('APP_RCCM'))
                    <p style="margin: 0;">RCCM : {{ env('APP_RCCM') }}</p>
                @endif
            </td>
            <td style="width: 25%; text-align: right; font-size: 9px;">
                @if(isset($metadata))
                    {!! $metadata !!}
                @else
                    <strong>Date :</strong> {{ isset($date) ? $date : now()->format('d/m/Y') }}<br>
                    <strong>Heure :</strong> {{ isset($heure) ? $heure : now()->format('H:i') }}<br>
                    <strong>Généré par :</strong><br>
                    {{ $agent_name ?? (Auth::user()->name ?? 'Système') . ' ' . (Auth::user()->postnom ?? '') }}
                @endif
            </td>
        </tr>
    </table>
    <hr style="margin: 10px 0; border: 0; border-bottom: 2px solid #ed8d0f;">
    @if(isset($reportTitle))
        <h3 style="text-align: center; text-decoration: underline; margin-bottom: 10px; text-transform: uppercase; font-size: 12px;">{{ $reportTitle }}</h3>
    @endif
</div>
