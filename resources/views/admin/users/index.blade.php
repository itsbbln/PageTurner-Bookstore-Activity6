@extends('layouts.app')

@section('title', 'User Management - PageTurner')

@section('header')
    <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold text-white">User Management</h1>
        <a href="{{ route('admin.dashboard') }}" class="text-sm text-matcha-200 hover:text-white">← Back to Dashboard</a>
    </div>
@endsection

@section('content')
    <div class="bg-white rounded-lg shadow p-6 mb-6 space-y-4">
        <h2 class="text-lg font-semibold text-gray-900">User Import/Export (Admin)</h2>
        <a href="{{ route('admin.users.template') }}" class="inline-flex items-center px-3 py-2 bg-slate-700 text-white text-sm rounded">Download User Import Template</a>

        <form method="POST" action="{{ route('admin.users.import') }}" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            @csrf
            <input type="file" name="file" required class="border rounded px-2 py-1 text-sm md:col-span-2" />
            <select name="default_role" class="border rounded px-2 py-1 text-sm">
                <option value="customer">Default role: customer</option>
                <option value="premium">Default role: premium</option>
                <option value="admin">Default role: admin</option>
            </select>
            <button type="submit" class="bg-indigo-600 text-white rounded px-3 py-2 text-sm">Bulk Import Users</button>
        </form>

        <form method="POST" action="{{ route('admin.users.export') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3">
            @csrf
            <select name="format" class="border rounded px-2 py-1 text-sm">
                <option value="xlsx">XLSX</option>
                <option value="csv">CSV</option>
                <option value="pdf">PDF</option>
            </select>
            <select name="role" class="border rounded px-2 py-1 text-sm">
                <option value="">All roles</option>
                <option value="admin">Admin</option>
                <option value="customer">Customer</option>
                <option value="premium">Premium</option>
            </select>
            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="redact_pii" value="1" class="rounded border-gray-300" />
                Redact PII (GDPR)
            </label>
            <div></div>
            <button type="submit" class="bg-green-600 text-white rounded px-3 py-2 text-sm">Export Users</button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Recent Order/User Export Logs</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div>
                <h3 class="font-semibold mb-2">User Imports</h3>
                <ul class="space-y-1 text-gray-700">
                    @forelse($recentUserImports as $log)
                        <li>{{ $log->created_at?->format('Y-m-d H:i') }} - {{ $log->status }} ({{ $log->original_filename }})</li>
                    @empty
                        <li class="text-gray-500">No recent records</li>
                    @endforelse
                </ul>
            </div>
            <div>
                <h3 class="font-semibold mb-2">User Exports</h3>
                <ul class="space-y-1 text-gray-700">
                    @forelse($recentUserExports as $log)
                        <li>{{ $log->created_at?->format('Y-m-d H:i') }} - {{ $log->status }} ({{ strtoupper($log->format) }})</li>
                    @empty
                        <li class="text-gray-500">No recent records</li>
                    @endforelse
                </ul>
            </div>
            <div>
                <h3 class="font-semibold mb-2">Order Exports</h3>
                <ul class="space-y-1 text-gray-700">
                    @forelse($recentOrderExports as $log)
                        <li>{{ $log->created_at?->format('Y-m-d H:i') }} - {{ $log->type }} / {{ $log->status }}</li>
                    @empty
                        <li class="text-gray-500">No recent records</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

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
