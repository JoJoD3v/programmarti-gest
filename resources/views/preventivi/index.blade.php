@section('page-title', 'Gestione Preventivi')

<x-app-layout>
    <x-slot name="header">
        Gestione Preventivi
    </x-slot>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <!-- Header with Search and Add Button -->
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">Lista Preventivi</h2>
                    @if(request()->hasAny(['search', 'client_id', 'status']))
                        <div class="flex items-center mt-2 text-sm text-gray-600">
                            <i class="fas fa-filter mr-2"></i>
                            <span>Filtri attivi:</span>
                            @if(request('search'))
                                <span class="ml-2 px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs">
                                    Ricerca: "{{ request('search') }}"
                                </span>
                            @endif
                            @if(request('client_id'))
                                <span class="ml-2 px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">
                                    Cliente selezionato
                                </span>
                            @endif
                            @if(request('status'))
                                <span class="ml-2 px-2 py-1 bg-orange-100 text-orange-800 rounded-full text-xs">
                                    Stato: {{ \App\Models\Preventivo::getStatuses()[request('status')] ?? request('status') }}
                                </span>
                            @endif
                        </div>
                    @endif
                </div>
                <a href="{{ route('preventivi.create') }}"
                   class="text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200 hover:opacity-90"
                   style="background-color: #007BCE;"
                   onmouseover="this.style.backgroundColor='#005B99'"
                   onmouseout="this.style.backgroundColor='#007BCE'">
                    <i class="fas fa-plus mr-2"></i>
                    Nuovo Preventivo
                </a>
            </div>

            <!-- Search and Filters -->
            <form method="GET" class="flex flex-wrap gap-4" id="preventiviFilterForm">
                <div class="flex-1 min-w-64">
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Cerca per numero preventivo, cliente o progetto..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <select name="client_id"
                            id="clientFilter"
                            class="select-improved px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Tutti i clienti</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>
                                {{ $client->full_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select name="status"
                            id="statusFilter"
                            class="select-improved px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Tutti gli stati</option>
                        @foreach(\App\Models\Preventivo::getStatuses() as $value => $label)
                            <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                    <i class="fas fa-search mr-2"></i>Cerca
                </button>
                <a href="{{ route('preventivi.index') }}" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">
                    Reset
                </a>
            </form>
        </div>

        <!-- Preventivi Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Numero Preventivo
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Cliente
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Progetto
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Importo Totale
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Stato
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Data Creazione
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Azioni
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($preventivi as $preventivo)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $preventivo->quote_number }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $preventivo->client->full_name }}</div>
                                <div class="text-sm text-gray-500">{{ $preventivo->client->email }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $preventivo->project->name }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                €{{ number_format($preventivo->total_amount, 2, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-medium rounded-full {{ $preventivo->status_color }}">
                                    {{ $preventivo->status_label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $preventivo->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end space-x-2">
                                    <a href="{{ route('preventivi.show', $preventivo) }}" 
                                       class="text-blue-600 hover:text-blue-900" title="Visualizza">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('preventivi.edit', $preventivo) }}" 
                                       class="text-yellow-600 hover:text-yellow-900" title="Modifica">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @if($preventivo->pdf_path)
                                        <a href="{{ route('preventivi.download-pdf', $preventivo) }}" 
                                           class="text-green-600 hover:text-green-900" title="Scarica PDF">
                                            <i class="fas fa-download"></i>
                                        </a>
                                    @endif
                                    <form action="{{ route('preventivi.destroy', $preventivo) }}" 
                                          method="POST" 
                                          class="inline"
                                          onsubmit="return confirm('Sei sicuro di voler eliminare questo preventivo?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900" title="Elimina">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-file-invoice text-4xl mb-4 text-gray-300"></i>
                                <p class="text-lg">Nessun preventivo trovato</p>
                                <p class="text-sm">Inizia creando il tuo primo preventivo</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($preventivi->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $preventivi->appends(request()->query())->links('pagination.custom') }}
            </div>
        @endif
    </div>

    <!-- Auto-refresh JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('preventiviFilterForm');
            const clientFilter = document.getElementById('clientFilter');
            const statusFilter = document.getElementById('statusFilter');
            const searchInput = document.querySelector('input[name="search"]');
            const submitButton = form.querySelector('button[type="submit"]');

            // Function to show loading state
            function showLoading() {
                if (submitButton) {
                    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Caricamento...';
                    submitButton.disabled = true;
                }
            }

            // Function to submit form automatically
            function autoSubmitForm() {
                showLoading();
                form.submit();
            }

            // Add change event listeners to dropdown filters
            clientFilter.addEventListener('change', function() {
                console.log('Client filter changed to:', this.value);
                autoSubmitForm();
            });

            statusFilter.addEventListener('change', function() {
                console.log('Status filter changed to:', this.value);
                autoSubmitForm();
            });

            // Optional: Add debounced search for text input
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    if (searchInput.value.length >= 3 || searchInput.value.length === 0) {
                        console.log('Search triggered with:', searchInput.value);
                        autoSubmitForm();
                    }
                }, 500); // 500ms delay for search input
            });

            // Prevent double submission on manual form submit
            form.addEventListener('submit', function(e) {
                clearTimeout(searchTimeout);
                showLoading();
            });

            // Reset button functionality
            const resetButton = document.querySelector('a[href*="preventivi.index"]');
            if (resetButton) {
                resetButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    form.reset();
                    window.location.href = this.href;
                });
            }
        });
    </script>
</x-app-layout>
