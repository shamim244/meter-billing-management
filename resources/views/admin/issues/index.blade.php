@extends('layouts.admin', ['title' => 'Bug Tracker & AI Desk'])

@section('content')
<div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto space-y-6">
    @include('admin.issues.partials.index.header')
    @include('admin.issues.partials.index.kpi-grid')
    @include('admin.issues.partials.index.filters')
    @include('admin.issues.partials.index.table')
</div>
@endsection
