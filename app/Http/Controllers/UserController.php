<?php

namespace App\Http\Controllers;

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

        return view('admin.users.index', compact('users'));
    }
}
