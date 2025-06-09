@section('page-title', 'Dettagli Utente')

<x-app-layout>
    <x-slot name="header">
        Dettagli Utente: {{ $user->full_name }}
    </x-slot>

    <div class="space-y-6">
        <!-- User Information Card -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-semibold text-gray-900">Informazioni Utente</h2>
                    <div class="flex space-x-2">
                        <a href="{{ route('users.edit', $user) }}" 
                           class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg transition-colors duration-200">
                            <i class="fas fa-edit mr-2"></i>
                            Modifica
                        </a>
                        <a href="{{ route('users.index') }}" 
                           class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors duration-200">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Torna alla Lista
                        </a>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <div class="flex items-start space-x-6">
                    <!-- Profile Photo -->
                    <div class="flex-shrink-0">
                        <div class="w-24 h-24 rounded-full flex items-center justify-center" style="background-color: #007BCE;">
                            @if($user->profile_photo)
                                <img src="{{ $user->profile_photo_url }}" 
                                     alt="Profile" 
                                     class="w-full h-full rounded-full object-cover">
                            @else
                                <i class="fas fa-user text-white text-3xl"></i>
                            @endif
                        </div>
                    </div>

                    <!-- User Details -->
                    <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Nome Completo</label>
                                <p class="text-lg font-medium text-gray-900">{{ $user->full_name }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-500">Username</label>
                                <p class="text-gray-900">{{ $user->username }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-500">Email</label>
                                <p class="text-gray-900">
                                    <a href="mailto:{{ $user->email }}" style="color: #007BCE;" class="hover:opacity-80">
                                        {{ $user->email }}
                                    </a>
                                </p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Ruolo</label>
                                @foreach($user->roles as $role)
                                    <span class="inline-flex px-3 py-1 text-sm font-medium rounded-full bg-purple-100 text-purple-800">
                                        {{ $role->name }}
                                    </span>
                                @endforeach
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-500">Data Registrazione</label>
                                <p class="text-gray-900">{{ $user->created_at->format('d/m/Y H:i') }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-500">Ultimo Aggiornamento</label>
                                <p class="text-gray-900">{{ $user->updated_at->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Projects Section -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Progetti Assegnati</h3>
            </div>

            <div class="p-6">
                @if($user->projects->count() > 0)
                    <div class="space-y-4">
                        @foreach($user->projects as $project)
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="font-medium text-gray-900">{{ $project->name }}</h4>
                                        <p class="text-sm text-gray-600 mt-1">{{ $project->description }}</p>
                                        <div class="flex items-center space-x-4 mt-2 text-sm text-gray-500">
                                            <span>Cliente: {{ $project->client->full_name }}</span>
                                            <span>Inizio: {{ $project->start_date->format('d/m/Y') }}</span>
                                            @if($project->total_cost)
                                                <span>Costo: €{{ number_format($project->total_cost, 2) }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <span class="px-2 py-1 text-xs font-medium rounded-full
                                        @if($project->status === 'completed') bg-green-100 text-green-800
                                        @elseif($project->status === 'in_progress') bg-blue-100 text-blue-800
                                        @elseif($project->status === 'planning') bg-yellow-100 text-yellow-800
                                        @else bg-gray-100 text-gray-800 @endif">
                                        {{ App\Models\Project::getStatuses()[$project->status] ?? $project->status }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <i class="fas fa-project-diagram text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500">Nessun progetto assegnato a questo utente</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Payments Section -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Pagamenti Gestiti</h3>
            </div>

            <div class="p-6">
                @if($user->payments->count() > 0)
                    <div class="space-y-4">
                        @foreach($user->payments->take(5) as $payment)
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <h4 class="font-medium text-gray-900">{{ $payment->project->name }}</h4>
                                        <p class="text-sm text-gray-600">
                                            Cliente: {{ $payment->client->full_name }} | 
                                            Scadenza: {{ $payment->due_date->format('d/m/Y') }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-medium text-gray-900">€{{ number_format($payment->amount, 2) }}</p>
                                        <span class="px-2 py-1 text-xs font-medium rounded-full
                                            @if($payment->status === 'completed') bg-green-100 text-green-800
                                            @elseif($payment->status === 'overdue') bg-red-100 text-red-800
                                            @else bg-yellow-100 text-yellow-800 @endif">
                                            {{ App\Models\Payment::getStatuses()[$payment->status] ?? $payment->status }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        @if($user->payments->count() > 5)
                            <div class="text-center">
                                <a href="{{ route('payments.index', ['user_id' => $user->id]) }}" 
                                   class="text-blue-600 hover:text-blue-800">
                                    Vedi tutti i pagamenti ({{ $user->payments->count() }})
                                </a>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="text-center py-8">
                        <i class="fas fa-credit-card text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500">Nessun pagamento gestito da questo utente</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Expenses Section -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Spese Sostenute</h3>
            </div>

            <div class="p-6">
                @if($user->expenses->count() > 0)
                    <div class="space-y-4">
                        @foreach($user->expenses->take(5) as $expense)
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <h4 class="font-medium text-gray-900">{{ $expense->description }}</h4>
                                        <p class="text-sm text-gray-600">
                                            Data: {{ $expense->expense_date->format('d/m/Y') }}
                                            @if($expense->category)
                                                | Categoria: {{ App\Models\Expense::getCategories()[$expense->category] ?? $expense->category }}
                                            @endif
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-medium text-gray-900">€{{ number_format($expense->amount, 2) }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        @if($user->expenses->count() > 5)
                            <div class="text-center">
                                <a href="{{ route('expenses.index', ['user_id' => $user->id]) }}" 
                                   class="text-blue-600 hover:text-blue-800">
                                    Vedi tutte le spese ({{ $user->expenses->count() }})
                                </a>
                            </div>
                        @endif
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <p class="text-sm text-gray-600">
                            Totale spese: <strong>€{{ number_format($user->expenses->sum('amount'), 2) }}</strong>
                        </p>
                    </div>
                @else
                    <div class="text-center py-8">
                        <i class="fas fa-receipt text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500">Nessuna spesa sostenuta da questo utente</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
