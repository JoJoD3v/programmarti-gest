@section('page-title', 'Aggiungi Pagamento')

<x-app-layout>
    <x-slot name="header">
        Aggiungi Nuovo Pagamento
    </x-slot>

    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">Informazioni Pagamento</h2>
            </div>

            <form action="{{ route('payments.store') }}" method="POST" class="p-6 space-y-6">
                @csrf

                <!-- Project and Client -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="project_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Progetto *
                        </label>
                        <select id="project_id"
                                name="project_id"
                                class="select-improved w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('project_id') border-red-500 @enderror"
                                required
                                onchange="updateClient()">
                            <option value="">Seleziona progetto</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}" 
                                        data-client-id="{{ $project->client_id }}"
                                        {{ old('project_id') == $project->id ? 'selected' : '' }}>
                                    {{ $project->name }} ({{ $project->client->full_name }})
                                </option>
                            @endforeach
                        </select>
                        @error('project_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="client_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Cliente *
                        </label>
                        <select id="client_id"
                                name="client_id"
                                class="select-improved w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('client_id') border-red-500 @enderror"
                                required>
                            <option value="">Seleziona cliente</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>
                                    {{ $client->full_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('client_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Amount and Due Date -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">
                            Importo (€) *
                        </label>
                        <input type="number" 
                               id="amount" 
                               name="amount" 
                               step="0.01"
                               min="0"
                               value="{{ old('amount') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('amount') border-red-500 @enderror"
                               required>
                        @error('amount')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="due_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Data Scadenza *
                        </label>
                        <input type="date" 
                               id="due_date" 
                               name="due_date" 
                               value="{{ old('due_date', date('Y-m-d')) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('due_date') border-red-500 @enderror"
                               required>
                        @error('due_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Payment Type and Assigned User -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="payment_type" class="block text-sm font-medium text-gray-700 mb-2">
                            Tipo Pagamento *
                        </label>
                        <select id="payment_type"
                                name="payment_type"
                                class="select-improved w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('payment_type') border-red-500 @enderror"
                                required>
                            @foreach(App\Models\Payment::getPaymentTypes() as $key => $type)
                                <option value="{{ $key }}" {{ old('payment_type', 'one_time') === $key ? 'selected' : '' }}>
                                    {{ $type }}
                                </option>
                            @endforeach
                        </select>
                        @error('payment_type')
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
                            @foreach(App\Models\User::all() as $user)
                                <option value="{{ $user->id }}" {{ old('assigned_user_id', auth()->id()) == $user->id ? 'selected' : '' }}>
                                    {{ $user->full_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('assigned_user_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Notes -->
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                        Note
                    </label>
                    <textarea id="notes" 
                              name="notes" 
                              rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('notes') border-red-500 @enderror">{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                    <a href="{{ route('payments.index') }}" 
                       class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors duration-200">
                        Annulla
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 text-white rounded-lg transition-colors duration-200"
                            style="background-color: #007BCE;"
                            onmouseover="this.style.backgroundColor='#005B99'"
                            onmouseout="this.style.backgroundColor='#007BCE'">
                        Salva Pagamento
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function updateClient() {
            const projectSelect = document.getElementById('project_id');
            const clientSelect = document.getElementById('client_id');
            const selectedOption = projectSelect.options[projectSelect.selectedIndex];
            
            if (selectedOption.value) {
                const clientId = selectedOption.getAttribute('data-client-id');
                clientSelect.value = clientId;
            } else {
                clientSelect.value = '';
            }
        }
    </script>
</x-app-layout>
