@extends('layouts.backend')

@section('title', 'Sessions utilisateurs')

@section('content')

@can('afficher-logs', App\Models\User::class)
    <livewire:admin.user-sessions />
@endcan

@endsection
