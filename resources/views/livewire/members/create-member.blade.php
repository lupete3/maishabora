
@extends('layouts.backend')

@section('title', 'Adhesion du membre')

@section('content')

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                <h2 class="text-2xl font-bold mb-4">Vendre un carnet d'adhésion</h2>

                @if (session()->has('message'))
                    <div class="alert alert-success">{{ session('message') }}</div>
                @endif

                <form wire:submit.prevent="sellCard">
                    <div class="mb-3">
                        <label for="user_id" class="form-label">Choisir un membre</label>
                        <select wire:model="user_id" id="user_id" class="form-select">
                            <option value="">-- Sélectionner --</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} {{ $user->postnom }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">Vendre le carnet</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
