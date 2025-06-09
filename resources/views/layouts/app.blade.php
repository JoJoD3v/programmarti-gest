<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Favicon -->
        <link rel="icon" type="image/jpeg" href="{{ asset('img/logo/LOGO.jpg') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

        <!-- Icons -->
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-50" style="font-family: 'Inter', sans-serif;">
        <div class="min-h-screen flex">
            <!-- Sidebar -->
            @include('layouts.sidebar')

            <!-- Main Content Area -->
            <div class="flex-1 flex flex-col">
                <!-- Topbar -->
                @include('layouts.topbar')

                <!-- Page Content -->
                <main class="flex-1 p-6 bg-gray-50 overflow-y-auto">
                    <!-- Page Heading -->
                    @isset($header)
                        <div class="mb-6">
                            <h1 class="text-2xl font-semibold text-gray-900">{{ $header }}</h1>
                        </div>
                    @endisset

                    <!-- Flash Messages -->
                    @if(session('success'))
                        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                            <span class="block sm:inline">{{ session('success') }}</span>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                            <span class="block sm:inline">{{ session('error') }}</span>
                        </div>
                    @endif

                    <!-- Content -->
                    {{ $slot }}
                </main>
            </div>
        </div>


        <script>
            // Notifications functionality
            let notificationsDropdown = null;
            let notificationBadge = null;

            document.addEventListener('DOMContentLoaded', function() {
                notificationsDropdown = document.getElementById('notifications-dropdown');
                notificationBadge = document.getElementById('notification-badge');

                // Load initial notification count
                updateNotificationCount();

                // Update notification count every 30 seconds
                setInterval(updateNotificationCount, 30000);

                // Close dropdown when clicking outside
                document.addEventListener('click', function(event) {
                    if (notificationsDropdown && !event.target.closest('.relative')) {
                        notificationsDropdown.classList.add('hidden');
                    }
                });
            });

            function toggleNotifications() {
                if (notificationsDropdown) {
                    if (notificationsDropdown.classList.contains('hidden')) {
                        loadRecentNotifications();
                        notificationsDropdown.classList.remove('hidden');
                    } else {
                        notificationsDropdown.classList.add('hidden');
                    }
                }
            }

            function updateNotificationCount() {
                fetch('/notifications/unread-count')
                    .then(response => response.json())
                    .then(data => {
                        if (notificationBadge) {
                            if (data.count > 0) {
                                notificationBadge.textContent = data.count;
                                notificationBadge.classList.remove('hidden');
                            } else {
                                notificationBadge.classList.add('hidden');
                            }
                        }
                    })
                    .catch(error => console.error('Error updating notification count:', error));
            }

            function loadRecentNotifications() {
                fetch('/notifications/recent')
                    .then(response => response.json())
                    .then(notifications => {
                        const notificationsList = document.getElementById('notifications-list');
                        if (notificationsList) {
                            if (notifications.length === 0) {
                                notificationsList.innerHTML = `
                                    <div class="p-4 text-center text-gray-500">
                                        <i class="fas fa-bell-slash text-2xl mb-2"></i>
                                        <p>Nessuna notifica</p>
                                    </div>
                                `;
                            } else {
                                notificationsList.innerHTML = notifications.map(notification => `
                                    <div class="p-4 border-b border-gray-100 hover:bg-gray-50 ${notification.read_at ? '' : 'bg-blue-50'}">
                                        <div class="flex items-start space-x-3">
                                            <div class="flex-shrink-0">
                                                ${getNotificationIcon(notification.type)}
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-900">${notification.title}</p>
                                                <p class="text-xs text-gray-600 mt-1">${notification.message}</p>
                                                <p class="text-xs text-gray-400 mt-1">${formatDate(notification.created_at)}</p>
                                            </div>
                                            ${!notification.read_at ? '<div class="w-2 h-2 bg-blue-500 rounded-full"></div>' : ''}
                                        </div>
                                    </div>
                                `).join('');
                            }
                        }
                    })
                    .catch(error => console.error('Error loading notifications:', error));
            }

            function getNotificationIcon(type) {
                switch(type) {
                    case 'payment_created':
                        return '<div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center"><i class="fas fa-credit-card text-green-600 text-sm"></i></div>';
                    case 'project_assigned':
                        return '<div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center"><i class="fas fa-project-diagram text-blue-600 text-sm"></i></div>';
                    case 'payment_due':
                        return '<div class="w-8 h-8 rounded-full bg-yellow-100 flex items-center justify-center"><i class="fas fa-exclamation-triangle text-yellow-600 text-sm"></i></div>';
                    default:
                        return '<div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center"><i class="fas fa-bell text-gray-600 text-sm"></i></div>';
                }
            }

            function formatDate(dateString) {
                const date = new Date(dateString);
                const now = new Date();
                const diffInMinutes = Math.floor((now - date) / (1000 * 60));

                if (diffInMinutes < 1) return 'Ora';
                if (diffInMinutes < 60) return `${diffInMinutes} min fa`;

                const diffInHours = Math.floor(diffInMinutes / 60);
                if (diffInHours < 24) return `${diffInHours} ore fa`;

                const diffInDays = Math.floor(diffInHours / 24);
                if (diffInDays < 7) return `${diffInDays} giorni fa`;

                return date.toLocaleDateString('it-IT');
            }

            // Real-time notifications with Laravel Echo (if available)
            if (typeof window.Echo !== 'undefined') {
                // Listen for general notifications
                window.Echo.private('notifications')
                    .listen('.payment.created', (e) => {
                        updateNotificationCount();
                        showToast('Nuovo Pagamento', e.message, 'success');
                    });

                // Listen for user-specific notifications
                window.Echo.private(`user.{{ auth()->id() }}`)
                    .listen('.project.assigned', (e) => {
                        updateNotificationCount();
                        showToast('Progetto Assegnato', e.message, 'info');
                    });
            }

            // Toast notification function
            function showToast(title, message, type = 'info') {
                // Create toast element
                const toast = document.createElement('div');
                toast.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm transform transition-all duration-300 translate-x-full`;

                const bgColor = type === 'success' ? 'bg-green-500' :
                               type === 'error' ? 'bg-red-500' :
                               type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500';

                toast.classList.add(bgColor);

                toast.innerHTML = `
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <i class="fas fa-${type === 'success' ? 'check-circle' :
                                              type === 'error' ? 'exclamation-circle' :
                                              type === 'warning' ? 'exclamation-triangle' : 'info-circle'} text-white"></i>
                        </div>
                        <div class="ml-3 flex-1">
                            <p class="text-sm font-medium text-white">${title}</p>
                            <p class="text-sm text-white opacity-90">${message}</p>
                        </div>
                        <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;

                document.body.appendChild(toast);

                // Animate in
                setTimeout(() => {
                    toast.classList.remove('translate-x-full');
                }, 100);

                // Auto remove after 5 seconds
                setTimeout(() => {
                    toast.classList.add('translate-x-full');
                    setTimeout(() => {
                        if (toast.parentElement) {
                            toast.remove();
                        }
                    }, 300);
                }, 5000);
            }
        </script>
    </body>
</html>
