@section('page-title', 'Gestione Clienti')

<x-app-layout>
    <x-slot name="header">
        Gestione Clienti
    </x-slot>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <!-- Header with Search and Add Button -->
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-900">Lista Clienti</h2>
                <a href="{{ route('clients.create') }}"
                   class="text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200 hover:opacity-90"
                   style="background-color: #007BCE;"
                   onmouseover="this.style.backgroundColor='#005B99'"
                   onmouseout="this.style.backgroundColor='#007BCE'">
                    <i class="fas fa-plus mr-2"></i>
                    Aggiungi Cliente
                </a>
            </div>

            <!-- Search and Filters -->
            <form method="GET" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-64">
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Cerca per nome, email, codice fiscale o P.IVA..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <select name="entity_type" class="select-improved px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Tutti i tipi</option>
                        <option value="individual" {{ request('entity_type') === 'individual' ? 'selected' : '' }}>Privato</option>
                        <option value="business" {{ request('entity_type') === 'business' ? 'selected' : '' }}>Azienda</option>
                    </select>
                </div>
                <button type="submit" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                    <i class="fas fa-search mr-2"></i>Cerca
                </button>
                <a href="{{ route('clients.index') }}" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">
                    Reset
                </a>
            </form>
        </div>

        <!-- Clients Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Cliente
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Contatto
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Tipo
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Codice Fiscale/P.IVA
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
                    @forelse($clients as $client)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $client->full_name }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $client->email }}</div>
                                @if($client->phone)
                                    <div class="text-sm text-gray-500">{{ $client->phone }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    @if($client->entity_type === 'business') bg-blue-100 text-blue-800
                                    @else bg-green-100 text-green-800 @endif">
                                    {{ $client->entity_type === 'business' ? 'Azienda' : 'Privato' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                @if($client->entity_type === 'business' && $client->vat_number)
                                    P.IVA: {{ $client->vat_number }}
                                @elseif($client->tax_code)
                                    CF: {{ $client->tax_code }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $client->created_at->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end space-x-2">
                                    <a href="{{ route('clients.show', $client) }}" 
                                       class="text-blue-600 hover:text-blue-900">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('clients.edit', $client) }}" 
                                       class="text-yellow-600 hover:text-yellow-900">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('clients.destroy', $client) }}" 
                                          method="POST" 
                                          class="inline"
                                          onsubmit="return confirm('Sei sicuro di voler eliminare questo cliente?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-address-book text-4xl mb-4 text-gray-300"></i>
                                <p class="text-lg">Nessun cliente trovato</p>
                                <p class="text-sm">Inizia aggiungendo il tuo primo cliente</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($clients->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $clients->appends(request()->query())->links('pagination.custom') }}
            </div>
        @endif
    </div>
</x-app-layout>
