@extends('layouts.app')

@section('title', 'User Management - PageTurner')

@section('header')
    <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold text-white">User Management</h1>
        <a href="{{ route('admin.dashboard') }}" class="text-sm text-matcha-200 hover:text-white">← Back to Dashboard</a>
    </div>
@endsection

@section('content')
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Orders</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reviews</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email Verified</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">2FA</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach ($users as $user)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">{{ $user->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-600">{{ $user->email }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 rounded text-xs font-medium {{ $user->role === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($user->role) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-600">{{ $user->orders_count }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-600">{{ $user->reviews_count }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($user->hasVerifiedEmail())
                                <span class="text-green-600">Yes</span>
                            @else
                                <span class="text-red-600">No</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($user->two_factor_enabled ?? false)
                                <span class="text-green-600">Enabled</span>
                            @else
                                <span class="text-gray-500">Disabled</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="px-6 py-4">
            {{ $users->links() }}
        </div>
    </div>
@endsection
