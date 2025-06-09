@section('page-title', 'Gestione Pagamenti')

<x-app-layout>
    <x-slot name="header">
        Gestione Pagamenti
    </x-slot>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <!-- Header with Filters and Add Button -->
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-900">Lista Pagamenti</h2>
                <a href="{{ route('payments.create') }}" 
                   class="text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200 hover:opacity-90"
                   style="background-color: #007BCE;"
                   onmouseover="this.style.backgroundColor='#005B99'"
                   onmouseout="this.style.backgroundColor='#007BCE'">
                    <i class="fas fa-plus mr-2"></i>
                    Aggiungi Pagamento
                </a>
            </div>

            <!-- Filters -->
            <form method="GET" class="flex flex-wrap gap-4">
                <div>
                    <select name="status" class="select-improved px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Tutti gli stati</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>In Attesa</option>
                        <option value="overdue" {{ request('status') === 'overdue' ? 'selected' : '' }}>Scaduto</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completato</option>
                    </select>
                </div>
                <div>
                    <select name="month" class="select-improved px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Tutti i mesi</option>
                        @for($i = 1; $i <= 12; $i++)
                            <option value="{{ $i }}" {{ request('month') == $i ? 'selected' : '' }}>
                                {{ DateTime::createFromFormat('!m', $i)->format('F') }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div>
                    <select name="year" class="select-improved px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Tutti gli anni</option>
                        @for($year = date('Y'); $year >= date('Y') - 5; $year--)
                            <option value="{{ $year }}" {{ request('year') == $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endfor
                    </select>
                </div>
                <button type="submit" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                    Filtra
                </button>
                <a href="{{ route('payments.index') }}" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">
                    Reset
                </a>
            </form>
        </div>

        <!-- Payments Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Progetto
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Cliente
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Importo
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Scadenza
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Stato
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Tipo
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Azioni
                        </th>
                    </tr>
                </thead>
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
                                <i class="fas fa-credit-card text-4xl mb-4 text-gray-300"></i>
                                <p class="text-lg">Nessun pagamento trovato</p>
                                <p class="text-sm">Inizia aggiungendo il tuo primo pagamento</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($payments->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $payments->appends(request()->query())->links('pagination.custom') }}
            </div>
        @endif
    </div>
</x-app-layout>
