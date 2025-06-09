@section('page-title', 'Dettagli Spesa')

<x-app-layout>
    <x-slot name="header">
        Dettagli Spesa
    </x-slot>

    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-semibold text-gray-900">Informazioni Spesa</h2>
                    <div class="flex space-x-2">
                        <a href="{{ route('expenses.edit', $expense) }}" 
                           class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg transition-colors duration-200">
                            <i class="fas fa-edit mr-2"></i>
                            Modifica
                        </a>
                        <a href="{{ route('expenses.index') }}" 
                           class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors duration-200">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Torna alla Lista
                        </a>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <div class="space-y-6">
                    <!-- Amount -->
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Importo</label>
                        <p class="text-2xl font-bold text-gray-900">€{{ number_format($expense->amount, 2) }}</p>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Descrizione</label>
                        <p class="text-gray-900">{{ $expense->description }}</p>
                    </div>

                    <!-- User -->
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Utente</label>
                        <div class="flex items-center mt-2">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3" style="background-color: #007BCE;">
                                @if($expense->user->profile_photo)
                                    <img src="{{ $expense->user->profile_photo_url }}" 
                                         alt="Profile" 
                                         class="w-full h-full rounded-full object-cover">
                                @else
                                    <i class="fas fa-user text-white"></i>
                                @endif
                            </div>
                            <div>
                                <p class="text-gray-900 font-medium">
                                    <a href="{{ route('users.show', $expense->user) }}" style="color: #007BCE;" class="hover:opacity-80">
                                        {{ $expense->user->full_name }}
                                    </a>
                                </p>
                                <p class="text-sm text-gray-600">{{ $expense->user->getRoleNames()->first() }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Category -->
                    @if($expense->category)
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Categoria</label>
                        <span class="inline-flex px-3 py-1 text-sm font-medium rounded-full bg-purple-100 text-purple-800">
                            {{ App\Models\Expense::getCategories()[$expense->category] ?? $expense->category }}
                        </span>
                    </div>
                    @endif

                    <!-- Date -->
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Data Spesa</label>
                        <p class="text-gray-900">{{ $expense->expense_date->format('d/m/Y') }}</p>
                    </div>

                    <!-- Timestamps -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-6 border-t border-gray-200">
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Data Creazione</label>
                            <p class="text-gray-900">{{ $expense->created_at->format('d/m/Y H:i') }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-500">Ultimo Aggiornamento</label>
                            <p class="text-gray-900">{{ $expense->updated_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
