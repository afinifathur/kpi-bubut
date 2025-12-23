@extends('layouts.app')

@section('content')

{{-- ===== LEGACY DASHBOARD ===== --}}
@include('dashboard._legacy_dashboard')

<hr class="my-4">

{{-- ===== MACHINE STATUS DASHBOARD ===== --}}
@include('dashboard._machine_status')

@endsection
