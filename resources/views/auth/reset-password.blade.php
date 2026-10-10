<x-guest-layout>
    <div x-data="{
        password: '',
        password_confirmation: '',
        showPassword: false,
        isSubmitting: false
    }" class="w-full max-w-md mx-auto">
        <!-- Glassmorphism Main Card -->
        <div class="glass-panel rounded-3xl p-8 sm:p-10 shadow-2xl relative overflow-hidden">
            <!-- Top Subtle Gradient Border Line -->
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-brand-500 via-indigo-500 to-cyan-400"></div>

            @include('auth.partials.reset-password.header')
            @include('auth.partials.reset-password.form')
        </div>
    </div>
</x-guest-layout>
