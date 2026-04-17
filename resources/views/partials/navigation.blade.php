<nav class="bg-matcha-900 text-white shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            
            <!-- Left Side -->
            <div class="flex items-center">
                
                <!-- Logo -->
                <a href="{{ route('home') }}" class="text-xl font-bold">
                    PageTurner
                </a>

                <!-- Navigation Links -->
                <div class="hidden md:flex ml-10 space-x-4">
                    <a href="{{ route('home') }}" class="hover:bg-matcha-800 px-3 py-2 rounded-md">
                        Home
                    </a>

                    <a href="{{ route('books.index') }}" class="hover:bg-matcha-800 px-3 py-2 rounded-md">
                        Books
                    </a>

                    <a href="{{ route('categories.index') }}" class="hover:bg-matcha-800 px-3 py-2 rounded-md">
                        Categories
                    </a>

                    @auth
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('admin.books.create') }}" class="hover:bg-matcha-800 px-3 py-2 rounded-md">
                                Add Book
                            </a>

                            <a href="{{ route('admin.categories.create') }}" class="hover:bg-matcha-800 px-3 py-2 rounded-md">
                                Add Category
                            </a>

                            <a href="{{ route('admin.data-management.index') }}" class="hover:bg-matcha-800 px-3 py-2 rounded-md">
                                Data Management
                            </a>
                        @endif
                    @endauth
                </div>
            </div>

            <!-- Right Side -->
            <div class="flex items-center space-x-4">
                @guest
                    <a href="{{ route('login') }}" class="hover:bg-matcha-800 px-3 py-2 rounded-md">
                        Login
                    </a>

                    <a href="{{ route('register') }}" class="bg-matcha-300 text-matcha-900 px-4 py-2 rounded-md font-medium hover:bg-matcha-200">
                        Register
                    </a>
                @endguest

                @auth
                    @if(!auth()->user()->isAdmin())
                        <a href="{{ route('dashboard') }}" class="hover:bg-matcha-800 px-3 py-2 rounded-md">
                            My Dashboard
                        </a>
                        <!-- Shopping Cart (Customer only) -->
                        <a href="{{ route('cart.index') }}" class="hover:bg-matcha-800 px-3 py-2 rounded-md flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span id="cart-count" class="text-sm bg-matcha-700 px-2 py-1 rounded-full">
                                {{ count(session('cart', [])) }}
                            </span>
                        </a>

                        <a href="{{ route('orders.index') }}" class="hover:bg-matcha-800 px-3 py-2 rounded-md">
                            My Orders
                        </a>
                    @endif

                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="hover:bg-matcha-800 px-3 py-2 rounded-md text-yellow-300 font-semibold">
                            Admin Dashboard
                        </a>
                        <a href="{{ route('admin.audit.index') }}" class="hover:bg-matcha-800 px-3 py-2 rounded-md">
                            Audit Logs
                        </a>
                    @endif

                    <a href="{{ route('profile.edit') }}" class="hover:bg-matcha-800 px-3 py-2 rounded-md">
                        Profile
                    </a>

                    <span class="text-matcha-100">
                        {{ auth()->user()->name }}
                    </span>

                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="hover:bg-matcha-800 px-3 py-2 rounded-md">
                            Logout
                        </button>
                    </form>
                @endauth
            </div>

        </div>
    </div>
</nav>
