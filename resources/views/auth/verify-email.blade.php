<x-guest-layout>
    <div class="auth-heading">
        <p>VERIFIKASI AKUN</p>
        <h1>Periksa email Anda</h1>
        <span>Verifikasi alamat email untuk melanjutkan.</span>
    </div>
    <div class="mb-4 text-sm text-gray-600">
        Kami telah mengirim tautan verifikasi ke alamat email Anda. Buka tautan tersebut untuk melanjutkan. Jika belum menerima email, Anda dapat meminta pengiriman ulang.
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-600">
            Tautan verifikasi baru telah dikirim ke alamat email Anda.
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button>
                    Kirim ulang email verifikasi
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Keluar
            </button>
        </form>
    </div>
</x-guest-layout>
