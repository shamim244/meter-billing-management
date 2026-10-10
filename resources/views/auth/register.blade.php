<x-guest-layout>
    <div x-data="{
        name: '{{ old('name', '') }}',
        email: '{{ old('email', '') }}',
        phone: '{{ old('phone', '') }}',
        password: '',
        password_confirmation: '',
        showPassword: false,
        isSubmitting: false,
        get strength() {
            let s = 0;
            if (this.password.length >= 8) s += 1;
            if (/[0-9]/.test(this.password)) s += 1;
            if (/[^A-Za-z0-9]/.test(this.password) || /[A-Z]/.test(this.password)) s += 1;
            return s;
        }
    }" class="w-full max-w-md mx-auto">

        <!-- Glassmorphism Main Card -->
        <div class="glass-panel rounded-3xl p-8 sm:p-10 shadow-2xl relative overflow-hidden">
            
            @include('auth.partials.register.header')

            <!-- Registration Form -->
            <form method="POST" action="{{ route('register') }}" @submit="isSubmitting = true" class="space-y-4">
                @csrf

                @include('auth.partials.register.basic-fields')
                @include('auth.partials.register.security-fields')
                @include('auth.partials.register.referral-field')
                @include('auth.partials.register.footer')
            </form>

        </div>

    </div>
</x-guest-layout>
