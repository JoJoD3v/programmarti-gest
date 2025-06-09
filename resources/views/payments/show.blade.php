@section('page-title', 'Dettagli Pagamento')

<x-app-layout>
    <x-slot name="header">
        Dettagli Pagamento #{{ $payment->id }}
    </x-slot>

    <div class="max-w-4xl mx-auto space-y-6">
        <!-- Payment Information Card -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-semibold text-gray-900">Informazioni Pagamento</h2>
                    <div class="flex space-x-2">
                        @if($payment->status === 'completed')
                            @can('generate invoices')
                            <a href="{{ route('payments.invoice', $payment) }}" 
                               class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors duration-200">
                                <i class="fas fa-file-pdf mr-2"></i>
                                Scarica Fattura PDF
                            </a>
                            @endcan
                            @can('send emails')
                            <form action="{{ route('payments.send-invoice', $payment) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" 
                                        class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors duration-200">
                                    <i class="fas fa-envelope mr-2"></i>
                                    Invia via Email
                                </button>
                            </form>
                            @endcan
                        @endif
                        @if($payment->status === 'pending')
                            @can('manage payments')
                            <form action="{{ route('payments.mark-completed', $payment) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" 
                                        class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors duration-200">
                                    <i class="fas fa-check mr-2"></i>
                                    Segna come Completato
                                </button>
                            </form>
                            @endcan
                        @endif
                        <a href="{{ route('payments.edit', $payment) }}" 
                           class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg transition-colors duration-200">
                            <i class="fas fa-edit mr-2"></i>
                            Modifica
                        </a>
                        <a href="{{ route('payments.index') }}" 
                           class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors duration-200">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Torna alla Lista
                        </a>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Payment Details -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Importo</label>
                            <p class="text-2xl font-bold text-gray-900">€{{ number_format($payment->amount, 2) }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-500">Stato</label>
                            <span class="inline-flex px-3 py-1 text-sm font-medium rounded-full
                                @if($payment->status === 'completed') bg-green-100 text-green-800
                                @elseif($payment->status === 'overdue' || $payment->isOverdue()) bg-red-100 text-red-800
                                @else bg-yellow-100 text-yellow-800 @endif">
                                @if($payment->isOverdue() && $payment->status === 'pending')
                                    Scaduto
                                @else
                                    {{ App\Models\Payment::getStatuses()[$payment->status] ?? $payment->status }}
                                @endif
                            </span>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-500">Tipo Pagamento</label>
                            <span class="inline-flex px-3 py-1 text-sm font-medium rounded-full bg-blue-100 text-blue-800">
                                {{ App\Models\Payment::getPaymentTypes()[$payment->payment_type] ?? $payment->payment_type }}
                                @if($payment->installment_number)
                                    #{{ $payment->installment_number }}
                                @endif
                            </span>
                        </div>

                        @if($payment->invoice_number)
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Numero Fattura</label>
                            <p class="text-gray-900 font-mono">{{ $payment->invoice_number }}</p>
                        </div>
                        @endif
                    </div>

                    <!-- Dates and Assignment -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Data Scadenza</label>
                            <p class="text-gray-900">{{ $payment->due_date->format('d/m/Y') }}</p>
                            @if($payment->due_date->isPast() && $payment->status === 'pending')
                                <p class="text-sm text-red-600">Scaduto da {{ $payment->due_date->diffForHumans() }}</p>
                            @elseif($payment->status === 'pending')
                                <p class="text-sm text-green-600">{{ $payment->due_date->diffForHumans() }}</p>
                            @endif
                        </div>

                        @if($payment->paid_date)
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Data Pagamento</label>
                            <p class="text-gray-900">{{ $payment->paid_date->format('d/m/Y H:i') }}</p>
                        </div>
                        @endif

                        @if($payment->assignedUser)
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Assegnato a</label>
                            <div class="flex items-center mt-2">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center mr-3" style="background-color: #007BCE;">
                                    @if($payment->assignedUser->profile_photo)
                                        <img src="{{ $payment->assignedUser->profile_photo_url }}" 
                                             alt="Profile" 
                                             class="w-full h-full rounded-full object-cover">
                                    @else
                                        <i class="fas fa-user text-white text-xs"></i>
                                    @endif
                                </div>
                                <div>
                                    <p class="text-gray-900 font-medium">
                                        <a href="{{ route('users.show', $payment->assignedUser) }}" style="color: #007BCE;" class="hover:opacity-80">
                                            {{ $payment->assignedUser->full_name }}
                                        </a>
                                    </p>
                                </div>
                            </div>
                        </div>
                        @endif

                        <div>
                            <label class="block text-sm font-medium text-gray-500">Data Creazione</label>
                            <p class="text-gray-900">{{ $payment->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                </div>

                @if($payment->notes)
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <label class="block text-sm font-medium text-gray-500 mb-2">Note</label>
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                        <p class="text-gray-900">{{ $payment->notes }}</p>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Project Information -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Progetto Associato</h3>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Nome Progetto</label>
                            <p class="text-lg font-medium text-gray-900">
                                <a href="{{ route('projects.show', $payment->project) }}" style="color: #007BCE;" class="hover:opacity-80">
                                    {{ $payment->project->name }}
                                </a>
                            </p>
                        </div>

                        @if($payment->project->description)
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Descrizione</label>
                            <p class="text-gray-900">{{ $payment->project->description }}</p>
                        </div>
                        @endif

                        <div>
                            <label class="block text-sm font-medium text-gray-500">Tipo Progetto</label>
                            <span class="inline-flex px-3 py-1 text-sm font-medium rounded-full bg-blue-100 text-blue-800">
                                {{ App\Models\Project::getProjectTypes()[$payment->project->project_type] ?? $payment->project->project_type }}
                            </span>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Stato Progetto</label>
                            <span class="inline-flex px-3 py-1 text-sm font-medium rounded-full
                                @if($payment->project->status === 'completed') bg-green-100 text-green-800
                                @elseif($payment->project->status === 'in_progress') bg-blue-100 text-blue-800
                                @elseif($payment->project->status === 'planning') bg-yellow-100 text-yellow-800
                                @else bg-gray-100 text-gray-800 @endif">
                                {{ App\Models\Project::getStatuses()[$payment->project->status] ?? $payment->project->status }}
                            </span>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-500">Data Inizio</label>
                            <p class="text-gray-900">{{ $payment->project->start_date->format('d/m/Y') }}</p>
                        </div>

                        @if($payment->project->total_cost)
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Costo Totale Progetto</label>
                            <p class="text-gray-900">€{{ number_format($payment->project->total_cost, 2) }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Client Information -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Cliente</h3>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Nome Cliente</label>
                            <p class="text-lg font-medium text-gray-900">
                                <a href="{{ route('clients.show', $payment->client) }}" style="color: #007BCE;" class="hover:opacity-80">
                                    {{ $payment->client->full_name }}
                                </a>
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-500">Email</label>
                            <p class="text-gray-900">
                                <a href="mailto:{{ $payment->client->email }}" style="color: #007BCE;" class="hover:opacity-80">
                                    {{ $payment->client->email }}
                                </a>
                            </p>
                        </div>

                        @if($payment->client->phone)
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Telefono</label>
                            <p class="text-gray-900">
                                <a href="tel:{{ $payment->client->phone }}" style="color: #007BCE;" class="hover:opacity-80">
                                    {{ $payment->client->phone }}
                                </a>
                            </p>
                        </div>
                        @endif
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Tipo Entità</label>
                            <span class="inline-flex px-3 py-1 text-sm font-medium rounded-full
                                @if($payment->client->entity_type === 'business') bg-blue-100 text-blue-800
                                @else bg-green-100 text-green-800 @endif">
                                {{ $payment->client->entity_type === 'business' ? 'Azienda' : 'Privato' }}
                            </span>
                        </div>

                        @if($payment->client->vat_number)
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Partita IVA</label>
                            <p class="text-gray-900 font-mono">{{ $payment->client->vat_number }}</p>
                        </div>
                        @endif

                        @if($payment->client->tax_code)
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Codice Fiscale</label>
                            <p class="text-gray-900 font-mono">{{ $payment->client->tax_code }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
