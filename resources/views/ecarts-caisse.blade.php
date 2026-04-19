@extends('layouts.backend')

@section('title', 'Écarts de Caisse')

@section('content')

    <div class="container-xxl flex-grow-1 container-p-y">

        <h4 class="fw-bold py-3 mb-4">
            <span class="text-muted fw-light">Caisse /</span> Gestion des Écarts
        </h4>

        <livewire:cash.ecart-caisse-dashboard />

    </div>

@endsection

@push('scripts')
    <script>
        // Initialize Bootstrap popovers for resolution notes
        document.addEventListener('livewire:navigated', () => {
            var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            popoverTriggerList.forEach(function (el) {
                new bootstrap.Popover(el);
            });
        });

        document.addEventListener('livewire:init', () => {
            Livewire.hook('morph.updated', () => {
                var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
                popoverTriggerList.forEach(function (el) {
                    new bootstrap.Popover(el);
                });
            });
        });
    </script>
@endpush
