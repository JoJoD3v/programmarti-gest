<!-- Sidebar -->
<div class="w-64 shadow-lg flex flex-col" style="background: linear-gradient(135deg, #002D4D 0%, #007BCE 100%);">
    <!-- Logo -->
    <div class="p-6 border-b border-white/10">
        <div class="flex items-center">
            <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center mr-3 overflow-hidden">
                <img src="{{ asset('img/logo/LOGO.jpg') }}" alt="ProgrammArti Logo" class="w-full h-full object-contain">
            </div>
            <div>
                <h2 class="text-white font-bold text-lg">ProgrammArti</h2>
                <p class="text-white/70 text-sm">Gestionale</p>
            </div>
        </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="flex-1 p-4">
        <ul class="space-y-2">
            <!-- Dashboard -->
            <li>
                <a href="{{ route('dashboard') }}" 
                   class="flex items-center px-4 py-3 text-white/90 rounded-lg hover:bg-white/10 hover:text-white transition-all duration-200 {{ request()->routeIs('dashboard') ? 'bg-white/20 text-white' : '' }}">
                    <i class="fas fa-tachometer-alt w-5 h-5 mr-3"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <!-- User Management -->
            @can('manage users')
            <li>
                <a href="{{ route('users.index') }}" 
                   class="flex items-center px-4 py-3 text-white/90 rounded-lg hover:bg-white/10 hover:text-white transition-all duration-200 {{ request()->routeIs('users.*') ? 'bg-white/20 text-white' : '' }}">
                    <i class="fas fa-users w-5 h-5 mr-3"></i>
                    <span>Gestione Utenti</span>
                </a>
            </li>
            @endcan

            <!-- Client Management -->
            @can('manage clients')
            <li>
                <a href="{{ route('clients.index') }}"
                   class="flex items-center px-4 py-3 text-white/90 rounded-lg hover:bg-white/10 hover:text-white transition-all duration-200 {{ request()->routeIs('clients.*') ? 'bg-white/20 text-white' : '' }}">
                    <i class="fas fa-address-book w-5 h-5 mr-3"></i>
                    <span>Gestione Clienti</span>
                </a>
            </li>
            @endcan

            <!-- Appointment Management -->
            <li>
                <a href="{{ route('appointments.index') }}"
                   class="flex items-center px-4 py-3 text-white/90 rounded-lg hover:bg-white/10 hover:text-white transition-all duration-200 {{ request()->routeIs('appointments.*') ? 'bg-white/20 text-white' : '' }}">
                    <i class="fas fa-calendar-check w-5 h-5 mr-3"></i>
                    <span>Gestione Appuntamenti</span>
                </a>
            </li>

            <!-- Project Management -->
            @can('manage projects')
            <li>
                <a href="{{ route('projects.index') }}" 
                   class="flex items-center px-4 py-3 text-white/90 rounded-lg hover:bg-white/10 hover:text-white transition-all duration-200 {{ request()->routeIs('projects.*') ? 'bg-white/20 text-white' : '' }}">
                    <i class="fas fa-project-diagram w-5 h-5 mr-3"></i>
                    <span>Gestione Progetti</span>
                </a>
            </li>
            @endcan

            <!-- Payment Management -->
            @can('manage payments')
            <li>
                <a href="{{ route('payments.index') }}" 
                   class="flex items-center px-4 py-3 text-white/90 rounded-lg hover:bg-white/10 hover:text-white transition-all duration-200 {{ request()->routeIs('payments.*') ? 'bg-white/20 text-white' : '' }}">
                    <i class="fas fa-credit-card w-5 h-5 mr-3"></i>
                    <span>Gestione Pagamenti</span>
                </a>
            </li>
            @endcan

            <!-- Expense Management -->
            @can('manage expenses')
            <li>
                <a href="{{ route('expenses.index') }}"
                   class="flex items-center px-4 py-3 text-white/90 rounded-lg hover:bg-white/10 hover:text-white transition-all duration-200 {{ request()->routeIs('expenses.*') ? 'bg-white/20 text-white' : '' }}">
                    <i class="fas fa-receipt w-5 h-5 mr-3"></i>
                    <span>Gestione Spese</span>
                </a>
            </li>
            @endcan

            <!-- Work Management -->
            <li>
                <a href="{{ route('works.index') }}"
                   class="flex items-center px-4 py-3 text-white/90 rounded-lg hover:bg-white/10 hover:text-white transition-all duration-200 {{ request()->routeIs('works.*') ? 'bg-white/20 text-white' : '' }}">
                    <i class="fas fa-tasks w-5 h-5 mr-3"></i>
                    <span>Gestione Lavori</span>
                </a>
            </li>

            <!-- Preventivi Management -->
            <li>
                <a href="{{ route('preventivi.index') }}"
                   class="flex items-center px-4 py-3 text-white/90 rounded-lg hover:bg-white/10 hover:text-white transition-all duration-200 {{ request()->routeIs('preventivi.*') ? 'bg-white/20 text-white' : '' }}">
                    <i class="fas fa-file-invoice w-5 h-5 mr-3"></i>
                    <span>Preventivi</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- User Info at Bottom -->
    <div class="p-4 border-t border-white/10">
        <div class="flex items-center">
            <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center mr-3">
                @if(auth()->user()->profile_photo)
                    <img src="{{ auth()->user()->profile_photo_url }}" alt="Profile" class="w-full h-full rounded-full object-cover">
                @else
                    <i class="fas fa-user text-white"></i>
                @endif
            </div>
            <div class="flex-1">
                <p class="text-white font-medium text-sm">{{ auth()->user()->full_name }}</p>
                <p class="text-white/70 text-xs">{{ auth()->user()->getRoleNames()->first() }}</p>
            </div>
        </div>
    </div>
</div>
