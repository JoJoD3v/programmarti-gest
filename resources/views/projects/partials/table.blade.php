<tbody class="bg-white divide-y divide-gray-200">
    @forelse($projects as $project)
        <tr class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap">
                <div>
                    <div class="text-sm font-medium text-gray-900">
                        {{ $project->name }}
                    </div>
                    @if($project->description)
                        <div class="text-sm text-gray-500">
                            {{ Str::limit($project->description, 50) }}
                        </div>
                    @endif
                </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">{{ $project->client->full_name }}</div>
                <div class="text-sm text-gray-500">{{ $project->client->email }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                    {{ App\Models\Project::getProjectTypes()[$project->project_type] ?? $project->project_type }}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ $project->assignedUser ? $project->assignedUser->full_name : 'Non assegnato' }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                @if($project->total_cost)
                    €{{ number_format($project->total_cost, 2) }}
                @else
                    -
                @endif
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 py-1 text-xs font-medium rounded-full
                    @if($project->status === 'completed') bg-green-100 text-green-800
                    @elseif($project->status === 'in_progress') bg-blue-100 text-blue-800
                    @elseif($project->status === 'planning') bg-yellow-100 text-yellow-800
                    @else bg-gray-100 text-gray-800 @endif">
                    {{ App\Models\Project::getStatuses()[$project->status] ?? $project->status }}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ $project->start_date->format('d/m/Y') }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div class="flex justify-end space-x-2">
                    <a href="{{ route('projects.show', $project) }}" 
                       class="text-blue-600 hover:text-blue-900">
                        <i class="fas fa-eye"></i>
                    </a>
                    <a href="{{ route('projects.edit', $project) }}" 
                       class="text-yellow-600 hover:text-yellow-900">
                        <i class="fas fa-edit"></i>
                    </a>
                    <form action="{{ route('projects.destroy', $project) }}" 
                          method="POST" 
                          class="inline"
                          onsubmit="return confirm('Sei sicuro di voler eliminare questo progetto?')">
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
            <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                <i class="fas fa-search text-4xl mb-4 text-gray-300"></i>
                <p class="text-lg">Nessun progetto trovato per i filtri selezionati</p>
                <p class="text-sm">Prova a modificare i filtri o aggiungi un nuovo progetto</p>
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
