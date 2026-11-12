<x-guest-layout>
    <div class="auth-heading">
        <p>PEMULIHAN AKUN</p>
        <h1>Lupa kata sandi?</h1>
        <span>Kami akan mengirim tautan untuk mengatur kata sandi baru.</span>
    </div>
    <div class="mb-4 text-sm text-gray-600">
        Masukkan alamat email akun Anda untuk menerima tautan pemulihan.
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" value="Alamat email" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                Kirim tautan pemulihan
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
