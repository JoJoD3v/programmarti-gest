@section('page-title', 'Modifica Preventivo ' . $preventivo->quote_number)

<x-app-layout>
    <x-slot name="header">
        Modifica Preventivo {{ $preventivo->quote_number }}
    </x-slot>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-900">Modifica Preventivo</h2>
                <a href="{{ route('preventivi.show', $preventivo) }}"
                   class="text-gray-600 hover:text-gray-900">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Torna al preventivo
                </a>
            </div>
        </div>

        <form action="{{ route('preventivi.update', $preventivo) }}" method="POST" class="p-6" onsubmit="debugFormSubmission(event)">
            @csrf
            @method('PUT')

            <!-- Client and Project Selection -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="client_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Cliente *
                    </label>
                    <select id="client_id"
                            name="client_id"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('client_id') border-red-500 @enderror"
                            required>
                        <option value="">Seleziona cliente</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" {{ old('client_id', $preventivo->client_id) == $client->id ? 'selected' : '' }}>
                                {{ $client->full_name }} ({{ $client->email }})
                            </option>
                        @endforeach
                    </select>
                    @error('client_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="project_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Progetto *
                    </label>
                    <select id="project_id"
                            name="project_id"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('project_id') border-red-500 @enderror"
                            required>
                        <option value="">Seleziona progetto</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}" {{ old('project_id', $preventivo->project_id) == $project->id ? 'selected' : '' }}>
                                {{ $project->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('project_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Status -->
            <div class="mb-6">
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                    Stato *
                </label>
                <select id="status"
                        name="status"
                        class="w-full md:w-1/3 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('status') border-red-500 @enderror"
                        required>
                    @foreach(\App\Models\Preventivo::getStatuses() as $value => $label)
                        <option value="{{ $value }}" {{ old('status', $preventivo->status) === $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('status')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Job Description -->
            <div class="mb-6">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                    Descrizione del Lavoro *
                </label>
                <textarea id="description"
                          name="description"
                          rows="4"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('description') border-red-500 @enderror"
                          placeholder="Descrivi il lavoro da svolgere..."
                          required>{{ old('description', $preventivo->description) }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Dynamic Work Items Section -->
            <div class="mb-6">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">
                        Voci di Lavoro *
                    </label>
                    <p class="text-sm text-gray-500 mt-1">Usa i pulsanti "+" per aggiungere nuove voci dopo ogni riga</p>
                </div>

                <div id="workItemsContainer">
                    @foreach($preventivo->items as $index => $item)
                        <div class="work-item-group mb-4">
                            <div class="work-item-row grid grid-cols-1 md:grid-cols-12 gap-4 p-4 border border-gray-200 rounded-lg">
                                <div class="md:col-span-7">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Descrizione Lavoro
                                    </label>
                                    <input type="text"
                                           name="work_items[{{ $index }}][description]"
                                           value="{{ old('work_items.' . $index . '.description', $item->description) }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                           placeholder="Descrizione del lavoro..."
                                           required>
                                </div>
                                <div class="md:col-span-3">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Costo (€)
                                    </label>
                                    <input type="number"
                                           name="work_items[{{ $index }}][cost]"
                                           value="{{ old('work_items.' . $index . '.cost', $item->cost) }}"
                                           step="0.01"
                                           min="0"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 cost-input"
                                           placeholder="0.00"
                                           required>
                                </div>
                                <div class="md:col-span-2 flex items-end gap-2">
                                    <button type="button"
                                            class="add-item-after flex-1 px-3 py-2 text-white rounded-lg hover:opacity-90 transition-colors duration-200"
                                            style="background-color: #007BCE;"
                                            title="Aggiungi voce dopo questa"
                                            onclick="addWorkItemAfter(this)">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                    <button type="button"
                                            class="remove-item flex-1 px-3 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600"
                                            title="Rimuovi questa voce"
                                            onclick="removeWorkItem(this)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- VAT Calculation Section -->
                <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <div class="flex items-center mb-3">
                        <!-- Hidden input to ensure vat_enabled is always sent -->
                        <input type="hidden" name="vat_enabled" value="0">
                        <input type="checkbox"
                               id="vatEnabled"
                               name="vat_enabled"
                               value="1"
                               {{ $preventivo->vat_enabled ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="vatEnabled" class="ml-2 text-sm font-medium text-gray-700">
                            Calcola IVA ({{ $preventivo->vat_rate }}%)
                        </label>
                    </div>
                    <input type="hidden" name="vat_rate" value="{{ $preventivo->vat_rate }}">

                    <!-- Debug info (remove in production) -->
                    <div class="text-xs text-gray-500 mt-2">
                        Debug: VAT Enabled = {{ $preventivo->vat_enabled ? 'true' : 'false' }},
                        VAT Rate = {{ $preventivo->vat_rate }}%
                    </div>
                </div>

                <!-- Total Amount Display -->
                <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-medium text-gray-700">Subtotale:</span>
                            <span id="subtotalAmount" class="text-lg font-semibold text-gray-900">€{{ number_format($preventivo->subtotal_amount, 2, ',', '.') }}</span>
                        </div>
                        <div id="vatRow" class="flex justify-between items-center text-blue-600" style="display: {{ $preventivo->vat_enabled ? 'flex' : 'none' }};">
                            <span class="text-lg font-medium">IVA ({{ $preventivo->vat_rate }}%):</span>
                            <span id="vatAmount" class="text-lg font-semibold">€{{ number_format($preventivo->vat_amount, 2, ',', '.') }}</span>
                        </div>
                        <hr class="border-gray-300">
                        <div class="flex justify-between items-center">
                            <span class="text-xl font-bold text-gray-900">Totale:</span>
                            <span id="totalAmount" class="text-xl font-bold text-gray-900">€{{ number_format($preventivo->total_amount, 2, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="flex justify-end space-x-4">
                <a href="{{ route('preventivi.show', $preventivo) }}"
                   class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Annulla
                </a>
                <button type="submit"
                        class="text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200"
                        style="background-color: #007BCE;"
                        onmouseover="this.style.backgroundColor='#005B99'"
                        onmouseout="this.style.backgroundColor='#007BCE'">
                    <i class="fas fa-save mr-2"></i>
                    Aggiorna Preventivo
                </button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let workItemIndex = {{ count($preventivo->items) }};

            // Client selection change handler
            const clientSelect = document.getElementById('client_id');
            const projectSelect = document.getElementById('project_id');

            clientSelect.addEventListener('change', function() {
                const clientId = this.value;

                if (clientId) {
                    // Enable project select and show loading
                    projectSelect.disabled = false;
                    projectSelect.innerHTML = '<option value="">Caricamento progetti...</option>';

                    // Fetch projects for selected client
                    const baseUrl = '{{ route("api.clients.projects", ["client" => "__CLIENT_ID__"]) }}';
                    const apiUrl = baseUrl.replace('__CLIENT_ID__', clientId);
                    console.log('Base URL template:', baseUrl);
                    console.log('Final API URL:', apiUrl);
                    console.log('Client ID:', clientId);

                    fetch(apiUrl, {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                            },
                            credentials: 'same-origin'
                        })
                        .then(response => {
                            console.log('Response status:', response.status);
                            console.log('Response headers:', response.headers);
                            if (!response.ok) {
                                throw new Error(`HTTP error! status: ${response.status}`);
                            }
                            return response.json();
                        })
                        .then(data => {
                            projectSelect.innerHTML = '<option value="">Seleziona progetto</option>';
                            data.projects.forEach(project => {
                                const option = document.createElement('option');
                                option.value = project.id;
                                option.textContent = project.name;
                                // Keep current selection if it matches
                                if (project.id == {{ $preventivo->project_id }}) {
                                    option.selected = true;
                                }
                                projectSelect.appendChild(option);
                            });
                        })
                        .catch(error => {
                            console.error('Error fetching projects:', error);
                            console.error('API URL was:', apiUrl);
                            projectSelect.innerHTML = '<option value="">Errore nel caricamento progetti</option>';

                            // Mostra un messaggio di errore più dettagliato
                            const errorDiv = document.createElement('div');
                            errorDiv.className = 'fixed top-4 right-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded z-50';
                            errorDiv.innerHTML = `
                                <div class="flex items-center">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    <span>Errore API: ${error.message}<br><small>URL: ${apiUrl}</small></span>
                                    <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-red-500 hover:text-red-700">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            `;
                            document.body.appendChild(errorDiv);

                            // Auto-remove after 8 seconds (increased for debugging)
                            setTimeout(() => {
                                if (errorDiv.parentElement) {
                                    errorDiv.remove();
                                }
                            }, 8000);
                        });
                } else {
                    projectSelect.disabled = true;
                    projectSelect.innerHTML = '<option value="">Prima seleziona un cliente</option>';
                }
            });

            // Add work item functionality
            const workItemsContainer = document.getElementById('workItemsContainer');

            // Function to add work item after a specific row
            window.addWorkItemAfter = function(button) {
                const currentGroup = button.closest('.work-item-group');
                const newGroup = createWorkItemGroup(workItemIndex);
                currentGroup.insertAdjacentElement('afterend', newGroup);
                workItemIndex++;
                updateRemoveButtons();
                calculateTotal();
            };

            // Function to create a new work item group
            function createWorkItemGroup(index) {
                const div = document.createElement('div');
                div.className = 'work-item-group mb-4';
                div.innerHTML = `
                    <div class="work-item-row grid grid-cols-1 md:grid-cols-12 gap-4 p-4 border border-gray-200 rounded-lg">
                        <div class="md:col-span-7">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Descrizione Lavoro
                            </label>
                            <input type="text"
                                   name="work_items[${index}][description]"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Descrizione del lavoro..."
                                   required>
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Costo (€)
                            </label>
                            <input type="number"
                                   name="work_items[${index}][cost]"
                                   step="0.01"
                                   min="0"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 cost-input"
                                   placeholder="0.00"
                                   required>
                        </div>
                        <div class="md:col-span-2 flex items-end gap-2">
                            <button type="button"
                                    class="add-item-after flex-1 px-3 py-2 text-white rounded-lg hover:opacity-90 transition-colors duration-200"
                                    style="background-color: #007BCE;"
                                    title="Aggiungi voce dopo questa"
                                    onclick="addWorkItemAfter(this)">
                                <i class="fas fa-plus"></i>
                            </button>
                            <button type="button"
                                    class="remove-item flex-1 px-3 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600"
                                    title="Rimuovi questa voce"
                                    onclick="removeWorkItem(this)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `;

                // Add event listener for cost calculation
                const costInput = div.querySelector('.cost-input');
                costInput.addEventListener('input', calculateTotal);

                return div;
            }

            // Remove work item function
            window.removeWorkItem = function(button) {
                const group = button.closest('.work-item-group');
                group.remove();
                updateRemoveButtons();
                calculateTotal();
            };

            // Update remove buttons state
            function updateRemoveButtons() {
                const groups = document.querySelectorAll('.work-item-group');
                const removeButtons = document.querySelectorAll('.remove-item');

                removeButtons.forEach(button => {
                    button.disabled = groups.length <= 1;
                });
            }

            // Calculate total amount with VAT
            function calculateTotal() {
                const costInputs = document.querySelectorAll('.cost-input');
                const vatEnabled = document.getElementById('vatEnabled').checked;
                const vatRate = parseFloat(document.querySelector('input[name="vat_rate"]').value) || 22;

                let subtotal = 0;

                costInputs.forEach(input => {
                    const value = parseFloat(input.value) || 0;
                    subtotal += value;
                });

                const vatAmount = vatEnabled ? (subtotal * vatRate / 100) : 0;
                const total = subtotal + vatAmount;

                // Debug logging
                console.log('🧮 VAT Calculation Debug:', {
                    subtotal: subtotal,
                    vatEnabled: vatEnabled,
                    vatRate: vatRate,
                    vatAmount: vatAmount,
                    total: total,
                    itemsCount: costInputs.length
                });

                // Update display
                document.getElementById('subtotalAmount').textContent =
                    '€' + subtotal.toLocaleString('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                document.getElementById('vatAmount').textContent =
                    '€' + vatAmount.toLocaleString('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                document.getElementById('totalAmount').textContent =
                    '€' + total.toLocaleString('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                // Show/hide VAT row
                const vatRow = document.getElementById('vatRow');
                vatRow.style.display = vatEnabled ? 'flex' : 'none';
            }

            // Add event listeners to existing cost inputs
            document.querySelectorAll('.cost-input').forEach(input => {
                input.addEventListener('input', calculateTotal);
            });

            // Add event listener for VAT checkbox
            document.getElementById('vatEnabled').addEventListener('change', calculateTotal);

            // Initialize
            updateRemoveButtons();
            calculateTotal();
        });

        // Debug form submission
        function debugFormSubmission(event) {
            const formData = new FormData(event.target);
            const vatEnabled = formData.get('vat_enabled');
            const vatRate = formData.get('vat_rate');

            console.log('🚀 Form Submission Debug:', {
                vat_enabled: vatEnabled,
                vat_rate: vatRate,
                all_form_data: Object.fromEntries(formData.entries())
            });

            // Don't prevent submission, just log
            return true;
        }
    </script>
</x-app-layout>
