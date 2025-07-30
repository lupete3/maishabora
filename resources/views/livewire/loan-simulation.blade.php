<div class="p-4 space-y-4">

    <!-- Formulaire de simulation -->
    <div id="simulation-form">
        <h2 class="text-xl font-bold">Simulation de Crédit</h2>
        <div class="flex space-x-4">
            <div>
                <label>Montant du prêt :</label>
                <input type="number" wire:model="amount" class="border rounded p-2">
            </div>
            <div>
                <label>Taux d’intérêt (%):</label>
                <input type="number" step="0.1" wire:model="rate" class="border rounded p-2">
            </div>
            <div>
                <label>Nombre d'échéances :</label>
                <input type="number" wire:model="installments" class="border rounded p-2">
            </div>
        </div>
        <div class="mt-2">
            <button wire:click="simulate" type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                <span wire:loading class="spinner-border spinner-border-sm me-2" role="status"></span>
                    Simuler
            </button>
        </div>
    </div>

    @if($schedule)
    <!-- Zone à imprimer -->
    <div id="print-section" class="bg-white p-4 rounded shadow">
        <div class="flex justify-between items-center mt-4">
            <div>
                <h2 class="text-xl font-bold text-center">PLAN DE REMBOURSEMENT DE CRÉDIT</h2>
                <p><strong>MAISHA BORA</strong></p>
            </div>
            <div class="flex justify-between items-center mt-4">
                <div class="space-x-2">
                    <button wire:click="exportToPdf" class="btn-success text-white rounded p-2">📄 Exporter en PDF</button>
                </div>
            </div>
        </div>

        <table class="w-full mt-4">
            <tr>
                <td>
                    <strong>Code Membre :</strong> IMF111000<br>
                    <strong>Nom Complet :</strong> MATATA KODI Jules<br>
                    <strong>Email :</strong> matatkodi@amb.com
                </td>
                <td>
                    <strong>Montant du prêt :</strong> {{ number_format($amount, 2) }}<br>
                    <strong>Taux d'intérêt :</strong> {{ number_format($rate, 2) }}%<br>
                    <strong>Type de remboursement :</strong> Mensuel<br>
                    <strong>Date d'impression :</strong> {{ now()->format('d/m/Y H:i') }}
                </td>
            </tr>
        </table>

        <table class="min-w-full mt-4 border-collapse border border-gray-300">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border p-2">#</th>
                    <th class="border p-2">Capital Début</th>
                    <th class="border p-2">Capital Remboursé</th>
                    <th class="border p-2">Intérêt</th>
                    <th class="border p-2">Mensualité</th>
                    <th class="border p-2">Capital Restant</th>
                </tr>
            </thead>
            <tbody>
                @foreach($schedule as $line)
                <tr>
                    <td class="border p-2 text-center">{{ $line['no'] }}</td>
                    <td class="border p-2 text-right">{{ number_format($line['opening_capital'], 2) }}</td>
                    <td class="border p-2 text-right">{{ number_format($line['capital_repaid'], 2) }}</td>
                    <td class="border p-2 text-right">{{ number_format($line['interest'], 2) }}</td>
                    <td class="border p-2 text-right">{{ number_format($line['due'], 2) }}</td>
                    <td class="border p-2 text-right">{{ number_format($line['remaining_capital'], 2) }}</td>
                </tr>
                @endforeach
                <tr class="bg-gray-100 font-bold">
                    <td class="border p-2 text-center">Totaux</td>
                    <td class="border p-2 text-right">-</td>
                    <td class="border p-2 text-right">{{ number_format(collect($schedule)->sum('capital_repaid'), 2) }}</td>
                    <td class="border p-2 text-right">{{ number_format(collect($schedule)->sum('interest'), 2) }}</td>
                    <td class="border p-2 text-right">{{ number_format(collect($schedule)->sum('due'), 2) }}</td>
                    <td class="border p-2 text-right">-</td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif

    <!-- SCRIPT pour imprimer uniquement le bloc -->
<script>
    function printSection() {
        const section = document.getElementById("print-section").innerHTML;
        const original = document.body.innerHTML;

        document.body.innerHTML = section;
        window.print();
        document.body.innerHTML = original;
        window.location.reload(); // pour recharger Livewire
    }
</script>
</div>

