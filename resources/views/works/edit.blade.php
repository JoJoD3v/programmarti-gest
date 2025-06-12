@section('page-title', 'Modifica Lavoro')

<x-app-layout>
    <x-slot name="header">
        Modifica Lavoro
    </x-slot>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-900">Modifica Lavoro: {{ $work->name }}</h2>
                <a href="{{ route('works.index') }}" 
                   class="text-gray-600 hover:text-gray-900">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Torna alla lista
                </a>
            </div>
        </div>

        <form action="{{ route('works.update', $work) }}" method="POST" class="p-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Project Selection -->
                <div>
                    <label for="project_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Progetto <span class="text-red-500">*</span>
                    </label>
                    <select name="project_id" 
                            id="project_id" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('project_id') border-red-500 @enderror"
                            required>
                        <option value="">Seleziona un progetto</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}" 
                                    {{ (old('project_id', $work->project_id) == $project->id) ? 'selected' : '' }}>
                                {{ $project->name }} - {{ $project->client->full_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('project_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Work Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Nome Lavoro <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="name"
                           id="name"
                           value="{{ old('name', $work->name) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-500 @enderror"
                           placeholder="Inserisci il nome del lavoro"
                           required>
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Description Field (Full Width) -->
            <div class="col-span-1 md:col-span-2">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                    Descrizione
                </label>
                <textarea name="description"
                          id="description"
                          rows="4"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('description') border-red-500 @enderror"
                          placeholder="Inserisci una descrizione dettagliata del lavoro (opzionale)">{{ old('description', $work->description) }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- Work Type -->
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-2">
                        Tipo Lavoro <span class="text-red-500">*</span>
                    </label>
                    <select name="type" 
                            id="type" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('type') border-red-500 @enderror"
                            required>
                        <option value="">Seleziona il tipo</option>
                        @foreach(App\Models\Work::getWorkTypes() as $key => $value)
                            <option value="{{ $key }}" {{ old('type', $work->type) === $key ? 'selected' : '' }}>
                                {{ $value }}
                            </option>
                        @endforeach
                    </select>
                    @error('type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Assigned Employee -->
                <div>
                    <label for="assigned_user_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Dipendente Assegnato <span class="text-red-500">*</span>
                    </label>
                    <select name="assigned_user_id" 
                            id="assigned_user_id" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('assigned_user_id') border-red-500 @enderror"
                            required>
                        <option value="">Seleziona un dipendente</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('assigned_user_id', $work->assigned_user_id) == $user->id ? 'selected' : '' }}>
                                {{ $user->full_name }} - {{ $user->getRoleNames()->first() }}
                            </option>
                        @endforeach
                    </select>
                    @error('assigned_user_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                        Stato <span class="text-red-500">*</span>
                    </label>
                    <select name="status" 
                            id="status" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('status') border-red-500 @enderror"
                            required>
                        @foreach(App\Models\Work::getStatuses() as $key => $value)
                            <option value="{{ $key }}" {{ old('status', $work->status) === $key ? 'selected' : '' }}>
                                {{ $value }}
                            </option>
                        @endforeach
                    </select>
                    @error('status')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Creation Date (Read-only) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Data Creazione
                    </label>
                    <input type="text" 
                           value="{{ $work->created_at->format('d/m/Y H:i') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50"
                           readonly>
                    <p class="mt-1 text-xs text-gray-500">La data di creazione non può essere modificata</p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end space-x-4 mt-6 pt-6 border-t border-gray-200">
                <a href="{{ route('works.index') }}" 
                   class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Annulla
                </a>
                <button type="submit" 
                        class="text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200 hover:opacity-90"
                        style="background-color: #007BCE;"
                        onmouseover="this.style.backgroundColor='#005B99'"
                        onmouseout="this.style.backgroundColor='#007BCE'">
                    <i class="fas fa-save mr-2"></i>
                    Aggiorna Lavoro
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
