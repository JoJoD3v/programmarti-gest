@section('page-title', 'Gestione Lavori')

<x-app-layout>
    <x-slot name="header">
        Gestione Lavori
    </x-slot>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <!-- Header with Search and Add Button -->
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">Lista Lavori</h2>
                    @if(request()->hasAny(['search', 'project_id', 'status', 'type']))
                        <div class="flex items-center mt-2 text-sm text-gray-600">
                            <i class="fas fa-filter mr-2"></i>
                            <span>Filtri attivi:</span>
                            @if(request('search'))
                                <span class="ml-2 px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs">
                                    Ricerca: "{{ request('search') }}"
                                </span>
                            @endif
                            @if(request('project_id'))
                                <span class="ml-2 px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">
                                    Progetto selezionato
                                </span>
                            @endif
                            @if(request('status'))
                                <span class="ml-2 px-2 py-1 bg-orange-100 text-orange-800 rounded-full text-xs">
                                    Stato: {{ request('status') }}
                                </span>
                            @endif
                            @if(request('type'))
                                <span class="ml-2 px-2 py-1 bg-purple-100 text-purple-800 rounded-full text-xs">
                                    Tipo: {{ request('type') }}
                                </span>
                            @endif
                        </div>
                    @endif
                </div>
                <a href="{{ route('works.create') }}"
                   class="text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200 hover:opacity-90"
                   style="background-color: #007BCE;"
                   onmouseover="this.style.backgroundColor='#005B99'"
                   onmouseout="this.style.backgroundColor='#007BCE'">
                    <i class="fas fa-plus mr-2"></i>
                    Aggiungi Lavoro
                </a>
            </div>

            <!-- Search and Filters -->
            <form method="GET" class="flex flex-wrap gap-4" id="worksFilterForm">
                <div class="flex-1 min-w-64">
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Cerca per nome lavoro, progetto o dipendente..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <select name="project_id"
                            id="projectFilter"
                            class="select-improved px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Tutti i progetti</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}" {{ request('project_id') == $project->id ? 'selected' : '' }}>
                                {{ $project->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select name="status"
                            id="statusFilter"
                            class="select-improved px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Tutti gli stati</option>
                        <option value="In Sospeso" {{ request('status') === 'In Sospeso' ? 'selected' : '' }}>In Sospeso</option>
                        <option value="Completato" {{ request('status') === 'Completato' ? 'selected' : '' }}>Completato</option>
                    </select>
                </div>
                <div>
                    <select name="type"
                            id="typeFilter"
                            class="select-improved px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Tutti i tipi</option>
                        <option value="Bug" {{ request('type') === 'Bug' ? 'selected' : '' }}>Bug</option>
                        <option value="Miglioramenti" {{ request('type') === 'Miglioramenti' ? 'selected' : '' }}>Miglioramenti</option>
                        <option value="Da fare" {{ request('type') === 'Da fare' ? 'selected' : '' }}>Da fare</option>
                    </select>
                </div>
                <button type="submit" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                    <i class="fas fa-search mr-2"></i>Cerca
                </button>
                <a href="{{ route('works.index') }}" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">
                    Reset
                </a>
            </form>
        </div>

        <!-- Works Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Nome Lavoro
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Progetto
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Tipo
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Assegnato a
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
                    @forelse($works as $work)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $work->name }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $work->project->name }}</div>
                                <div class="text-sm text-gray-500">{{ $work->project->client->full_name }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    @if($work->type === 'Bug') bg-red-100 text-red-800
                                    @elseif($work->type === 'Miglioramenti') bg-blue-100 text-blue-800
                                    @else bg-yellow-100 text-yellow-800 @endif">
                                    {{ $work->type }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $work->assignedUser->full_name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    @if($work->status === 'Completato') bg-green-100 text-green-800
                                    @else bg-orange-100 text-orange-800 @endif">
                                    {{ $work->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $work->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end space-x-2">
                                    @if($work->status === 'In Sospeso')
                                        <form action="{{ route('works.mark-completed', $work) }}" 
                                              method="POST" 
                                              class="inline"
                                              onsubmit="return confirm('Sei sicuro di voler contrassegnare questo lavoro come completato?')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-green-600 hover:text-green-900" title="Segna come completato">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                    @endif
                                    <a href="{{ route('works.show', $work) }}" 
                                       class="text-blue-600 hover:text-blue-900" title="Visualizza">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('works.edit', $work) }}" 
                                       class="text-yellow-600 hover:text-yellow-900" title="Modifica">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('works.destroy', $work) }}" 
                                          method="POST" 
                                          class="inline"
                                          onsubmit="return confirm('Sei sicuro di voler eliminare questo lavoro?')">
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
                                <i class="fas fa-tasks text-4xl mb-4 text-gray-300"></i>
                                <p class="text-lg">Nessun lavoro trovato</p>
                                <p class="text-sm">Inizia aggiungendo il tuo primo lavoro</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($works->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $works->appends(request()->query())->links('pagination.custom') }}
            </div>
        @endif
    </div>

    <!-- Auto-refresh JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('worksFilterForm');
            const projectFilter = document.getElementById('projectFilter');
            const statusFilter = document.getElementById('statusFilter');
            const typeFilter = document.getElementById('typeFilter');
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
            projectFilter.addEventListener('change', function() {
                console.log('Project filter changed to:', this.value);
                autoSubmitForm();
            });

            statusFilter.addEventListener('change', function() {
                console.log('Status filter changed to:', this.value);
                autoSubmitForm();
            });

            typeFilter.addEventListener('change', function() {
                console.log('Type filter changed to:', this.value);
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
                // Clear any pending search timeout
                clearTimeout(searchTimeout);
                showLoading();
            });

            // Reset button functionality
            const resetButton = document.querySelector('a[href*="works.index"]');
            if (resetButton) {
                resetButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    // Clear all form fields
                    form.reset();
                    // Navigate to clean URL
                    window.location.href = this.href;
                });
            }
        });
    </script>
</x-app-layout>
