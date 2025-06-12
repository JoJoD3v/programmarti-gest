@section('page-title', 'Dettagli Lavoro')

<x-app-layout>
    <x-slot name="header">
        Dettagli Lavoro
    </x-slot>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-900">{{ $work->name }}</h2>
                <div class="flex space-x-2">
                    <a href="{{ route('works.index') }}" 
                       class="text-gray-600 hover:text-gray-900">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Torna alla lista
                    </a>
                    <a href="{{ route('works.edit', $work) }}" 
                       class="text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200 hover:opacity-90"
                       style="background-color: #007BCE;"
                       onmouseover="this.style.backgroundColor='#005B99'"
                       onmouseout="this.style.backgroundColor='#007BCE'">
                        <i class="fas fa-edit mr-2"></i>
                        Modifica
                    </a>
                </div>
            </div>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Work Information -->
                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Informazioni Lavoro</h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Nome Lavoro</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $work->name }}</p>
                            </div>

                            @if($work->description)
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Descrizione</label>
                                <div class="mt-1 text-sm text-gray-900 bg-gray-50 p-3 rounded-lg border">
                                    {!! nl2br(e($work->description)) !!}
                                </div>
                            </div>
                            @endif

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Tipo</label>
                                <span class="mt-1 inline-flex px-2 py-1 text-xs font-medium rounded-full
                                    @if($work->type === 'Bug') bg-red-100 text-red-800
                                    @elseif($work->type === 'Miglioramenti') bg-blue-100 text-blue-800
                                    @else bg-yellow-100 text-yellow-800 @endif">
                                    {{ $work->type }}
                                </span>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Stato</label>
                                <span class="mt-1 inline-flex px-2 py-1 text-xs font-medium rounded-full
                                    @if($work->status === 'Completato') bg-green-100 text-green-800
                                    @else bg-orange-100 text-orange-800 @endif">
                                    {{ $work->status }}
                                </span>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Data Creazione</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $work->created_at->format('d/m/Y H:i') }}</p>
                            </div>

                            @if($work->updated_at != $work->created_at)
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Ultima Modifica</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $work->updated_at->format('d/m/Y H:i') }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Project and Assignment Information -->
                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Progetto e Assegnazione</h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Progetto</label>
                                <div class="mt-1">
                                    <p class="text-sm font-medium text-gray-900">{{ $work->project->name }}</p>
                                    <p class="text-sm text-gray-500">{{ $work->project->client->full_name }}</p>
                                    <a href="{{ route('projects.show', $work->project) }}" 
                                       class="text-blue-600 hover:text-blue-900 text-sm">
                                        <i class="fas fa-external-link-alt mr-1"></i>
                                        Visualizza progetto
                                    </a>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Assegnato a</label>
                                <div class="mt-1">
                                    <p class="text-sm font-medium text-gray-900">{{ $work->assignedUser->full_name }}</p>
                                    <p class="text-sm text-gray-500">{{ $work->assignedUser->email }}</p>
                                    <p class="text-sm text-gray-500">{{ $work->assignedUser->getRoleNames()->first() }}</p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Stato Progetto</label>
                                <span class="mt-1 inline-flex px-2 py-1 text-xs font-medium rounded-full
                                    @if($work->project->status === 'completed') bg-green-100 text-green-800
                                    @elseif($work->project->status === 'in_progress') bg-blue-100 text-blue-800
                                    @elseif($work->project->status === 'planning') bg-yellow-100 text-yellow-800
                                    @else bg-gray-100 text-gray-800 @endif">
                                    {{ App\Models\Project::getStatuses()[$work->project->status] ?? $work->project->status }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            @if($work->status === 'In Sospeso')
            <div class="mt-8 pt-6 border-t border-gray-200">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Azioni Rapide</h3>
                <div class="flex space-x-4">
                    <form action="{{ route('works.mark-completed', $work) }}" 
                          method="POST" 
                          class="inline"
                          onsubmit="return confirm('Sei sicuro di voler contrassegnare questo lavoro come completato?')">
                        @csrf
                        @method('PATCH')
                        <button type="submit" 
                                class="text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200 hover:opacity-90"
                                style="background-color: #10B981;"
                                onmouseover="this.style.backgroundColor='#059669'"
                                onmouseout="this.style.backgroundColor='#10B981'">
                            <i class="fas fa-check mr-2"></i>
                            Segna come Completato
                        </button>
                    </form>
                </div>
            </div>
            @endif

            <!-- Delete Action -->
            <div class="mt-8 pt-6 border-t border-gray-200">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Zona Pericolosa</h3>
                <form action="{{ route('works.destroy', $work) }}" 
                      method="POST" 
                      class="inline"
                      onsubmit="return confirm('Sei sicuro di voler eliminare questo lavoro? Questa azione non può essere annullata.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" 
                            class="text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200 hover:opacity-90"
                            style="background-color: #EF4444;"
                            onmouseover="this.style.backgroundColor='#DC2626'"
                            onmouseout="this.style.backgroundColor='#EF4444'">
                        <i class="fas fa-trash mr-2"></i>
                        Elimina Lavoro
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
