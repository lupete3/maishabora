@extends('layouts.backend')

@section('title', 'Resume Transactions AI')

@section('content')

<div class="container-xxl flex-grow-1 container-p-y">
    <livewire:reports.ai-transaction-summary />
</div>  

@endsection
