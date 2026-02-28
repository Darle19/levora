@extends('layouts.app')

@section('content')
<div class="py-12 px-4 sm:px-6 lg:px-8 bg-slate-50">
    <div class="max-w-lg mx-auto">
        <div class="text-center mb-10">
            <div class="w-16 h-16 rounded-2xl bg-amber-100 flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-slate-800 mb-2">{{ __('messages.auth.registration_pending_title') }}</h1>
            <p class="text-slate-500 mt-4">{{ __('messages.auth.registration_pending_description') }}</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8 text-center">
            <div class="text-slate-600 space-y-4">
                <p>{{ __('messages.auth.registration_pending_message') }}</p>
            </div>

            <div class="mt-8">
                <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-6 py-3 text-sm font-semibold text-white rounded-xl btn-primary transition-all duration-200">
                    {{ __('messages.auth.back_to_login') }}
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
