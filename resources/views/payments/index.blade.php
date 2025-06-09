@section('page-title', 'Gestione Pagamenti')

<x-app-layout>
    <x-slot name="header">
        Gestione Pagamenti
    </x-slot>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <!-- Header with Filters and Add Button -->
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-900">Lista Pagamenti</h2>
                <a href="{{ route('payments.create') }}" 
                   class="text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200 hover:opacity-90"
                   style="background-color: #007BCE;"
                   onmouseover="this.style.backgroundColor='#005B99'"
                   onmouseout="this.style.backgroundColor='#007BCE'">
                    <i class="fas fa-plus mr-2"></i>
                    Aggiungi Pagamento
                </a>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-4 items-center">
                <div>
                    <label for="status-filter" class="block text-sm font-medium text-gray-700 mb-1">Stato</label>
                    <select id="status-filter" class="select-improved px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Tutti gli stati</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>In Attesa</option>
                        <option value="overdue" {{ request('status') === 'overdue' ? 'selected' : '' }}>Scaduto</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completato</option>
                    </select>
                </div>
                <div>
                    <label for="month-filter" class="block text-sm font-medium text-gray-700 mb-1">Mese</label>
                    <select id="month-filter" class="select-improved px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @php
                            $currentMonth = request('month', date('n'));
                            $italianMonths = [
                                1 => 'Gennaio', 2 => 'Febbraio', 3 => 'Marzo', 4 => 'Aprile',
                                5 => 'Maggio', 6 => 'Giugno', 7 => 'Luglio', 8 => 'Agosto',
                                9 => 'Settembre', 10 => 'Ottobre', 11 => 'Novembre', 12 => 'Dicembre'
                            ];
                        @endphp
                        @foreach($italianMonths as $monthNum => $monthName)
                            <option value="{{ $monthNum }}" {{ $currentMonth == $monthNum ? 'selected' : '' }}>
                                {{ $monthName }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="year-filter" class="block text-sm font-medium text-gray-700 mb-1">Anno</label>
                    <select id="year-filter" class="select-improved px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @php $currentYear = request('year', date('Y')); @endphp
                        @for($year = date('Y'); $year >= date('Y') - 5; $year--)
                            <option value="{{ $year }}" {{ $currentYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endfor
                    </select>
                </div>
                <div class="flex items-end">
                    <button id="reset-filters" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors duration-200">
                        <i class="fas fa-undo mr-2"></i>Reset
                    </button>
                </div>

                <!-- Loading indicator -->
                <div id="loading-indicator" class="hidden items-center">
                    <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
                    <span class="ml-2 text-sm text-gray-600">Caricamento...</span>
                </div>
            </div>
        </div>

        <!-- Payments Table -->
        <div class="overflow-x-auto">
            <table id="payments-table" class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Progetto
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Cliente
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Tipo
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Azioni
                        </th>
                    </tr>
                </thead>
                @include('payments.partials.table')
            </table>
        </div>

        <!-- Pagination -->
        <div id="pagination-container">
            @include('payments.partials.pagination')
        </div>
    </div>

    <!-- JavaScript for Dynamic Filters -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const statusFilter = document.getElementById('status-filter');
            const monthFilter = document.getElementById('month-filter');
            const yearFilter = document.getElementById('year-filter');
            const resetButton = document.getElementById('reset-filters');
            const loadingIndicator = document.getElementById('loading-indicator');
            const paymentsTable = document.getElementById('payments-table');
            const paginationContainer = document.getElementById('pagination-container');

            // Function to show loading state
            function showLoading() {
                loadingIndicator.classList.remove('hidden');
                loadingIndicator.classList.add('flex');
                paymentsTable.style.opacity = '0.5';
            }

            // Function to hide loading state
            function hideLoading() {
                loadingIndicator.classList.add('hidden');
                loadingIndicator.classList.remove('flex');
                paymentsTable.style.opacity = '1';
            }

            // Function to apply filters
            function applyFilters(page = 1) {
                showLoading();

                const params = new URLSearchParams({
                    status: statusFilter.value,
                    month: monthFilter.value,
                    year: yearFilter.value,
                    page: page
                });

                // Update URL without page reload
                const newUrl = `${window.location.pathname}?${params.toString()}`;
                window.history.pushState({}, '', newUrl);

                // Make AJAX request
                fetch(`{{ route('payments.filter') }}?${params.toString()}`, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    // Update table content
                    const tbody = paymentsTable.querySelector('tbody');
                    tbody.outerHTML = data.html;

                    // Update pagination
                    paginationContainer.innerHTML = data.pagination;

                    // Re-attach pagination event listeners
                    attachPaginationListeners();

                    hideLoading();

                    // Show success feedback briefly
                    const successDiv = document.createElement('div');
                    successDiv.className = 'fixed top-4 right-4 bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded z-50 transition-opacity duration-300';
                    successDiv.innerHTML = `
                        <div class="flex items-center">
                            <i class="fas fa-check mr-2"></i>
                            <span>Filtri applicati (${data.total} risultati)</span>
                        </div>
                    `;
                    document.body.appendChild(successDiv);

                    // Auto-remove after 2 seconds
                    setTimeout(() => {
                        successDiv.style.opacity = '0';
                        setTimeout(() => {
                            if (successDiv.parentElement) {
                                successDiv.remove();
                            }
                        }, 300);
                    }, 2000);
                })
                .catch(error => {
                    console.error('Error:', error);
                    hideLoading();

                    // Show user-friendly error message
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

            // Function to attach pagination event listeners
            function attachPaginationListeners() {
                const paginationLinks = paginationContainer.querySelectorAll('.pagination-link');
                paginationLinks.forEach(link => {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        const page = this.getAttribute('data-page');
                        if (page) {
                            applyFilters(page);
                        }
                    });
                });
            }

            // Event listeners for filters
            statusFilter.addEventListener('change', () => applyFilters());
            monthFilter.addEventListener('change', () => applyFilters());
            yearFilter.addEventListener('change', () => applyFilters());

            // Reset filters
            resetButton.addEventListener('click', function() {
                statusFilter.value = '';
                monthFilter.value = '{{ date("n") }}'; // Current month
                yearFilter.value = '{{ date("Y") }}';  // Current year
                applyFilters();
            });

            // Initial pagination listeners
            attachPaginationListeners();
        });
    </script>
</x-app-layout>
