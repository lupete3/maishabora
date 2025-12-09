@extends('layouts.backend')

@section('title', 'Tableaude des loga')

@section('content')

@can('afficher-logs', App\Models\User::class)
    <livewire:admin.system-logs />
@endcan


@endsection
