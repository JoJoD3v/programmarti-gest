<!-- Table -->
<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Cliente
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Operatore Assegnato
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Data e Ora
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Nome Appuntamento
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Status
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Azione
                </th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Azioni
                </th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($appointments as $appointment)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">
                            {{ $appointment->client->full_name }}
                        </div>
                        <div class="text-sm text-gray-500">
                            {{ $appointment->client->email }}
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">
                            {{ $appointment->user->first_name }} {{ $appointment->user->last_name }}
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $appointment->formatted_date }}
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">{{ $appointment->appointment_name }}</div>
                        @if($appointment->notes)
                            <div class="text-sm text-gray-500 mt-1">{{ Str::limit($appointment->notes, 50) }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span id="status-badge-{{ $appointment->id }}"
                              class="px-2 py-1 text-xs font-medium rounded-full {{ $appointment->status_color }}">
                            {{ $appointment->status_label }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($appointment->status === 'pending')
                            <form method="POST" action="{{ route('appointments.mark-completed', $appointment) }}" class="inline-block">
                                @csrf
                                <button type="submit" 
                                    class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-2" 
                                    onclick="return confirm('Sei sicuro di voler segnare questo appuntamento come completato?')"                                    
                                    title="Elimina">
                                <i class="fas fa-check mr-2"></i>
                                    Completa
                                </button>
                            </form>
                        @elseif($appointment->status === 'completed')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <i class="fas fa-check mr-1"></i>
                                Completato
                            </span>
                        @elseif($appointment->status === 'cancelled')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                <i class="fas fa-times mr-1"></i>
                                Annullato
                            </span>
                        @elseif($appointment->status === 'absent')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                <i class="fas fa-user-slash mr-1"></i>
                                Assente
                            </span>
                        @else
                            <span class="text-sm text-gray-500">{{ $appointment->status }}</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex justify-end space-x-2">
                            <a href="{{ route('appointments.edit', $appointment) }}" 
                               class="text-blue-600 hover:text-blue-900" title="Modifica">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('appointments.destroy', $appointment) }}" 
                                  method="POST" 
                                  class="inline"
                                  onsubmit="return confirm('Sei sicuro di voler eliminare questo appuntamento?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="text-red-600 hover:text-red-900" title="Elimina">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center">
                        <div class="text-gray-500">
                            <i class="fas fa-calendar-times text-4xl mb-4"></i>
                            <p class="text-lg font-medium">Nessun appuntamento trovato</p>
                            <p class="text-sm">Non ci sono appuntamenti per i filtri selezionati.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
