<section x-data="{
    current_password: '',
    password: '',
    password_confirmation: '',
    showCurrent: false,
    showNew: false,
    get strength() {
        let s = 0;
        if (this.password.length >= 8) s += 1;
        if (/[0-9]/.test(this.password)) s += 1;
        if (/[^A-Za-z0-9]/.test(this.password) || /[A-Z]/.test(this.password)) s += 1;
        return s;
    }
}">
    @include('profile.partials.password.header')

    <form method="post" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        @method('put')

        @include('profile.partials.password.inputs')
        @include('profile.partials.password.actions')
    </form>
</section>
