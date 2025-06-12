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

                <div class="flex items-end">
                    <button type="button" 
                            id="clear-filters"
                            class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-200">
                        <i class="fas fa-times mr-2"></i>
                        Pulisci Filtri
                    </button>
                </div>
            </div>
        </div>

        <!-- Appointments Table -->
        <div id="appointments-table">
            @include('appointments.partials.table', ['appointments' => $appointments])
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dateFilter = document.getElementById('date-filter');
            const clientFilter = document.getElementById('client-filter');
            const clearFilters = document.getElementById('clear-filters');
            const appointmentsTable = document.getElementById('appointments-table');

            function updateTable() {
                const date = dateFilter.value;
                const clientId = clientFilter.value;
                
                const url = new URL(window.location.href);
                url.searchParams.set('date', date);
                if (clientId) {
                    url.searchParams.set('client_id', clientId);
                } else {
                    url.searchParams.delete('client_id');
                }

                fetch(url.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.text())
                .then(html => {
                    appointmentsTable.innerHTML = html;
                    attachStatusHandlers();
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            }

            function attachStatusHandlers() {
                document.querySelectorAll('.status-select').forEach(select => {
                    select.addEventListener('change', function() {
                        const appointmentId = this.dataset.appointmentId;
                        const status = this.value;
                        
                        fetch(`/appointments/${appointmentId}/status`, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({ status: status })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const statusBadge = document.querySelector(`#status-badge-${appointmentId}`);
                                statusBadge.textContent = data.status_label;
                                statusBadge.className = `px-2 py-1 text-xs font-medium rounded-full ${data.status_color}`;
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                        });
                    });
                });
            }

            dateFilter.addEventListener('change', updateTable);
            clientFilter.addEventListener('change', updateTable);
            
            clearFilters.addEventListener('click', function() {
                dateFilter.value = '{{ \Carbon\Carbon::today()->format('Y-m-d') }}';
                clientFilter.value = '';
                updateTable();
            });

            // Initial attachment of status handlers
            attachStatusHandlers();
        });
    </script>
    @endpush
</x-app-layout>
