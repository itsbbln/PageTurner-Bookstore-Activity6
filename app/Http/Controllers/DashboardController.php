<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;

class DashboardController extends Controller
{
    /**
     * Admin dashboard.
     */
    public function index()
    {
        $totalBooks = Book::count();
        $totalCategories = Category::count();
        $totalOrders = Order::count();
        $completedOrders = Order::where('status', 'completed')->count();
        $pendingOrders = Order::where('status', 'pending')->count();
        $totalCustomers = User::where('role', 'customer')->count();

        $recentOrders = Order::with('user')
            ->latest()
            ->take(10)
            ->get();

        $orderStatusSummary = Order::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $recentReviews = Review::with(['book', 'user'])
            ->latest()
            ->take(10)
            ->get();

        return view('dashboard', compact(
            'totalBooks',
            'totalCategories',
            'totalOrders',
            'completedOrders',
            'pendingOrders',
            'totalCustomers',
            'recentOrders',
            'orderStatusSummary',
            'recentReviews'
        ));
    }

    /**
     * Customer dashboard.
     */
    public function customer()
    {
        $user = auth()->user();

        $ordersQuery = $user->orders()->with('orderItems.book');

        $totalOrders = (clone $ordersQuery)->count();
        $recentOrders = (clone $ordersQuery)->latest()->take(5)->get();

        $orderStatusSummary = (clone $ordersQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $recentBooks = $user->orders()
            ->where('status', 'completed')
            ->with('orderItems.book')
            ->latest()
            ->take(10)
            ->get()
            ->flatMap->orderItems
            ->pluck('book')
            ->unique('id')
            ->values();

        $reviews = $user->reviews()
            ->with('book')
            ->latest()
            ->take(10)
            ->get();

        return view('user.dashboard', [
            'user' => $user,
            'totalOrders' => $totalOrders,
            'recentOrders' => $recentOrders,
            'orderStatusSummary' => $orderStatusSummary,
            'recentBooks' => $recentBooks,
            'reviews' => $reviews,
        ]);
    }
}

