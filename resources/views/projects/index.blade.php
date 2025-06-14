@section('page-title', 'Gestione Progetti')

<x-app-layout>
    <x-slot name="header">
        Gestione Progetti
    </x-slot>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <!-- Header with Search and Add Button -->
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-900">Lista Progetti</h2>
                <a href="{{ route('projects.create') }}"
                   class="text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200 hover:opacity-90"
                   style="background-color: #007BCE;"
                   onmouseover="this.style.backgroundColor='#005B99'"
                   onmouseout="this.style.backgroundColor='#007BCE'">
                    <i class="fas fa-plus mr-2"></i>
                    Aggiungi Progetto
                </a>
            </div>

            <!-- Search and Filters -->
            <form method="GET" class="flex flex-wrap gap-4" id="projectsFilterForm">
                <div class="flex-1 min-w-64">
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Cerca per nome progetto, descrizione o cliente..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <select name="status"
                            id="statusFilter"
                            class="select-improved px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Tutti gli stati</option>
                        <option value="planning" {{ request('status') === 'planning' ? 'selected' : '' }}>Pianificazione</option>
                        <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Corso</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completato</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Annullato</option>
                    </select>
                </div>
                <div>
                    <select name="project_type"
                            id="projectTypeFilter"
                            class="select-improved px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Tutti i tipi</option>
                        <option value="website" {{ request('project_type') === 'website' ? 'selected' : '' }}>Sito Web</option>
                        <option value="ecommerce" {{ request('project_type') === 'ecommerce' ? 'selected' : '' }}>E-commerce</option>
                        <option value="management_system" {{ request('project_type') === 'management_system' ? 'selected' : '' }}>Sistema Gestionale</option>
                        <option value="marketing_campaign" {{ request('project_type') === 'marketing_campaign' ? 'selected' : '' }}>Campagna Marketing</option>
                        <option value="social_media_management" {{ request('project_type') === 'social_media_management' ? 'selected' : '' }}>Gestione Social Media</option>
                        <option value="nfc_accessories" {{ request('project_type') === 'nfc_accessories' ? 'selected' : '' }}>Accessori NFC</option>
                    </select>
                </div>
                <button type="submit"
                        id="searchButton"
                        class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                    <i class="fas fa-search mr-2"></i>Cerca
                </button>
                <a href="{{ route('projects.index') }}"
                   id="reset-filters"
                   class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">
                    Reset
                </a>
            </form>
        </div>

        <!-- Projects Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200" id="projectsTable">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Progetto
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Cliente
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Tipo
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Assegnato a
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Costo
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Stato
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Data Inizio
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Azioni
                        </th>
                    </tr>
                </thead>
                @include('projects.partials.table')
            </table>
        </div>

        <!-- Pagination -->
        <div id="projectsPagination">
            @include('projects.partials.pagination')
        </div>
    </div>

    <!-- AJAX Filtering JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('projectsFilterForm');
            const statusFilter = document.getElementById('statusFilter');
            const projectTypeFilter = document.getElementById('projectTypeFilter');
            const searchInput = document.querySelector('input[name="search"]');
            const searchButton = document.getElementById('searchButton');
            const resetButton = document.getElementById('reset-filters');
            const projectsTable = document.getElementById('projectsTable');
            const projectsPagination = document.getElementById('projectsPagination');

            let searchTimeout;

            // Function to show loading state
            function showLoading() {
                if (searchButton) {
                    searchButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Caricamento...';
                    searchButton.disabled = true;
                }
                // Add loading overlay to table
                projectsTable.style.opacity = '0.6';
                projectsTable.style.pointerEvents = 'none';
            }

            // Function to hide loading state
            function hideLoading() {
                if (searchButton) {
                    searchButton.innerHTML = '<i class="fas fa-search mr-2"></i>Cerca';
                    searchButton.disabled = false;
                }
                // Remove loading overlay from table
                projectsTable.style.opacity = '1';
                projectsTable.style.pointerEvents = 'auto';
            }

            // Function to perform AJAX filtering
            function performFilter(page = 1) {
                showLoading();

                // Build query parameters
                const params = new URLSearchParams();

                if (searchInput.value.trim()) {
                    params.append('search', searchInput.value.trim());
                }
                if (statusFilter.value) {
                    params.append('status', statusFilter.value);
                }
                if (projectTypeFilter.value) {
                    params.append('project_type', projectTypeFilter.value);
                }
                if (page > 1) {
                    params.append('page', page);
                }

                // Update URL without page reload
                const newUrl = `${window.location.pathname}${params.toString() ? '?' + params.toString() : ''}`;
                window.history.pushState({}, '', newUrl);

                // Make AJAX request
                const filterUrl = `{{ route('projects.filter') }}${params.toString() ? '?' + params.toString() : ''}`;
                console.log('Filter URL:', filterUrl); // Debug log
                console.log('Filters:', {
                    search: searchInput.value,
                    status: statusFilter.value,
                    project_type: projectTypeFilter.value,
                    page: page
                }); // Debug log

                fetch(filterUrl, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    // Update table content
                    const tbody = projectsTable.querySelector('tbody');
                    if (tbody) {
                        tbody.outerHTML = data.html;
                    }

                    // Update pagination
                    projectsPagination.innerHTML = data.pagination;

                    // Reattach pagination event listeners
                    attachPaginationListeners();

                    console.log('Filter applied successfully. Total results:', data.total);
                })
                .catch(error => {
                    console.error('Error applying filters:', error);
                    alert('Errore durante il filtraggio. Riprova.');
                })
                .finally(() => {
                    hideLoading();
                });
            }

            // Function to attach pagination event listeners
            function attachPaginationListeners() {
                const paginationLinks = document.querySelectorAll('.pagination-link');
                paginationLinks.forEach(link => {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        const page = this.getAttribute('data-page');
                        if (page) {
                            performFilter(parseInt(page));
                        }
                    });
                });
            }

            // Auto-submit for dropdown filters
            statusFilter.addEventListener('change', function() {
                console.log('Status filter changed to:', this.value);
                performFilter();
            });

            projectTypeFilter.addEventListener('change', function() {
                console.log('Project type filter changed to:', this.value);
                performFilter();
            });

            // Debounced search for text input
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    console.log('Search input changed to:', this.value);
                    performFilter();
                }, 500); // 500ms delay
            });

            // Manual search button
            searchButton.addEventListener('click', function(e) {
                e.preventDefault();
                clearTimeout(searchTimeout);
                performFilter();
            });

            // Reset filters
            resetButton.addEventListener('click', function(e) {
                e.preventDefault();

                // Clear all filters
                searchInput.value = '';
                statusFilter.value = '';
                projectTypeFilter.value = '';

                // Perform filter to show all results
                performFilter();
            });

            // Prevent form submission
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                performFilter();
            });

            // Initial pagination listeners
            attachPaginationListeners();

            console.log('Projects AJAX filtering initialized');
        });
    </script>
</x-app-layout>
