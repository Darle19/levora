@extends('layouts.app')

@section('content')
<div class="py-8">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Agency Profile</h1>
            <p class="mt-2 text-sm text-gray-600">Manage your agency information</p>
        </div>

        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-900">Agency Information</h2>
            </div>

            <div class="p-6">
                <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Agency Name</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $agency->name }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Legal Name</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $agency->legal_name }}</dd>
                    </div>

                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">Legal Address</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $agency->legal_address }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Phone</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $agency->phone }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Mobile</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $agency->mobile ?? 'N/A' }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Email</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $agency->email }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Website</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $agency->website ?? 'N/A' }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Director</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $agency->director }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">INN (Tax ID)</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $agency->inn }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Bank Account</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $agency->bank_account ?? 'N/A' }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Bank Name</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $agency->bank_name ?? 'N/A' }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">MFO</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $agency->mfo ?? 'N/A' }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1">
                            @if($agency->is_active)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    Inactive
                                </span>
                            @endif
                        </dd>
                    </div>
                </dl>

                @if($agency->notes)
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <dt class="text-sm font-medium text-gray-500 mb-2">Notes</dt>
                        <dd class="text-sm text-gray-900">{{ $agency->notes }}</dd>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
