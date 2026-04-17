@extends('layouts.app')

@section('title', 'Book Data Management - PageTurner')

@section('header')
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-white">Book Data Management</h1>
            <p class="text-matcha-100 text-sm">Bulk import/export with validation, chunking, and logs</p>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="text-sm text-matcha-200 hover:text-white">← Back to Dashboard</a>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-100 p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Import Books</h2>
                    <p class="text-sm text-gray-500 mt-1">
                        XLSX/CSV with required headers:
                        <span class="font-medium text-gray-700">ISBN, Title, Author, Price, Stock, Category, Description</span>
                    </p>
                </div>
                <a href="{{ route('admin.books.data.template') }}" class="shrink-0 inline-flex items-center rounded-lg px-3 py-2 text-sm font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100">
                    Download template
                </a>
            </div>

            <form class="mt-5 space-y-4" method="POST" action="{{ route('admin.books.data.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="rounded-xl border border-gray-200 p-4">
                    <label class="block text-sm font-semibold text-gray-900">Upload file</label>
                    <p class="text-xs text-gray-500 mt-1">Accepted: .xlsx, .csv</p>
                    <input type="file" name="file" required class="mt-3 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm" />
                    @error('file')
                        <div class="text-sm text-red-600 mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="rounded-xl border border-gray-200 p-4">
                    <label class="flex items-start gap-3 text-sm text-gray-700">
                        <input type="checkbox" name="update_existing" value="1" class="mt-0.5 rounded border-gray-300" @checked(old('update_existing')) />
                        <span>
                            <span class="font-semibold text-gray-900">Update existing</span>
                            <span class="block text-xs text-gray-500">If ISBN already exists, update the book instead of failing.</span>
                        </span>
                    </label>
                </div>

                <div class="rounded-xl border border-gray-200 p-4">
                    <label class="flex items-start gap-3 text-sm text-gray-700">
                        <input type="checkbox" name="allow_duplicate_file" value="1" class="mt-0.5 rounded border-gray-300" @checked(old('allow_duplicate_file')) />
                        <span>
                            <span class="font-semibold text-gray-900">Allow duplicate file upload</span>
                            <span class="block text-xs text-gray-500">If the same file was uploaded before, importing is blocked unless this is checked.</span>
                        </span>
                    </label>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <button class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-matcha-900 text-white hover:bg-matcha-800 font-semibold">
                        Queue import
                    </button>
                    <p class="text-xs text-gray-500">You can refresh the log page to see progress.</p>
                </div>
            </form>

            <div class="mt-8">
                <div class="flex items-center justify-between gap-4">
                    <h3 class="font-semibold text-gray-900">Recent imports</h3>
                    <span class="text-xs text-gray-500">Last 10</span>
                </div>
                <div class="mt-3 space-y-2">
                    @forelse ($importLogs as $log)
                        <a href="{{ route('admin.books.data.import-logs.show', $log) }}" class="block rounded-xl border border-gray-200 p-4 hover:bg-gray-50">
                            <div class="flex items-center justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="text-sm font-medium text-gray-900 truncate">{{ $log->original_filename }}</div>
                                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-gray-500">
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 font-semibold
                                            @if($log->status === 'completed') bg-green-100 text-green-800
                                            @elseif($log->status === 'completed_with_errors') bg-amber-100 text-amber-800
                                            @elseif($log->status === 'failed') bg-red-100 text-red-800
                                            @else bg-gray-100 text-gray-800 @endif
                                        ">
                                            {{ str_replace('_',' ', $log->status) }}
                                        </span>
                                        <span>•</span>
                                        <span>{{ $log->created_at->format('M d, Y H:i') }}</span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs font-semibold text-gray-700 whitespace-nowrap">
                                        {{ $log->processed_rows }}/{{ $log->total_rows ?? '—' }}
                                    </div>
                                    <div class="text-[11px] text-gray-500 whitespace-nowrap">
                                        {{ $log->failed_rows }} failed
                                    </div>
                                </div>
                            </div>
                        </a>
                    @empty
                        <p class="text-sm text-gray-500">No imports yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-100 p-6">
            <h2 class="text-xl font-bold text-gray-900">Export Books</h2>
            <p class="text-sm text-gray-500 mt-1">Filter by category, price, stock, and date range. XLSX/CSV/PDF.</p>

            <form class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-4" method="POST" action="{{ route('admin.books.data.export') }}">
                @csrf
                <div>
                    <label class="block text-sm font-semibold text-gray-900">Format</label>
                    <select name="format" class="mt-1 block w-full rounded-lg border-gray-300">
                        <option value="xlsx">XLSX</option>
                        <option value="csv">CSV</option>
                        <option value="pdf">PDF</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-900">Category</label>
                    <select name="category_id" class="mt-1 block w-full rounded-lg border-gray-300">
                        <option value="">All</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-900">Min price</label>
                    <input type="number" step="0.01" name="min_price" class="mt-1 block w-full rounded-lg border-gray-300" />
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-900">Max price</label>
                    <input type="number" step="0.01" name="max_price" class="mt-1 block w-full rounded-lg border-gray-300" />
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-900">Stock status</label>
                    <select name="stock_status" class="mt-1 block w-full rounded-lg border-gray-300">
                        <option value="">Any</option>
                        <option value="in_stock">In stock</option>
                        <option value="out_of_stock">Out of stock</option>
                        <option value="low_stock">Low stock (1-5)</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-sm font-semibold text-gray-900">From</label>
                        <input type="date" name="date_from" class="mt-1 block w-full rounded-lg border-gray-300" />
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-900">To</label>
                        <input type="date" name="date_to" class="mt-1 block w-full rounded-lg border-gray-300" />
                    </div>
                </div>

                <div class="sm:col-span-2">
                    <div class="text-sm font-semibold text-gray-900">Columns</div>
                    <div class="mt-2 grid grid-cols-2 sm:grid-cols-4 gap-2 rounded-xl border border-gray-200 p-4">
                        @foreach ($defaultColumns as $col => $checked)
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="columns[]" value="{{ $col }}" class="rounded border-gray-300" @checked($checked) />
                                {{ ucfirst(str_replace('_',' ', $col)) }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="sm:col-span-2">
                    <div class="flex flex-wrap items-center gap-3">
                        <button class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 font-semibold">
                            Export & download
                        </button>
                        <p class="text-xs text-gray-500">Large exports will queue and auto-download when ready.</p>
                    </div>
                </div>
            </form>

            <div class="mt-8">
                <div class="flex items-center justify-between gap-4">
                    <h3 class="font-semibold text-gray-900">Recent exports</h3>
                    <span class="text-xs text-gray-500">Last 10</span>
                </div>
                <div class="mt-3 space-y-2">
                    @forelse ($exportLogs as $log)
                        <a href="{{ route('admin.books.data.export-logs.show', $log) }}" class="block rounded-xl border border-gray-200 p-4 hover:bg-gray-50">
                            <div class="flex items-center justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="text-sm font-medium text-gray-900 truncate">{{ strtoupper($log->format) }} export</div>
                                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-gray-500">
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 font-semibold
                                            @if($log->status === 'completed') bg-green-100 text-green-800
                                            @elseif($log->status === 'failed') bg-red-100 text-red-800
                                            @else bg-gray-100 text-gray-800 @endif
                                        ">
                                            {{ $log->status }}
                                        </span>
                                        <span>•</span>
                                        <span>{{ $log->created_at->format('M d, Y H:i') }}</span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs font-semibold text-gray-700 whitespace-nowrap">
                                        {{ $log->record_count ?? '—' }} records
                                    </div>
                                    <div class="text-[11px] text-gray-500 whitespace-nowrap">
                                        {{ strtoupper($log->format) }}
                                    </div>
                                </div>
                            </div>
                        </a>
                    @empty
                        <p class="text-sm text-gray-500">No exports yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection

