@section('page-title', 'Dettagli Cliente')

<x-app-layout>
    <x-slot name="header">
        Dettagli Cliente: {{ $client->full_name }}
    </x-slot>

    <div class="space-y-6">
        <!-- Client Information Card -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-semibold text-gray-900">Informazioni Cliente</h2>
                    <div class="flex space-x-2">
                        <a href="{{ route('clients.edit', $client) }}" 
                           class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg transition-colors duration-200">
                            <i class="fas fa-edit mr-2"></i>
                            Modifica
                        </a>
                        <a href="{{ route('clients.index') }}" 
                           class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors duration-200">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Torna alla Lista
                        </a>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Personal Information -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Nome Completo</label>
                            <p class="text-lg font-medium text-gray-900">{{ $client->full_name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-500">Email</label>
                            <p class="text-gray-900">
                                <a href="mailto:{{ $client->email }}" style="color: #007BCE;" class="hover:opacity-80">
                                    {{ $client->email }}
                                </a>
                            </p>
                        </div>

                        @if($client->phone)
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Telefono</label>
                            <p class="text-gray-900">
                                <a href="tel:{{ $client->phone }}" style="color: #007BCE;" class="hover:opacity-80">
                                    {{ $client->phone }}
                                </a>
                            </p>
                        </div>
                        @endif

                        <div>
                            <label class="block text-sm font-medium text-gray-500">Tipo di Entità</label>
                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full
                                @if($client->entity_type === 'business') bg-blue-100 text-blue-800
                                @else bg-green-100 text-green-800 @endif">
                                {{ $client->entity_type === 'business' ? 'Azienda' : 'Privato' }}
                            </span>
                        </div>
                    </div>

                    <!-- Tax and Legal Information -->
                    <div class="space-y-4">
                        @if($client->tax_code)
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Codice Fiscale</label>
                            <p class="text-gray-900 font-mono">{{ $client->tax_code }}</p>
                        </div>
                        @endif

                        @if($client->vat_number)
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Partita IVA</label>
                            <p class="text-gray-900 font-mono">{{ $client->vat_number }}</p>
                        </div>
                        @endif

                        @if($client->address)
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Indirizzo</label>
                            <p class="text-gray-900">{{ $client->address }}</p>
                        </div>
                        @endif

                        <div>
                            <label class="block text-sm font-medium text-gray-500">Data Registrazione</label>
                            <p class="text-gray-900">{{ $client->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Projects Section -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900">Progetti</h3>
                    @can('manage projects')
                    <a href="{{ route('projects.create', ['client_id' => $client->id]) }}"
                       class="px-4 py-2 text-white rounded-lg transition-colors duration-200"
                       style="background-color: #007BCE;"
                       onmouseover="this.style.backgroundColor='#005B99'"
                       onmouseout="this.style.backgroundColor='#007BCE'">
                        <i class="fas fa-plus mr-2"></i>
                        Nuovo Progetto
                    </a>
                    @endcan
                </div>
            </div>

            <div class="p-6">
                @if($client->projects->count() > 0)
                    <div class="space-y-4">
                        @foreach($client->projects as $project)
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="font-medium text-gray-900">{{ $project->name }}</h4>
                                        <p class="text-sm text-gray-600 mt-1">{{ $project->description }}</p>
                                        <div class="flex items-center space-x-4 mt-2 text-sm text-gray-500">
                                            <span>Tipo: {{ $project->project_type }}</span>
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
                                        {{ ucfirst($project->status) }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <i class="fas fa-project-diagram text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500">Nessun progetto associato a questo cliente</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Payments Section -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Pagamenti</h3>
            </div>

            <div class="p-6">
                @if($client->payments->count() > 0)
                    <div class="space-y-4">
                        @foreach($client->payments as $payment)
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <h4 class="font-medium text-gray-900">{{ $payment->project->name }}</h4>
                                        <p class="text-sm text-gray-600">
                                            Scadenza: {{ $payment->due_date->format('d/m/Y') }}
                                            @if($payment->paid_date)
                                                | Pagato: {{ $payment->paid_date->format('d/m/Y') }}
                                            @endif
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-medium text-gray-900">€{{ number_format($payment->amount, 2) }}</p>
                                        <span class="px-2 py-1 text-xs font-medium rounded-full
                                            @if($payment->status === 'completed') bg-green-100 text-green-800
                                            @elseif($payment->status === 'overdue') bg-red-100 text-red-800
                                            @else bg-yellow-100 text-yellow-800 @endif">
                                            {{ ucfirst($payment->status) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <i class="fas fa-credit-card text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500">Nessun pagamento associato a questo cliente</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
