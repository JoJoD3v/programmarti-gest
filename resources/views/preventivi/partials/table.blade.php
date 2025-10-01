<tbody class="bg-white divide-y divide-gray-200">
    @forelse($preventivi as $preventivo)
        <tr class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">
                    {{ $preventivo->quote_number }}
                </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">{{ $preventivo->client->full_name }}</div>
                <div class="text-sm text-gray-500">{{ $preventivo->client->email }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                €{{ number_format($preventivo->total_amount, 2, ',', '.') }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 py-1 text-xs font-medium rounded-full {{ $preventivo->status_color }}">
                    {{ $preventivo->status_label }}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ $preventivo->created_at->format('d/m/Y H:i') }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div class="flex justify-end space-x-2">
                    <a href="{{ route('preventivi.show', $preventivo) }}" 
                       class="text-blue-600 hover:text-blue-900" title="Visualizza">
                        <i class="fas fa-eye"></i>
                    </a>
                    <a href="{{ route('preventivi.edit', $preventivo) }}" 
                       class="text-yellow-600 hover:text-yellow-900" title="Modifica">
                        <i class="fas fa-edit"></i>
                    </a>
                    @if($preventivo->pdf_path)
                        <a href="{{ route('preventivi.download-pdf', $preventivo) }}" 
                           class="text-green-600 hover:text-green-900" title="Scarica PDF">
                            <i class="fas fa-download"></i>
                        </a>
                    @endif
                    <form action="{{ route('preventivi.destroy', $preventivo) }}" 
                          method="POST" 
                          class="inline"
                          onsubmit="return confirm('Sei sicuro di voler eliminare questo preventivo?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:text-red-900" title="Elimina">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                <i class="fas fa-file-invoice text-4xl mb-4 text-gray-300"></i>
                <p class="text-lg">Nessun preventivo trovato</p>
                <p class="text-sm">Inizia creando il tuo primo preventivo</p>
            </td>
        </tr>
    @endforelse
</tbody>
