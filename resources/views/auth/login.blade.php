<x-guest-layout>
    <div x-data="{
        email: '{{ old('email', '') }}',
        password: '',
        showPassword: false,
        isSubmitting: false
    }" class="w-full max-w-md mx-auto">

        <!-- Glassmorphism Main Card -->
        <div class="glass-panel rounded-3xl p-8 sm:p-10 shadow-2xl relative overflow-hidden">
            @include('auth.partials.login.header')
            @include('auth.partials.login.status')

            <!-- Login Form -->
            <form method="POST" action="{{ route('login') }}" @submit="isSubmitting = true" class="space-y-4">
                @csrf
                @include('auth.partials.login.form')
            </form>

            @include('auth.partials.login.footer')
        </div>

    </div>
</x-guest-layout>
