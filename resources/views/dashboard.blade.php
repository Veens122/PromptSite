@extends('layouts.app')
@section('content')
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="w-full max-w-md bg-white dark:bg-gray-800 shadow-lg rounded-lg p-6">


            {{-- Dashboard  --}}
            <h1>Dashboard</h1>
            <div>
                {{-- Profile settings --}}
                <a href="" class="block text-center px-4 py-2 text-white hover:bg-gray-100 hover:text-gray-700">
                    Profile edit
                </a>
                {{-- Update password --}}
                <a href="" class="block text-center px-4 py-2 text-white hover:bg-gray-100 hover:text-gray-700">
                    Change Password
                </a>
                {{-- Delete account --}}
                <a href="{{ route('profile.destroy') }}"
                    class="block text-center px-4 py-2 text-white hover:bg-gray-100 hover:text-gray-700">
                    Delete Account

                    {{-- Logout --}}
                    <a href="{{ route('logout') }}"
                        class="text-red-500 text-center w-full px-4 py-2 hover:bg-red-500 hover:text-white">
                        Logout
                    </a>
            </div>

            {{-- <x-slot name="header">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ __('Dashboard') }}
                </h2>
            </x-slot>

            <div class="py-12">
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900 dark:text-gray-100">
                            {{ __("You're logged in!") }}
                        </div>
                    </div>
                </div>
            </div> --}}
        </div>
    </div>
@endsection
