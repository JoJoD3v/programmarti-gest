@section('page-title', 'Modifica Cliente')

<x-app-layout>
    <x-slot name="header">
        Modifica Cliente: {{ $client->full_name }}
    </x-slot>

    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">Modifica Informazioni Cliente</h2>
            </div>

            <form action="{{ route('clients.update', $client) }}" method="POST" class="p-6 space-y-6">
                @csrf
                @method('PUT')

                <!-- Personal Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">
                            Nome *
                        </label>
                        <input type="text" 
                               id="first_name" 
                               name="first_name" 
                               value="{{ old('first_name', $client->first_name) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary @error('first_name') border-red-500 @enderror"
                               required>
                        @error('first_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">
                            Cognome *
                        </label>
                        <input type="text" 
                               id="last_name" 
                               name="last_name" 
                               value="{{ old('last_name', $client->last_name) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary @error('last_name') border-red-500 @enderror"
                               required>
                        @error('last_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            Email *
                        </label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               value="{{ old('email', $client->email) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary @error('email') border-red-500 @enderror"
                               required>
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                            Telefono
                        </label>
                        <input type="tel" 
                               id="phone" 
                               name="phone" 
                               value="{{ old('phone', $client->phone) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary @error('phone') border-red-500 @enderror">
                        @error('phone')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Entity Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Tipo di Entità *
                    </label>
                    <div class="flex space-x-6">
                        <label class="flex items-center">
                            <input type="radio" 
                                   name="entity_type" 
                                   value="individual" 
                                   {{ old('entity_type', $client->entity_type) === 'individual' ? 'checked' : '' }}
                                   class="text-primary focus:ring-primary">
                            <span class="ml-2 text-sm text-gray-700">Privato</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" 
                                   name="entity_type" 
                                   value="business" 
                                   {{ old('entity_type', $client->entity_type) === 'business' ? 'checked' : '' }}
                                   class="text-primary focus:ring-primary">
                            <span class="ml-2 text-sm text-gray-700">Azienda</span>
                        </label>
                    </div>
                    @error('entity_type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tax Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="tax_code" class="block text-sm font-medium text-gray-700 mb-2">
                            Codice Fiscale
                        </label>
                        <input type="text" 
                               id="tax_code" 
                               name="tax_code" 
                               value="{{ old('tax_code', $client->tax_code) }}"
                               maxlength="16"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary @error('tax_code') border-red-500 @enderror">
                        @error('tax_code')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="vat_number" class="block text-sm font-medium text-gray-700 mb-2">
                            Partita IVA
                        </label>
                        <input type="text" 
                               id="vat_number" 
                               name="vat_number" 
                               value="{{ old('vat_number', $client->vat_number) }}"
                               maxlength="11"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary @error('vat_number') border-red-500 @enderror">
                        @error('vat_number')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Address -->
                <div>
                    <label for="address" class="block text-sm font-medium text-gray-700 mb-2">
                        Indirizzo Completo
                    </label>
                    <textarea id="address" 
                              name="address" 
                              rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary @error('address') border-red-500 @enderror">{{ old('address', $client->address) }}</textarea>
                    @error('address')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                    <a href="{{ route('clients.index') }}" 
                       class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors duration-200">
                        Annulla
                    </a>
                    <button type="submit"
                            class="px-4 py-2 text-white rounded-lg transition-colors duration-200"
                            style="background-color: #007BCE;"
                            onmouseover="this.style.backgroundColor='#005B99'"
                            onmouseout="this.style.backgroundColor='#007BCE'">
                        Aggiorna Cliente
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
