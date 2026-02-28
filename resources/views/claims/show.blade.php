@extends('layouts.app')

@section('content')
<div class="py-8">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Claim Details</h1>
                <p class="mt-2 text-sm text-gray-600">Order #{{ $order->order_number }}</p>
            </div>
            <a href="{{ route('claims.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                Back to Claims
            </a>
        </div>

        <!-- Order Information -->
        <div class="bg-white shadow-sm rounded-lg overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-900">Order Information</h2>
            </div>

            <div class="p-6">
                <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Order Number</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $order->order_number }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Created By</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $order->user->name }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                @if($order->status === 'confirmed') bg-green-100 text-green-800
                                @elseif($order->status === 'pending') bg-yellow-100 text-yellow-800
                                @elseif($order->status === 'cancelled') bg-red-100 text-red-800
                                @else bg-gray-100 text-gray-800 @endif">
                                {{ ucfirst($order->status) }}
                            </span>
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Total Price</dt>
                        <dd class="mt-1 text-sm text-gray-900 font-semibold">{{ number_format($order->total_price, 2) }} {{ $order->currency->code ?? 'USD' }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">Created Date</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $order->created_at->format('d M Y H:i') }}</dd>
                    </div>

                    @if($order->notes)
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500">Notes</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $order->notes }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>

        <!-- Bookings -->
        <div class="bg-white shadow-sm rounded-lg overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-900">Bookings</h2>
            </div>

            <div class="p-6">
                @forelse($order->bookings as $booking)
                    <div class="mb-6 last:mb-0 pb-6 last:pb-0 border-b last:border-b-0 border-gray-200">
                        <h3 class="text-md font-semibold text-gray-900 mb-4">
                            Booking #{{ $booking->id }} - {{ $booking->bookable_type }}
                        </h3>
                        <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-3">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Status</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ ucfirst($booking->status) }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Price</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ number_format($booking->price, 2) }} {{ $booking->currency->code ?? 'USD' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Date</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $booking->date->format('d M Y') }}</dd>
                            </div>
                        </dl>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">No bookings found</p>
                @endforelse
            </div>
        </div>

        <!-- Documents -->
        @php
            $allDocuments = $order->bookings->flatMap->documents;
        @endphp
        @if($allDocuments->isNotEmpty())
        <div class="bg-white shadow-sm rounded-lg overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">{{ __('messages.doc_documents') }}</h2>
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-gray-600">{{ __('messages.doc_payment_progress') }}: {{ $paymentPercentage }}%</span>
                        <div class="w-32 bg-gray-200 rounded-full h-2.5">
                            <div class="h-2.5 rounded-full {{ $paymentPercentage >= 100 ? 'bg-green-500' : ($paymentPercentage >= 30 ? 'bg-yellow-500' : 'bg-red-500') }}"
                                 style="width: {{ min($paymentPercentage, 100) }}%"></div>
                        </div>
                    </div>
                </div>
                @if($paymentPercentage < 100)
                <p class="mt-2 text-sm text-amber-600">{{ __('messages.doc_locked_message') }}</p>
                @endif
            </div>

            <div class="divide-y divide-gray-200">
                @foreach($allDocuments as $document)
                <div class="px-6 py-3 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $document->getTypeLabel() }}</p>
                            @if($document->getDescription())
                            <p class="text-xs text-gray-500">{{ $document->getDescription() }}</p>
                            @endif
                        </div>
                    </div>
                    <div>
                        @if($order->isFullyPaid())
                        <a href="{{ route('documents.download', $document) }}"
                           class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                            {{ __('messages.doc_download') }}
                        </a>
                        @else
                        <span class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md text-gray-500 bg-gray-100">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            {{ __('messages.doc_locked') }}
                        </span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Payments -->
        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-900">Payments</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Transaction ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($order->payments as $payment)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $payment->payment_date->format('d M Y') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($payment->amount, 2) }} {{ $payment->currency->code ?? 'USD' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $payment->payment_method }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $payment->transaction_id ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                        @if($payment->status === 'completed') bg-green-100 text-green-800
                                        @elseif($payment->status === 'pending') bg-yellow-100 text-yellow-800
                                        @else bg-gray-100 text-gray-800 @endif">
                                        {{ ucfirst($payment->status) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">No payments found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
