@extends('installer.layout', ['currentStep' => 1, 'title' => 'Server Requirements Audit'])

@section('content')
<div class="space-y-6">
    @include('installer.partials.step1.header')
    @include('installer.partials.step1.banner')
    @include('installer.partials.step1.server-grid')
    @include('installer.partials.step1.extensions')
    @include('installer.partials.step1.functions-diagnostics')
    @include('installer.partials.step1.writable-paths')
    @include('installer.partials.step1.actions')
</div>
@endsection
