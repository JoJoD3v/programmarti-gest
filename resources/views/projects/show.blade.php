@section('page-title', 'Dettagli Progetto')

<x-app-layout>
    <x-slot name="header">
        Dettagli Progetto: {{ $project->name }}
    </x-slot>

    <div class="space-y-6">
        <!-- Project Information Card -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-semibold text-gray-900">Informazioni Progetto</h2>
                    <div class="flex space-x-2">
                        @if($project->payment_type === 'installments' && $project->payments->isEmpty())
                            <form action="{{ route('projects.generate-payments', $project) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" 
                                        class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors duration-200">
                                    <i class="fas fa-credit-card mr-2"></i>
                                    Genera Pagamenti
                                </button>
                            </form>
                        @endif
                        <a href="{{ route('projects.edit', $project) }}" 
                           class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg transition-colors duration-200">
                            <i class="fas fa-edit mr-2"></i>
                            Modifica
                        </a>
                        <a href="{{ route('projects.index') }}" 
                           class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors duration-200">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Torna alla Lista
                        </a>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Project Details -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Nome Progetto</label>
                            <p class="text-lg font-medium text-gray-900">{{ $project->name }}</p>
                        </div>

                        @if($project->description)
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Descrizione</label>
                            <p class="text-gray-900">{{ $project->description }}</p>
                        </div>
                        @endif

                        <div>
                            <label class="block text-sm font-medium text-gray-500">Tipo Progetto</label>
                            <span class="inline-flex px-3 py-1 text-sm font-medium rounded-full bg-blue-100 text-blue-800">
                                {{ App\Models\Project::getProjectTypes()[$project->project_type] ?? $project->project_type }}
                            </span>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-500">Cliente</label>
                            <p class="text-gray-900">
                                <a href="{{ route('clients.show', $project->client) }}" style="color: #007BCE;" class="hover:opacity-80">
                                    {{ $project->client->full_name }}
                                </a>
                            </p>
                            <p class="text-sm text-gray-600">{{ $project->client->email }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-500">Assegnato a</label>
                            <p class="text-gray-900">
                                @if($project->assignedUser)
                                    <a href="{{ route('users.show', $project->assignedUser) }}" style="color: #007BCE;" class="hover:opacity-80">
                                        {{ $project->assignedUser->full_name }}
                                    </a>
                                    <span class="text-sm text-gray-600">({{ $project->assignedUser->getRoleNames()->first() }})</span>
                                @else
                                    <span class="text-gray-500">Non assegnato</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    <!-- Project Status and Dates -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Stato</label>
                            <span class="inline-flex px-3 py-1 text-sm font-medium rounded-full
                                @if($project->status === 'completed') bg-green-100 text-green-800
                                @elseif($project->status === 'in_progress') bg-blue-100 text-blue-800
                                @elseif($project->status === 'planning') bg-yellow-100 text-yellow-800
                                @else bg-gray-100 text-gray-800 @endif">
                                {{ App\Models\Project::getStatuses()[$project->status] ?? $project->status }}
                            </span>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-500">Data Inizio</label>
                            <p class="text-gray-900">{{ $project->start_date->format('d/m/Y') }}</p>
                        </div>

                        @if($project->end_date)
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Data Fine Prevista</label>
                            <p class="text-gray-900">{{ $project->end_date->format('d/m/Y') }}</p>
                        </div>
                        @endif

                        @if($project->total_cost)
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Costo Totale</label>
                            <p class="text-lg font-medium text-gray-900">€{{ number_format($project->total_cost, 2) }}</p>
                        </div>
                        @endif

                        <div>
                            <label class="block text-sm font-medium text-gray-500">Tipo Pagamento</label>
                            <span class="inline-flex px-3 py-1 text-sm font-medium rounded-full bg-purple-100 text-purple-800">
                                {{ $project->payment_type === 'one_time' ? 'Pagamento Unico' : 'Rate' }}
                            </span>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-500">Data Creazione</label>
                            <p class="text-gray-900">{{ $project->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Payment Structure Details -->
                @if($project->payment_type === 'installments')
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Struttura Pagamenti</h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        @if($project->has_down_payment && $project->down_payment_amount)
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Acconto</label>
                            <p class="text-gray-900">€{{ number_format($project->down_payment_amount, 2) }}</p>
                        </div>
                        @endif

                        @if($project->payment_frequency)
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Frequenza</label>
                            <p class="text-gray-900">{{ App\Models\Project::getPaymentFrequencies()[$project->payment_frequency] ?? $project->payment_frequency }}</p>
                        </div>
                        @endif

                        @if($project->installment_amount)
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Importo Rata</label>
                            <p class="text-gray-900">€{{ number_format($project->installment_amount, 2) }}</p>
                        </div>
                        @endif

                        @if($project->installment_count)
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Numero Rate</label>
                            <p class="text-gray-900">{{ $project->installment_count }}</p>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Payments Section -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900">Pagamenti</h3>
                    @if($project->payments->count() > 0)
                        <div class="text-sm text-gray-600">
                            Totale: €{{ number_format($project->payments->sum('amount'), 2) }} | 
                            Completati: €{{ number_format($project->payments->where('status', 'completed')->sum('amount'), 2) }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="p-6">
                @if($project->payments->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Tipo
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Importo
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Scadenza
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Stato
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Azioni
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($project->payments->sortBy('due_date') as $payment)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                                {{ App\Models\Payment::getPaymentTypes()[$payment->payment_type] ?? $payment->payment_type }}
                                                @if($payment->installment_number)
                                                    #{{ $payment->installment_number }}
                                                @endif
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">€{{ number_format($payment->amount, 2) }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ $payment->due_date->format('d/m/Y') }}</div>
                                            @if($payment->paid_date)
                                                <div class="text-sm text-green-600">Pagato: {{ $payment->paid_date->format('d/m/Y') }}</div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 text-xs font-medium rounded-full
                                                @if($payment->status === 'completed') bg-green-100 text-green-800
                                                @elseif($payment->status === 'overdue' || $payment->isOverdue()) bg-red-100 text-red-800
                                                @else bg-yellow-100 text-yellow-800 @endif">
                                                @if($payment->isOverdue() && $payment->status === 'pending')
                                                    Scaduto
                                                @else
                                                    {{ App\Models\Payment::getStatuses()[$payment->status] ?? $payment->status }}
                                                @endif
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex justify-end space-x-2">
                                                @if($payment->status === 'pending')
                                                    @can('manage payments')
                                                    <form action="{{ route('payments.mark-completed', $payment) }}" 
                                                          method="POST" 
                                                          class="inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" 
                                                                class="text-green-600 hover:text-green-900"
                                                                title="Segna come completato">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                    @endcan
                                                @endif
                                                <a href="{{ route('payments.show', $payment) }}" 
                                                   class="text-blue-600 hover:text-blue-900">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-8">
                        <i class="fas fa-credit-card text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500">Nessun pagamento associato a questo progetto</p>
                        @if($project->payment_type === 'installments')
                            <form action="{{ route('projects.generate-payments', $project) }}" method="POST" class="inline mt-4">
                                @csrf
                                <button type="submit" 
                                        class="px-4 py-2 text-white rounded-lg transition-colors duration-200"
                                        style="background-color: #007BCE;"
                                        onmouseover="this.style.backgroundColor='#005B99'"
                                        onmouseout="this.style.backgroundColor='#007BCE'">
                                    <i class="fas fa-credit-card mr-2"></i>
                                    Genera Pagamenti
                                </button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
