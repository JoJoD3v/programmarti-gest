@section('page-title', 'Gestione Appuntamenti')

<x-app-layout>
    <x-slot name="header">
        Gestione Appuntamenti
    </x-slot>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <!-- Header with Filters and Add Button -->
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-900">Lista Appuntamenti</h2>
                <a href="{{ route('appointments.create') }}" 
                   class="text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200 hover:opacity-90"
                   style="background-color: #007BCE;"
                   onmouseover="this.style.backgroundColor='#005B99'"
                   onmouseout="this.style.backgroundColor='#007BCE'">
                    <i class="fas fa-plus mr-2"></i>
                    Nuovo Appuntamento
                </a>
            </div>

            <!-- Filters -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="date-filter" class="block text-sm font-medium text-gray-700 mb-1">
                        Filtra per Data
                    </label>
                    <input type="date" 
                           id="date-filter" 
                           value="{{ $filterDate }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label for="client-filter" class="block text-sm font-medium text-gray-700 mb-1">
                        Filtra per Cliente
                    </label>
                    <select id="client-filter" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Tutti i clienti</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" {{ $filterClient == $client->id ? 'selected' : '' }}>
                                {{ $client->full_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end space-x-2">
                    <button type="button"
                            id="show-all"
                            class="px-4 py-2 text-blue-600 border border-blue-300 rounded-lg hover:bg-blue-50 transition-colors duration-200">
                        <i class="fas fa-list mr-2"></i>
                        Mostra Tutti
                    </button>
                    <button type="button"
                            id="clear-filters"
                            class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-200">
                        <i class="fas fa-times mr-2"></i>
                        Pulisci Filtri
                    </button>
                </div>
            </div>
        </div>

        <!-- Loading Indicator (copied from payments) -->
        <div id="loading-indicator" class="hidden items-center justify-center p-8">
            <div class="flex items-center space-x-2">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
                <span class="text-gray-600">Caricamento...</span>
            </div>
        </div>

        <!-- Appointments Table -->
        <div id="appointments-table">
            @include('appointments.partials.table', ['appointments' => $appointments])
        </div>
    </div>

    <!-- JavaScript for Dynamic Filters (copied from payments pattern) -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dateFilter = document.getElementById('date-filter');
            const clientFilter = document.getElementById('client-filter');
            const clearFilters = document.getElementById('clear-filters');
            const showAll = document.getElementById('show-all');
            const loadingIndicator = document.getElementById('loading-indicator');
            const appointmentsTable = document.getElementById('appointments-table');

            // Function to show loading state
            function showLoading() {
                loadingIndicator.classList.remove('hidden');
                loadingIndicator.classList.add('flex');
                appointmentsTable.style.opacity = '0.5';
            }

            // Function to hide loading state
            function hideLoading() {
                loadingIndicator.classList.add('hidden');
                loadingIndicator.classList.remove('flex');
                appointmentsTable.style.opacity = '1';
            }

            // Function to apply filters (copied from payments)
            function applyFilters() {
                showLoading();

                const params = new URLSearchParams();

                // Only add parameters if they have values
                if (dateFilter.value !== '') {
                    params.append('date', dateFilter.value);
                }
                if (clientFilter.value !== '') {
                    params.append('client_id', clientFilter.value);
                }

                // Update URL without page reload
                const newUrl = `${window.location.pathname}${params.toString() ? '?' + params.toString() : ''}`;
                window.history.pushState({}, '', newUrl);

                // Make AJAX request (using the same pattern as payments)
                const filterUrl = `{{ route('appointments.filter') }}${params.toString() ? '?' + params.toString() : ''}`;
                console.log('Filter URL:', filterUrl); // Debug log
                console.log('Filters:', {
                    date: dateFilter.value,
                    client_id: clientFilter.value
                }); // Debug log

                fetch(filterUrl, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    // Update table content (same as payments)
                    appointmentsTable.innerHTML = data.html;

                    // Status handlers no longer needed since we use form buttons

                    hideLoading();

                    // Show success feedback (copied from payments)
                    const successDiv = document.createElement('div');
                    successDiv.className = 'fixed top-4 right-4 bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded z-50 transition-opacity duration-300';
                    successDiv.innerHTML = `
                        <div class="flex items-center">
                            <i class="fas fa-check mr-2"></i>
                            <span>Filtri applicati (${data.total} risultati)</span>
                        </div>
                    `;
                    document.body.appendChild(successDiv);

                    // Auto-remove success message
                    setTimeout(() => {
                        if (successDiv.parentElement) {
                            successDiv.remove();
                        }
                    }, 3000);
                })
                .catch(error => {
                    console.error('Error:', error);
                    hideLoading();

                    // Show user-friendly error message (copied from payments)
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'fixed top-4 right-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded z-50';
                    errorDiv.innerHTML = `
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            <span>Errore durante il caricamento dei dati. Riprova.</span>
                            <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-red-500 hover:text-red-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    `;
                    document.body.appendChild(errorDiv);

                    // Auto-remove after 5 seconds
                    setTimeout(() => {
                        if (errorDiv.parentElement) {
                            errorDiv.remove();
                        }
                    }, 5000);
                });
            }

            // Status handlers removed - now using form buttons instead of select dropdowns

            // Event listeners for filters (copied from payments)
            dateFilter.addEventListener('change', () => applyFilters());
            clientFilter.addEventListener('change', () => applyFilters());

            // Show all appointments
            showAll.addEventListener('click', function() {
                dateFilter.value = '';
                clientFilter.value = '';
                applyFilters();
            });

            // Reset filters (copied from payments)
            clearFilters.addEventListener('click', function() {
                dateFilter.value = '{{ \Carbon\Carbon::today()->format('Y-m-d') }}';
                clientFilter.value = '';

                // Clear URL parameters and redirect to show default view
                window.location.href = window.location.pathname;
            });

            // Status handlers no longer needed
        });
    </script>
</x-app-layout>
