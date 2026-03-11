@extends('layouts.app')

@section('title', 'Orders Management - Admin')

@section('content')
<div class="py-12 bg-gray-50">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h1 class="text-3xl font-bold text-gray-900 mb-6">Order Management</h1>

                @if (session('success'))
                    <div class="mb-4 rounded-lg bg-green-50 p-4 text-sm text-green-700 border border-green-200">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($orders->count() > 0)
                    {{-- Page size selector --}}
                    <div class="flex justify-end mb-4">
                        <form method="GET" action="{{ route('admin.orders.index') }}" class="flex items-center gap-2 text-sm">
                            <label for="per_page" class="text-gray-700">Show:</label>
                            @php
                                $perPageSelected = $perPageInput ?? request('per_page', 5);
                            @endphp
                            <select
                                id="per_page"
                                name="per_page"
                                class="border border-gray-300 rounded-md px-2 py-1 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                                onchange="this.form.submit()"
                            >
                                <option value="5" {{ $perPageSelected == 5 ? 'selected' : '' }}>5</option>
                                <option value="10" {{ $perPageSelected == 10 ? 'selected' : '' }}>10</option>
                                <option value="25" {{ $perPageSelected == 25 ? 'selected' : '' }}>25</option>
                                <option value="all" {{ $perPageSelected === 'all' ? 'selected' : '' }}>All</option>
                            </select>
                            <span class="text-gray-500">orders per page</span>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full border-t border-gray-300">
                            <thead class="bg-gray-70 border-b-2 border-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 uppercase tracking-wider">
                                        Order ID
                                    </th>
                                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 uppercase tracking-wider">
                                        Customer
                                    </th>
                                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 uppercase tracking-wider">
                                        Items
                                    </th>
                                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 uppercase tracking-wider">
                                        Total
                                    </th>
                                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 uppercase tracking-wider">
                                        Date
                                    </th>
                                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($orders as $order)
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                            #{{ $order->id }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            <div class="font-medium">{{ $order->user->name }}</div>
                                            <div class="text-xs text-gray-500">{{ $order->user->email }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600">
                                            @foreach ($order->orderItems as $item)
                                                <div class="text-xs">
                                                    {{ $item->quantity }}x {{ $item->book->title }}
                                                </div>
                                            @endforeach
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                            ₱{{ number_format($order->total_amount, 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-3 py-1 text-xs font-semibold rounded-full
                                                @switch($order->status)
                                                    @case('pending')
                                                        bg-yellow-100 text-yellow-800
                                                        @break
                                                    @case('processing')
                                                        bg-blue-100 text-blue-800
                                                        @break
                                                    @case('shipped')
                                                        bg-purple-100 text-purple-800
                                                        @break
                                                    @case('delivered')
                                                        bg-green-100 text-green-800
                                                        @break
                                                    @case('cancelled')
                                                        bg-red-100 text-red-800
                                                        @break
                                                @endswitch
                                            ">
                                                {{ ucfirst($order->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            {{ $order->created_at->format('M d, Y') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm space-y-2">
                                            <!-- View Details -->
                                            <a href="{{ route('orders.show', $order) }}"
                                                class="text-indigo-600 hover:text-indigo-700 font-semibold">
                                                View
                                            </a>

                                            <!-- Status Update Dropdown -->
                                            <div class="flex items-center gap-2">
                                                <form method="POST" 
                                                    action="{{ route('admin.orders.update', $order) }}"
                                                    class="inline">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="flex gap-2">
                                                        <select name="status" class="text-xs px-2 py-1 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                                                            <option value="pending" {{ $order->status == 'pending' ? 'selected' : '' }}>Pending</option>
                                                            <option value="processing" {{ $order->status == 'processing' ? 'selected' : '' }}>Processing</option>
                                                            <option value="completed" {{ $order->status == 'completed' ? 'selected' : '' }}>Completed</option>
                                                            <option value="cancelled" {{ $order->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                                        </select>
                                                        <button type="submit" class="text-xs text-indigo-600 hover:text-indigo-700 font-semibold">Update</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-6">
                        {{ $orders->links() }}
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">No Orders Yet</h3>
                        <p class="text-gray-600">No customer orders have been placed.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
