@section('page-title', 'Aggiungi Progetto')

<x-app-layout>
    <x-slot name="header">
        Aggiungi Nuovo Progetto
    </x-slot>

    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">Informazioni Progetto</h2>
            </div>

            <form action="{{ route('projects.store') }}" method="POST" class="p-6 space-y-6">
                @csrf

                <!-- Basic Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                            Nome Progetto *
                        </label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               value="{{ old('name') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-500 @enderror"
                               required>
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="project_type" class="block text-sm font-medium text-gray-700 mb-2">
                            Tipo Progetto *
                        </label>
                        <select id="project_type" 
                                name="project_type" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('project_type') border-red-500 @enderror"
                                required>
                            <option value="">Seleziona tipo progetto</option>
                            @foreach(App\Models\Project::getProjectTypes() as $key => $type)
                                <option value="{{ $key }}" {{ old('project_type') === $key ? 'selected' : '' }}>
                                    {{ $type }}
                                </option>
                            @endforeach
                        </select>
                        @error('project_type')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                        Descrizione
                    </label>
                    <textarea id="description" 
                              name="description" 
                              rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('description') border-red-500 @enderror">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Client and User Assignment -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                                <option value="{{ $client->id }}" 
                                    {{ old('client_id', $selectedClientId) == $client->id ? 'selected' : '' }}>
                                    {{ $client->full_name }} ({{ $client->email }})
                                </option>
                            @endforeach
                        </select>
                        @error('client_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="assigned_user_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Assegnato a
                        </label>
                        <select id="assigned_user_id" 
                                name="assigned_user_id" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('assigned_user_id') border-red-500 @enderror">
                            <option value="">Non assegnato</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('assigned_user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->full_name }} ({{ $user->getRoleNames()->first() }})
                                </option>
                            @endforeach
                        </select>
                        @error('assigned_user_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Dates and Status -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Data Inizio *
                        </label>
                        <input type="date" 
                               id="start_date" 
                               name="start_date" 
                               value="{{ old('start_date', date('Y-m-d')) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('start_date') border-red-500 @enderror"
                               required>
                        @error('start_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Data Fine (Prevista)
                        </label>
                        <input type="date" 
                               id="end_date" 
                               name="end_date" 
                               value="{{ old('end_date') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('end_date') border-red-500 @enderror">
                        @error('end_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                            Stato *
                        </label>
                        <select id="status" 
                                name="status" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('status') border-red-500 @enderror"
                                required>
                            @foreach(App\Models\Project::getStatuses() as $key => $status)
                                <option value="{{ $key }}" {{ old('status', 'planning') === $key ? 'selected' : '' }}>
                                    {{ $status }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Payment Structure -->
                <div class="border-t border-gray-200 pt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Struttura Pagamenti</h3>
                    
                    <!-- Payment Type -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Tipo Pagamento *
                        </label>
                        <div class="flex space-x-6">
                            <label class="flex items-center">
                                <input type="radio" 
                                       name="payment_type" 
                                       value="one_time" 
                                       {{ old('payment_type', 'one_time') === 'one_time' ? 'checked' : '' }}
                                       class="text-blue-600 focus:ring-blue-500"
                                       onchange="togglePaymentFields()">
                                <span class="ml-2 text-sm text-gray-700">Pagamento Unico</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" 
                                       name="payment_type" 
                                       value="installments" 
                                       {{ old('payment_type') === 'installments' ? 'checked' : '' }}
                                       class="text-blue-600 focus:ring-blue-500"
                                       onchange="togglePaymentFields()">
                                <span class="ml-2 text-sm text-gray-700">Rate</span>
                            </label>
                        </div>
                        @error('payment_type')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Total Cost -->
                    <div class="mb-6">
                        <label for="total_cost" class="block text-sm font-medium text-gray-700 mb-2">
                            Costo Totale (€)
                        </label>
                        <input type="number" 
                               id="total_cost" 
                               name="total_cost" 
                               step="0.01"
                               min="0"
                               value="{{ old('total_cost') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('total_cost') border-red-500 @enderror">
                        @error('total_cost')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Installment Fields -->
                    <div id="installment-fields" class="space-y-6" style="display: none;">
                        <!-- Down Payment -->
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="has_down_payment" 
                                       value="1"
                                       {{ old('has_down_payment') ? 'checked' : '' }}
                                       class="text-blue-600 focus:ring-blue-500"
                                       onchange="toggleDownPayment()">
                                <span class="ml-2 text-sm text-gray-700">Richiedi Acconto</span>
                            </label>
                        </div>

                        <div id="down-payment-amount" style="display: none;">
                            <label for="down_payment_amount" class="block text-sm font-medium text-gray-700 mb-2">
                                Importo Acconto (€)
                            </label>
                            <input type="number" 
                                   id="down_payment_amount" 
                                   name="down_payment_amount" 
                                   step="0.01"
                                   min="0"
                                   value="{{ old('down_payment_amount') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('down_payment_amount') border-red-500 @enderror">
                            @error('down_payment_amount')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label for="payment_frequency" class="block text-sm font-medium text-gray-700 mb-2">
                                    Frequenza Pagamenti
                                </label>
                                <select id="payment_frequency" 
                                        name="payment_frequency" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('payment_frequency') border-red-500 @enderror">
                                    <option value="">Seleziona frequenza</option>
                                    @foreach(App\Models\Project::getPaymentFrequencies() as $key => $frequency)
                                        <option value="{{ $key }}" {{ old('payment_frequency') === $key ? 'selected' : '' }}>
                                            {{ $frequency }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('payment_frequency')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="installment_amount" class="block text-sm font-medium text-gray-700 mb-2">
                                    Importo Rata (€)
                                </label>
                                <input type="number" 
                                       id="installment_amount" 
                                       name="installment_amount" 
                                       step="0.01"
                                       min="0"
                                       value="{{ old('installment_amount') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('installment_amount') border-red-500 @enderror">
                                @error('installment_amount')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="installment_count" class="block text-sm font-medium text-gray-700 mb-2">
                                    Numero Rate
                                </label>
                                <input type="number" 
                                       id="installment_count" 
                                       name="installment_count" 
                                       min="1"
                                       value="{{ old('installment_count') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('installment_count') border-red-500 @enderror">
                                @error('installment_count')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Generate Payments Checkbox -->
                    <div class="mt-6">
                        <label class="flex items-center">
                            <input type="checkbox" 
                                   name="generate_payments" 
                                   value="1"
                                   {{ old('generate_payments', true) ? 'checked' : '' }}
                                   class="text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Genera automaticamente i pagamenti</span>
                        </label>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                    <a href="{{ route('projects.index') }}" 
                       class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors duration-200">
                        Annulla
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 text-white rounded-lg transition-colors duration-200"
                            style="background-color: #007BCE;"
                            onmouseover="this.style.backgroundColor='#005B99'"
                            onmouseout="this.style.backgroundColor='#007BCE'">
                        Salva Progetto
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function togglePaymentFields() {
            const paymentType = document.querySelector('input[name="payment_type"]:checked').value;
            const installmentFields = document.getElementById('installment-fields');
            
            if (paymentType === 'installments') {
                installmentFields.style.display = 'block';
            } else {
                installmentFields.style.display = 'none';
            }
        }

        function toggleDownPayment() {
            const checkbox = document.querySelector('input[name="has_down_payment"]');
            const downPaymentAmount = document.getElementById('down-payment-amount');
            
            if (checkbox.checked) {
                downPaymentAmount.style.display = 'block';
            } else {
                downPaymentAmount.style.display = 'none';
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            togglePaymentFields();
            if (document.querySelector('input[name="has_down_payment"]').checked) {
                toggleDownPayment();
            }
        });
    </script>
</x-app-layout>
