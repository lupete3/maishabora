{{--
    Cellule « Dernière mise » : date et heure du dernier encaissement sur le carnet
    (valeur actuelle) + ancienneté.
    Paramètres : $date (date de la dernière mise payée ou null),
                 $reference (date jusqu'à laquelle on compte les jours, en pratique aujourd'hui).
--}}
@php
    use App\Models\MembershipCardInventoryItem;

    $days = MembershipCardInventoryItem::daysBetween($date, $reference);
    $level = MembershipCardInventoryItem::levelForDays($days);
@endphp

@if ($date)
    <div class="text-nowrap">{{ \Illuminate\Support\Carbon::parse($date)->format('d/m/Y H:i') }}</div>
    <span class="badge bg-label-{{ $level }}">
        {{ $days === 0 ? 'le jour même' : 'il y a ' . $days . ' j' }}
    </span>
@else
    <span class="badge bg-label-danger">Aucune mise payée</span>
@endif
