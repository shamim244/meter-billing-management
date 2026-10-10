<x-admin-layout>
    <x-slot name="header">
        Edit User Profile & Credentials — {{ $user->name }}
    </x-slot>

    <div class="max-w-4xl mx-auto space-y-6">
        @include('admin.users.partials.edit.header')
        @include('admin.users.partials.edit.profile-form')
        @include('admin.users.partials.edit.password-form')
    </div>
</x-admin-layout>
