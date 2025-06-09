<tbody class="bg-white divide-y divide-gray-200">
    @forelse($payments as $payment)
        <tr class="hover:bg-gray-50 {{ $payment->isOverdue() ? 'bg-red-50' : '' }}">
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">
                    {{ $payment->project->name }}
                </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">{{ $payment->client->full_name }}</div>
                <div class="text-sm text-gray-500">{{ $payment->client->email }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">€{{ number_format($payment->amount, 2) }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">{{ $payment->due_date->format('d/m/Y') }}</div>
                @if($payment->paid_date)
                    <div class="text-sm text-green-600">Pagato: {{ $payment->paid_date->format('d/m/Y') }}</div>
                @endif
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 py-1 text-xs font-medium rounded-full
                    @if($payment->status === 'completed') bg-green-100 text-green-800
                    @elseif($payment->status === 'overdue' || $payment->isOverdue()) bg-red-100 text-red-800
                    @else bg-yellow-100 text-yellow-800 @endif">
                    @if($payment->isOverdue() && $payment->status === 'pending')
                        Scaduto
                    @else
                        {{ App\Models\Payment::getStatuses()[$payment->status] ?? $payment->status }}
                    @endif
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                    {{ App\Models\Payment::getPaymentTypes()[$payment->payment_type] ?? $payment->payment_type }}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div class="flex justify-end space-x-2">
                    @if($payment->status === 'completed')
                        @can('generate invoices')
                        <a href="{{ route('payments.invoice', $payment) }}"
                           class="inline-flex items-center px-2 py-1 text-xs text-white rounded transition-colors duration-200"
                           style="background-color: #28a745;"
                           onmouseover="this.style.backgroundColor='#218838'"
                           onmouseout="this.style.backgroundColor='#28a745'"
                           title="Scarica Fattura PDF">
                            <i class="fas fa-file-pdf"></i>
                        </a>
                        @endcan
                        @can('send emails')
                        <form action="{{ route('payments.send-invoice', $payment) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center px-2 py-1 text-xs text-white rounded transition-colors duration-200"
                                    style="background-color: #007BCE;"
                                    onmouseover="this.style.backgroundColor='#005B99'"
                                    onmouseout="this.style.backgroundColor='#007BCE'"
                                    title="Invia Fattura via Email">
                                <i class="fas fa-envelope"></i>
                            </button>
                        </form>
                        @endcan
                    @endif
                    @if($payment->status === 'pending')
                        <form action="{{ route('payments.mark-completed', $payment) }}"
                              method="POST"
                              class="inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                    class="text-green-600 hover:text-green-900"
                                    title="Segna come completato">
                                <i class="fas fa-check"></i>
                            </button>
                        </form>
                    @endif
                    <a href="{{ route('payments.show', $payment) }}"
                       class="text-blue-600 hover:text-blue-900"
                       title="Visualizza dettagli">
                        <i class="fas fa-eye"></i>
                    </a>
                    <a href="{{ route('payments.edit', $payment) }}"
                       class="text-yellow-600 hover:text-yellow-900"
                       title="Modifica">
                        <i class="fas fa-edit"></i>
                    </a>
                    <form action="{{ route('payments.destroy', $payment) }}"
                          method="POST"
                          class="inline"
                          onsubmit="return confirm('Sei sicuro di voler eliminare questo pagamento?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="text-red-600 hover:text-red-900"
                                title="Elimina">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                <i class="fas fa-search text-4xl mb-4 text-gray-300"></i>
                <p class="text-lg">Nessun pagamento trovato per i filtri selezionati</p>
                <p class="text-sm">Prova a modificare i filtri o aggiungi un nuovo pagamento</p>
                <div class="mt-4">
                    <button onclick="document.getElementById('reset-filters').click()"
                            class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors duration-200">
                        <i class="fas fa-undo mr-2"></i>Reset Filtri
                    </button>
                </div>
            </td>
        </tr>
    @endforelse
</tbody>
