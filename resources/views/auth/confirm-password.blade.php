<x-guest-layout>
    <div class="auth-heading">
        <p>VERIFIKASI KEAMANAN</p>
        <h1>Konfirmasi kata sandi</h1>
        <span>Verifikasi diperlukan untuk melanjutkan.</span>
    </div>
    <div class="mb-4 text-sm text-gray-600">
        Masukkan kata sandi Anda untuk melanjutkan.
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <!-- Password -->
        <div>
            <x-input-label for="password" value="Kata sandi" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex justify-end mt-4">
            <x-primary-button>
                Konfirmasi
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
