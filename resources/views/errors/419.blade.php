{{-- resources/views/errors/419.blade.php --}}
@extends('filament::layouts.app')

@section('title', __('Page Expired'))

@section('content')
<div class="flex flex-col items-center justify-center min-h-[60vh] space-y-6 text-center">
    <h1 class="text-6xl font-bold text-danger-600">419</h1>

    <h2 class="text-2xl font-semibold text-gray-800 dark:text-gray-100">
        {{ __('Page Expired') }}
    </h2>

    <p class="text-gray-600 dark:text-gray-400">
        {{ __('Your session has expired. Redirecting you to the login page...') }}
    </p>

    <x-filament::button
        tag="a"
        color="primary"
        href="{{ route('filament.admin.auth.login') }}">
        {{ __('Go to Login Now') }}
    </x-filament::button>
</div>

<script>
    setTimeout(() => {
        window.location.href = "{{ route('filament.admin.auth.login') }}";
    }, 3000);
</script>
@endsection