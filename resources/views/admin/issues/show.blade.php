@extends('layouts.admin', ['title' => 'Issue ' . $issue->issue_code . ' — Bug Tracker'])

@section('content')
<div x-data="{ copied: false }" class="p-4 sm:p-6 lg:p-8 max-w-6xl mx-auto space-y-6">
    @include('admin.issues.partials.show.breadcrumb')
    @include('admin.issues.partials.show.title-card')
    @include('admin.issues.partials.show.ai-cockpit')
    @include('admin.issues.partials.show.context-cards')
    @include('admin.issues.partials.show.resolve-modal')
</div>
@endsection
