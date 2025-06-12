@section('page-title', 'Nuovo Appuntamento')

<x-app-layout>
    <x-slot name="header">
        Nuovo Appuntamento
    </x-slot>

    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">Crea Nuovo Appuntamento</h2>
            </div>

            <form action="{{ route('appointments.store') }}" method="POST" class="p-6 space-y-6">
                @csrf

                <!-- Client Selection -->
                <div>
                    <label for="client_id" class="block text-sm font-medium text-gray-700 mb-1">
                        Cliente <span class="text-red-500">*</span>
                    </label>
                    <select name="client_id" 
                            id="client_id" 
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('client_id') border-red-500 @enderror">
                        <option value="">Seleziona un cliente</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>
                                {{ $client->full_name }} - {{ $client->email }}
                            </option>
                        @endforeach
                    </select>
                    @error('client_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- User Assignment -->
                <div>
                    <label for="user_id" class="block text-sm font-medium text-gray-700 mb-1">
                        Operatore Assegnato <span class="text-red-500">*</span>
                    </label>
                    <select name="user_id" 
                            id="user_id" 
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('user_id') border-red-500 @enderror">
                        <option value="">Seleziona un operatore</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->first_name }} {{ $user->last_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('user_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Appointment Date and Time -->
                <div>
                    <label for="appointment_date" class="block text-sm font-medium text-gray-700 mb-1">
                        Data e Ora Appuntamento <span class="text-red-500">*</span>
                    </label>
                    <input type="datetime-local" 
                           name="appointment_date" 
                           id="appointment_date" 
                           value="{{ old('appointment_date') }}"
                           required
                           min="{{ now()->format('Y-m-d\TH:i') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('appointment_date') border-red-500 @enderror">
                    @error('appointment_date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Appointment Name -->
                <div>
                    <label for="appointment_name" class="block text-sm font-medium text-gray-700 mb-1">
                        Nome Appuntamento <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="appointment_name" 
                           id="appointment_name" 
                           value="{{ old('appointment_name') }}"
                           required
                           maxlength="255"
                           placeholder="Es: Consulenza fiscale, Riunione progetto, ecc."
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('appointment_name') border-red-500 @enderror">
                    @error('appointment_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Notes -->
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">
                        Note Aggiuntive
                    </label>
                    <textarea name="notes" 
                              id="notes" 
                              rows="4"
                              placeholder="Inserisci eventuali note o dettagli aggiuntivi per l'appuntamento..."
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('notes') border-red-500 @enderror">{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Form Actions -->
                <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                    <a href="{{ route('appointments.index') }}" 
                       class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors duration-200">
                        Annulla
                    </a>
                    <button type="submit" 
                            class="text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200 hover:opacity-90"
                            style="background-color: #007BCE;"
                            onmouseover="this.style.backgroundColor='#005B99'"
                            onmouseout="this.style.backgroundColor='#007BCE'">
                        <i class="fas fa-save mr-2"></i>
                        Crea Appuntamento
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
