@extends('layouts.app')

@section('content')
    <div class="verify-email min-h-screen flex items-center justify-center px-4">
        <div class="w-full max-w-md bg-white dark:bg-gray-800 shadow-lg rounded-lg p-6">

            <!-- Success/Error Messages -->
            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
                    {{ session('error') }}
                </div>
            @endif

            @if (session('warning'))
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded mb-4">
                    {{ session('warning') }}
                </div>
            @endif

            {{-- <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                {{ __('Before proceeding, please check your email for a verification code or click the link inside the email.') }}
            </div> --}}

            <!-- Email Display -->
            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-blue-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                        <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                    </svg>
                    <span class="text-blue-700 font-medium">Verification code sent to:</span>
                </div>
                <div class="mt-2 text-blue-900 font-semibold">{{ $email }}</div>
            </div>

            <!-- Verification Code Form -->
            <form method="POST" action="{{ route('verification.verify-code') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="email" value="{{ $email }}">

                <div>
                    <x-input-label for="verification_code" :value="__('Enter 6-digit Verification Code')" />
                    <x-text-input id="verification_code"
                        class="block mt-1 w-full text-center text-2xl font-mono tracking-widest @error('verification_code') border-red-500 @enderror"
                        type="text" name="verification_code" maxlength="6" placeholder="000000"
                        value="{{ old('verification_code') }}" required autofocus autocomplete="one-time-code" />
                    @error('verification_code')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <x-primary-button class="w-full justify-center py-3">
                    {{ __('Verify Email') }}
                </x-primary-button>
            </form>

            <!-- Resend Verification Code -->
            <div class="text-center mt-6">
                <form method="POST" action="{{ route('verification.resend') }}" id="resendForm">
                    @csrf
                    <input type="hidden" name="email" value="{{ $email }}">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                        Didn't receive the code?
                    </p>
                    <button type="submit" class="text-blue-600 hover:text-blue-500 font-medium underline"
                        id="resendButton">
                        {{ __('Resend Verification Code') }}
                    </button>
                </form>

                <div id="resendCounter" class="mt-2 text-sm text-gray-500 hidden">
                    You can resend again in <span id="countdown">60</span> seconds
                </div>
            </div>

            <div class="mt-4 text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Wrong email?
                    <a href="{{ route('login') }}" class="text-blue-600 hover:text-blue-500 font-medium">
                        Go to login
                    </a>
                </p>
            </div>

        </div>
    </div>

    <script>
        const codeInput = document.getElementById('verification_code');

        // Auto-format input
        codeInput.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '');
            if (this.value.length > 6) this.value = this.value.slice(0, 6);
            if (this.value.length === 6) this.form.submit(); // optional
        });

        codeInput.focus();

        // Resend cooldown
        const resendForm = document.getElementById('resendForm');
        const resendButton = document.getElementById('resendButton');
        const resendCounter = document.getElementById('resendCounter');
        const countdownElement = document.getElementById('countdown');

        resendForm.addEventListener('submit', function(e) {
            resendButton.disabled = true;
            resendButton.classList.add('text-gray-400', 'cursor-not-allowed');
            resendButton.classList.remove('text-blue-600', 'hover:text-blue-500');
            resendCounter.classList.remove('hidden');

            let seconds = 60;
            const countdown = setInterval(() => {
                countdownElement.textContent = seconds;
                seconds--;
                if (seconds < 0) {
                    clearInterval(countdown);
                    resendButton.disabled = false;
                    resendButton.classList.remove('text-gray-400', 'cursor-not-allowed');
                    resendButton.classList.add('text-blue-600', 'hover:text-blue-500');
                    resendCounter.classList.add('hidden');
                }
            }, 1000);
        });
    </script>
@endsection
