@section('page-title', 'Notifiche')

<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <span>Le Mie Notifiche</span>
            @if($notifications->where('read_at', null)->count() > 0)
                <button onclick="markAllAsRead()" 
                        class="px-4 py-2 text-white rounded-lg transition-colors duration-200"
                        style="background-color: #007BCE;"
                        onmouseover="this.style.backgroundColor='#005B99'"
                        onmouseout="this.style.backgroundColor='#007BCE'">
                    <i class="fas fa-check-double mr-2"></i>
                    Segna Tutte Come Lette
                </button>
            @endif
        </div>
    </x-slot>

    <div class="space-y-4">
        @if($notifications->count() > 0)
            @foreach($notifications as $notification)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 {{ $notification->isUnread() ? 'border-l-4 border-l-blue-500' : '' }}">
                    <div class="p-6">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3">
                                    <div class="flex-shrink-0">
                                        @if($notification->type === 'payment_created')
                                            <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                                                <i class="fas fa-credit-card text-green-600"></i>
                                            </div>
                                        @elseif($notification->type === 'project_assigned')
                                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                                <i class="fas fa-project-diagram text-blue-600"></i>
                                            </div>
                                        @elseif($notification->type === 'payment_due')
                                            <div class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center">
                                                <i class="fas fa-exclamation-triangle text-yellow-600"></i>
                                            </div>
                                        @else
                                            <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center">
                                                <i class="fas fa-bell text-gray-600"></i>
                                            </div>
                                        @endif
                                    </div>
                                    
                                    <div class="flex-1">
                                        <h3 class="text-lg font-medium text-gray-900">{{ $notification->title }}</h3>
                                        <p class="text-gray-600 mt-1">{{ $notification->message }}</p>
                                        
                                        <div class="flex items-center space-x-4 mt-3 text-sm text-gray-500">
                                            <span>
                                                <i class="fas fa-clock mr-1"></i>
                                                {{ $notification->created_at->diffForHumans() }}
                                            </span>
                                            
                                            @if($notification->isUnread())
                                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                                    <i class="fas fa-circle mr-1" style="font-size: 6px;"></i>
                                                    Non Letta
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex items-center space-x-2 ml-4">
                                @if($notification->isUnread())
                                    <button onclick="markAsRead({{ $notification->id }})" 
                                            class="text-blue-600 hover:text-blue-800"
                                            title="Segna come letta">
                                        <i class="fas fa-check"></i>
                                    </button>
                                @endif
                                
                                <!-- Action buttons based on notification type -->
                                @if($notification->type === 'payment_created' && isset($notification->data['payment_id']))
                                    <a href="{{ route('payments.show', $notification->data['payment_id']) }}" 
                                       class="text-green-600 hover:text-green-800"
                                       title="Visualizza pagamento">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                @elseif($notification->type === 'project_assigned' && isset($notification->data['project_id']))
                                    <a href="{{ route('projects.show', $notification->data['project_id']) }}" 
                                       class="text-blue-600 hover:text-blue-800"
                                       title="Visualizza progetto">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                @elseif($notification->type === 'payment_due' && isset($notification->data['payment_id']))
                                    <a href="{{ route('payments.show', $notification->data['payment_id']) }}" 
                                       class="text-yellow-600 hover:text-yellow-800"
                                       title="Visualizza pagamento">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            <!-- Pagination -->
            <div class="mt-6">
                {{ $notifications->links() }}
            </div>
        @else
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="p-12 text-center">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 flex items-center justify-center">
                        <i class="fas fa-bell text-gray-400 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Nessuna Notifica</h3>
                    <p class="text-gray-600">Non hai ancora ricevuto notifiche.</p>
                </div>
            </div>
        @endif
    </div>

    <script>
        function markAsRead(notificationId) {
            fetch(`/notifications/${notificationId}/mark-read`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        function markAllAsRead() {
            fetch('/notifications/mark-all-read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
    </script>
</x-app-layout>
