@extends('installer.layout', ['currentStep' => 2, 'title' => 'Database Configuration'])

@section('content')
<div x-data="{
    driver: 'mysql',
    host: '{{ old('host', $defaultHost) }}',
    port: '{{ old('port', $defaultPort) }}',
    database: '{{ old('database', $defaultDatabase) }}',
    username: '{{ old('username', $defaultUsername) }}',
    password: '{{ old('password') }}',
    testing: false,
    testSuccess: null,
    testMessage: '',

    testConnection() {
        this.testing = true;
        this.testSuccess = null;
        this.testMessage = '';

        fetch('{{ route('install.test_db') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
            },
            body: JSON.stringify({
                driver: this.driver,
                host: this.host,
                port: this.port,
                database: this.database,
                username: this.username,
                password: this.password
            })
        })
        .then(res => res.json())
        .then(data => {
            this.testing = false;
            this.testSuccess = data.success;
            this.testMessage = data.message;
        })
        .catch(err => {
            this.testing = false;
            this.testSuccess = false;
            this.testMessage = 'Network error or request timeout while testing connection.';
        });
    }
}" class="space-y-6">
    @include('installer.partials.step2.header')
    @include('installer.partials.step2.test-banner')

    <form action="{{ route('install.save_db') }}" method="POST" class="space-y-4">
        @csrf
        @include('installer.partials.step2.driver-selector')
        @include('installer.partials.step2.mysql-fields')
        @include('installer.partials.step2.app-url-field')
        @include('installer.partials.step2.actions')
    </form>
</div>
@endsection
