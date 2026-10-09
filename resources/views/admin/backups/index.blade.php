@extends('layouts.admin')

@section('content')
<div class="space-y-6" x-data="adminBackupApp()">
    @include('admin.backups.partials.header')
    @include('admin.backups.partials.alerts')
    @include('admin.backups.partials.storage-metrics')
    @include('admin.backups.partials.generators-console')
    @include('admin.backups.partials.archives-table')
    @include('admin.backups.partials.modal-manifest')
</div>

<!-- App Script -->
<script src="{{ asset('js/admin/backups/backup-manager-app.js') }}?v={{ file_exists(public_path('js/admin/backups/backup-manager-app.js')) ? filemtime(public_path('js/admin/backups/backup-manager-app.js')) : time() }}"></script>
@endsection
