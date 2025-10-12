<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Earnings Strategy Dashboard')</title>
    
    <!-- PWA Meta Tags -->
    <meta name="description" content="Earnings Anticipation Trading Strategy - Find trading opportunities before earnings announcements">
    <meta name="theme-color" content="#2563eb">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Earnings Strategy">
    <meta name="mobile-web-app-capable" content="yes">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="/manifest.json">
    
    <!-- Favicon & App Icons -->
    <link rel="icon" type="image/png" sizes="192x192" href="/images/icons/icon-192x192.png">
    <link rel="apple-touch-icon" sizes="192x192" href="/images/icons/icon-192x192.png">
    <link rel="apple-touch-icon" sizes="512x512" href="/images/icons/icon-512x512.png">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- PWA Script -->
    <script src="/js/pwa.js" defer></script>
    
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .card-shadow {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .confidence-very-good { @apply bg-green-100 text-green-800 border-green-200; }
        .confidence-good { @apply bg-blue-100 text-blue-800 border-blue-200; }
        .confidence-neutral { @apply bg-yellow-100 text-yellow-800 border-yellow-200; }
        .confidence-bad { @apply bg-orange-100 text-orange-800 border-orange-200; }
        .confidence-very-bad { @apply bg-red-100 text-red-800 border-red-200; }
        .confidence-not-eligible { @apply bg-gray-100 text-gray-800 border-gray-200; }
        
        .animate-fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Navigation -->
    <nav class="gradient-bg shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-chart-line text-white text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <h1 class="text-xl font-semibold text-white">Trading Strategy Dashboard</h1>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <!-- PWA Install Button -->
                    <button id="pwa-install-btn" 
                            onclick="window.pwaFunctions.installPWA()" 
                            class="hidden bg-white text-blue-600 px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-50 transition-colors">
                        <i class="fas fa-download mr-2"></i>
                        Install App
                    </button>
                    
                    <!-- Notification Toggle -->
                    <button id="notification-toggle-btn" 
                            onclick="toggleNotifications()" 
                            class="bg-white bg-opacity-20 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-opacity-30 transition-colors">
                        <i class="fas fa-bell mr-2"></i>
                        <span id="notification-status">Enable Alerts</span>
                    </button>
                    
                    <span class="text-white text-sm">
                        <i class="fas fa-clock mr-1"></i>
                        Last Updated: <span id="last-updated">{{ now()->format('H:i') }}</span>
                    </span>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        @yield('content')
    </main>

    <!-- Loading Overlay -->
    <div id="loading-overlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg p-6 flex items-center space-x-3">
            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
            <span class="text-gray-700">Loading...</span>
        </div>
    </div>

    @stack('scripts')
    
    <script>
        // Notification toggle handler
        async function toggleNotifications() {
            const statusEl = document.getElementById('notification-status');
            const isSubscribed = await window.pwaFunctions.checkNotificationStatus();
            
            if (isSubscribed) {
                // Unsubscribe
                await window.pwaFunctions.unsubscribeFromPushNotifications();
                statusEl.textContent = 'Enable Alerts';
                showToast('Notifications disabled', 'info');
            } else {
                // Subscribe
                const subscription = await window.pwaFunctions.subscribeToPushNotifications();
                if (subscription) {
                    statusEl.textContent = 'Disable Alerts';
                    showToast('Notifications enabled! You will receive trading alerts', 'success');
                } else {
                    showToast('Failed to enable notifications. Please check permissions.', 'error');
                }
            }
        }
        
        // Check notification status on load
        window.addEventListener('load', async () => {
            const statusEl = document.getElementById('notification-status');
            const isSubscribed = await window.pwaFunctions.checkNotificationStatus();
            if (isSubscribed) {
                statusEl.textContent = 'Disable Alerts';
            }
        });
        
        // Toast notification helper
        function showToast(message, type = 'info') {
            const colors = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                info: 'bg-blue-500',
                warning: 'bg-yellow-500'
            };
            
            const toast = document.createElement('div');
            toast.className = `fixed bottom-4 right-4 ${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg z-50 animate-fade-in`;
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.3s';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    </script>
</body>
</html>
