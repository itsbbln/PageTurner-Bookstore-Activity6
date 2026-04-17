@extends('layouts.app')

@section('title', 'Audit Logs - PageTurner')

@section('header')
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-white">Audit Logs</h1>
            <p class="text-sm text-matcha-100">Search and filter sensitive system changes</p>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="text-sm text-matcha-200 hover:text-white">← Back to Dashboard</a>
    </div>
@endsection

@section('content')
    <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-100 p-6 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">User</label>
                <select name="user_id" class="mt-1 block w-full rounded border-gray-300">
                    <option value="">Any</option>
                    @foreach ($users as $u)
                        <option value="{{ $u->id }}" @selected(request('user_id') == $u->id)>
                            {{ $u->name }} ({{ $u->email }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Event</label>
                <select name="event" class="mt-1 block w-full rounded border-gray-300">
                    <option value="">Any</option>
                    @foreach ($eventOptions as $ev)
                        <option value="{{ $ev }}" @selected(request('event') === $ev)>{{ $ev }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Model</label>
                <select name="auditable_type" class="mt-1 block w-full rounded border-gray-300">
                    <option value="">Any</option>
                    @foreach ($typeOptions as $t)
                        <option value="{{ $t }}" @selected(request('auditable_type') === $t)>{{ $t }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700">From</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="mt-1 block w-full rounded border-gray-300" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">To</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="mt-1 block w-full rounded border-gray-300" />
                </div>
            </div>

            <div class="md:col-span-6 flex items-center gap-3">
                <button class="inline-flex items-center px-4 py-2 rounded-lg bg-matcha-900 text-white hover:bg-matcha-800 font-semibold">Filter</button>
                <a href="{{ route('admin.audit.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Reset</a>
                <a href="{{ route('admin.audit.export.csv', request()->query()) }}" class="text-sm px-3 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700">Export CSV</a>
                <a href="{{ route('admin.audit.export.pdf', request()->query()) }}" class="text-sm px-3 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700">Export PDF</a>
                <div class="ml-auto text-xs text-gray-500">
                    Showing <span class="font-semibold text-gray-700">{{ $audits->count() }}</span> on this page
                </div>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-100 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Event</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Target</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($audits as $audit)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-700">{{ $audit->created_at?->format('Y-m-d H:i:s') }}</td>
                        <td class="px-6 py-4 text-gray-700">
                            {{ $audit->user_id ? ('#' . $audit->user_id) : '—' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 rounded-full text-xs font-semibold
                                @if($audit->event === 'created') bg-green-100 text-green-800
                                @elseif($audit->event === 'updated') bg-blue-100 text-blue-800
                                @elseif($audit->event === 'deleted') bg-red-100 text-red-800
                                @else bg-gray-100 text-gray-800 @endif
                            ">{{ $audit->event }}</span>
                        </td>
                        <td class="px-6 py-4 text-gray-700">
                            <div class="font-medium">{{ class_basename($audit->auditable_type) }} #{{ $audit->auditable_id }}</div>
                            <div class="text-xs text-gray-500">{{ $audit->auditable_type }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-600">{{ $audit->ip_address ?? '—' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <a class="text-indigo-600 hover:text-indigo-800" href="{{ route('admin.audit.show', $audit) }}">View diff</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-6 py-8 text-center text-gray-500" colspan="6">No audit logs found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="px-6 py-4">
            {{ $audits->links() }}
        </div>
    </div>
@endsection

