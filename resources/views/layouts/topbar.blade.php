<!-- Topbar -->
<header class="bg-white shadow-sm border-b border-gray-200 px-6 py-4">
    <div class="flex items-center justify-between">
        <!-- Page Title / Breadcrumb -->
        <div class="flex items-center">
            <h1 class="text-xl font-semibold text-gray-900">
                @yield('page-title', 'Dashboard')
            </h1>
        </div>

        <!-- Right Side: User Profile and Actions -->
        <div class="flex items-center space-x-4">
            <!-- Notifications -->
            <div class="relative">
                <button class="p-2 text-gray-400 hover:text-gray-600 focus:outline-none focus:text-gray-600">
                    <i class="fas fa-bell text-lg"></i>
                    <!-- Notification Badge -->
                    @php
                        $overduePayments = \App\Models\Payment::where('status', 'pending')
                                                            ->where('due_date', '<', now()->subDays(20))
                                                            ->count();
                    @endphp
                    @if($overduePayments > 0)
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                            {{ $overduePayments }}
                        </span>
                    @endif
                </button>
            </div>

            <!-- User Profile Dropdown -->
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" 
                        class="flex items-center space-x-3 p-2 rounded-lg hover:bg-gray-100 focus:outline-none focus:bg-gray-100">
                    <!-- Profile Photo -->
                    <div class="w-8 h-8 rounded-full flex items-center justify-center" style="background-color: #007BCE;">
                        @if(auth()->user()->profile_photo)
                            <img src="{{ auth()->user()->profile_photo_url }}" 
                                 alt="Profile" 
                                 class="w-full h-full rounded-full object-cover">
                        @else
                            <i class="fas fa-user text-white text-sm"></i>
                        @endif
                    </div>
                    
                    <!-- User Info -->
                    <div class="text-left">
                        <p class="text-sm font-medium text-gray-900">{{ auth()->user()->full_name }}</p>
                        <p class="text-xs text-gray-500">{{ auth()->user()->getRoleNames()->first() }}</p>
                    </div>
                    
                    <!-- Dropdown Arrow -->
                    <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                </button>

                <!-- Dropdown Menu -->
                <div x-show="open" 
                     @click.away="open = false"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="transform opacity-100 scale-100"
                     x-transition:leave-end="transform opacity-0 scale-95"
                     class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                    
                    <!-- Profile Link -->
                    <a href="{{ route('profile.edit') }}" 
                       class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <i class="fas fa-user-edit w-4 h-4 mr-3 text-gray-400"></i>
                        Modifica Profilo
                    </a>
                    
                    <!-- Divider -->
                    <hr class="my-1 border-gray-200">
                    
                    <!-- Logout -->
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" 
                                class="flex items-center w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                            <i class="fas fa-sign-out-alt w-4 h-4 mr-3"></i>
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- Alpine.js for dropdown functionality -->
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
