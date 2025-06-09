@section('page-title', 'Dashboard')

<x-app-layout>
    <x-slot name="header">
        Dashboard
    </x-slot>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Users -->
        <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <i class="fas fa-users text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Utenti Totali</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['total_users'] }}</p>
                </div>
            </div>
        </div>

        <!-- Total Clients -->
        <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <i class="fas fa-address-book text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Clienti Totali</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['total_clients'] }}</p>
                </div>
            </div>
        </div>

        <!-- Active Projects -->
        <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                    <i class="fas fa-project-diagram text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Progetti Attivi</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['active_projects'] }}</p>
                </div>
            </div>
        </div>

        <!-- Pending Payments -->
        <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-red-100 text-red-600">
                    <i class="fas fa-credit-card text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Pagamenti in Attesa</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['pending_payments'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue and Expenses -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Monthly Revenue -->
        <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Entrate Questo Mese</h3>
            <div class="text-3xl font-bold text-green-600">€{{ number_format($stats['total_revenue_this_month'], 2) }}</div>
            <p class="text-sm text-gray-600 mt-2">Pagamenti completati</p>
        </div>

        <!-- Monthly Expenses -->
        <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Spese Questo Mese</h3>
            <div class="text-3xl font-bold text-red-600">€{{ number_format($stats['total_expenses_this_month'], 2) }}</div>
            <p class="text-sm text-gray-600 mt-2">Spese sostenute</p>
        </div>
    </div>

    <!-- Recent Projects and Payments -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Projects -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Progetti Recenti</h3>
            </div>
            <div class="p-6">
                @if($recent_projects->count() > 0)
                    <div class="space-y-4">
                        @foreach($recent_projects as $project)
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                <div>
                                    <h4 class="font-medium text-gray-900">{{ $project->name }}</h4>
                                    <p class="text-sm text-gray-600">{{ $project->client->full_name }}</p>
                                    <p class="text-xs text-gray-500">{{ $project->created_at->format('d/m/Y') }}</p>
                                </div>
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    @if($project->status === 'completed') bg-green-100 text-green-800
                                    @elseif($project->status === 'in_progress') bg-blue-100 text-blue-800
                                    @elseif($project->status === 'planning') bg-yellow-100 text-yellow-800
                                    @else bg-gray-100 text-gray-800 @endif">
                                    {{ ucfirst($project->status) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 text-center py-8">Nessun progetto recente</p>
                @endif
            </div>
        </div>

        <!-- Upcoming Payments -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Pagamenti in Scadenza</h3>
            </div>
            <div class="p-6">
                @if($upcoming_payments->count() > 0)
                    <div class="space-y-4">
                        @foreach($upcoming_payments as $payment)
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                <div>
                                    <h4 class="font-medium text-gray-900">{{ $payment->project->name }}</h4>
                                    <p class="text-sm text-gray-600">{{ $payment->client->full_name }}</p>
                                    <p class="text-xs text-gray-500">Scadenza: {{ $payment->due_date->format('d/m/Y') }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-medium text-gray-900">€{{ number_format($payment->amount, 2) }}</p>
                                    @if($payment->due_date->isPast())
                                        <span class="text-xs text-red-600">Scaduto</span>
                                    @else
                                        <span class="text-xs text-green-600">{{ $payment->due_date->diffForHumans() }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 text-center py-8">Nessun pagamento in scadenza</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
