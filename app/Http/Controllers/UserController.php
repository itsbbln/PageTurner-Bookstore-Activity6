<?php

namespace App\Http\Controllers;

use App\Models\ExportLog;
use App\Models\ImportLog;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of users (Admin only).
     */
    public function index()
    {
        $users = User::withCount(['orders', 'reviews'])
            ->latest()
            ->paginate(15);

        $recentUserImports = ImportLog::query()->where('type', 'users')->latest()->take(10)->get();
        $recentUserExports = ExportLog::query()->where('type', 'users')->latest()->take(10)->get();
        $recentOrderExports = ExportLog::query()->whereIn('type', ['orders', 'financial'])->latest()->take(10)->get();

        return view('admin.users.index', compact('users', 'recentUserImports', 'recentUserExports', 'recentOrderExports'));
    }
}
