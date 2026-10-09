@extends('layouts.user-panel')

@section('header', 'My Bug Reports & Support Tickets')

@section('content')
<div x-data="userIssuesTracker(window.userIssuesConfig)" class="space-y-6">
    @include('user-panel.issues.partials.header')
    @include('user-panel.issues.partials.lookup-card')
    @include('user-panel.issues.partials.metrics-cards')
    @include('user-panel.issues.partials.filters-bar')
    @include('user-panel.issues.partials.tickets-table')
    @include('user-panel.issues.partials.details-modal')
    @include('user-panel.issues.partials.toast')
</div>

<script>
    window.userIssuesConfig = {
        trackUrl: '{{ url('/issues/track') }}'
    };
</script>
<script src="{{ asset('js/user-panel/issues-tracker-app.js') }}?v={{ file_exists(public_path('js/user-panel/issues-tracker-app.js')) ? filemtime(public_path('js/user-panel/issues-tracker-app.js')) : time() }}"></script>
@endsection
