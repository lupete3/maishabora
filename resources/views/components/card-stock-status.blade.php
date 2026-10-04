@props(['status' => null])
<span {{ $attributes->class(['badge', \App\Support\CardStockLabels::badge($status)]) }}>{{ \App\Support\CardStockLabels::status($status) }}</span>
