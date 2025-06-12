<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Appointments Debug</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-8">Appointments AJAX Debug Page</h1>
        
        <!-- Debug Info -->
        <div class="bg-white p-6 rounded-lg shadow mb-8">
            <h2 class="text-xl font-semibold mb-4">Debug Information</h2>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <strong>CSRF Token:</strong> 
                    <span id="csrf-token">{{ csrf_token() }}</span>
                </div>
                <div>
                    <strong>Current URL:</strong> 
                    <span id="current-url">{{ url()->current() }}</span>
                </div>
                <div>
                    <strong>Appointments Index URL:</strong> 
                    <span id="appointments-url">{{ route('appointments.index') }}</span>
                </div>
                <div>
                    <strong>User:</strong> 
                    <span id="current-user">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</span>
                </div>
            </div>
        </div>

        <!-- Test Buttons -->
        <div class="bg-white p-6 rounded-lg shadow mb-8">
            <h2 class="text-xl font-semibold mb-4">AJAX Tests</h2>
            <div class="space-x-4">
                <button id="test-basic-ajax" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    <i class="fas fa-flask mr-2"></i>Test Basic AJAX
                </button>
                <button id="test-appointments-filter" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                    <i class="fas fa-filter mr-2"></i>Test Appointments Filter
                </button>
                <button id="test-status-update" class="bg-purple-500 text-white px-4 py-2 rounded hover:bg-purple-600">
                    <i class="fas fa-edit mr-2"></i>Test Status Update
                </button>
            </div>
        </div>

        <!-- Console Output -->
        <div class="bg-black text-green-400 p-6 rounded-lg shadow">
            <h2 class="text-xl font-semibold mb-4 text-white">Console Output</h2>
            <div id="console-output" class="font-mono text-sm h-64 overflow-y-auto whitespace-pre-wrap"></div>
        </div>
    </div>

    <script>
        // Console logging override
        const consoleOutput = document.getElementById('console-output');
        const originalLog = console.log;
        const originalError = console.error;
        
        function addToConsole(message, type = 'log') {
            const timestamp = new Date().toLocaleTimeString();
            const color = type === 'error' ? 'text-red-400' : 'text-green-400';
            consoleOutput.innerHTML += `<span class="${color}">[${timestamp}] ${message}</span>\n`;
            consoleOutput.scrollTop = consoleOutput.scrollHeight;
        }
        
        console.log = function(...args) {
            originalLog.apply(console, args);
            addToConsole(args.join(' '), 'log');
        };
        
        console.error = function(...args) {
            originalError.apply(console, args);
            addToConsole(args.join(' '), 'error');
        };

        // Test functions
        document.getElementById('test-basic-ajax').addEventListener('click', function() {
            console.log('🧪 Testing basic AJAX...');
            
            fetch('/appointments-test-ajax', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                console.log('✅ Basic AJAX response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('✅ Basic AJAX data:', JSON.stringify(data));
            })
            .catch(error => {
                console.error('❌ Basic AJAX error:', error.message);
            });
        });

        document.getElementById('test-appointments-filter').addEventListener('click', function() {
            console.log('🔍 Testing appointments filter...');
            
            const url = '{{ route("appointments.index") }}?date={{ \Carbon\Carbon::today()->format("Y-m-d") }}';
            console.log('🌐 Filter URL:', url);
            
            fetch(url, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'text/html'
                }
            })
            .then(response => {
                console.log('✅ Filter response status:', response.status);
                return response.text();
            })
            .then(html => {
                console.log('✅ Filter HTML length:', html.length);
                console.log('✅ Filter HTML preview:', html.substring(0, 200));
            })
            .catch(error => {
                console.error('❌ Filter error:', error.message);
            });
        });

        document.getElementById('test-status-update').addEventListener('click', function() {
            console.log('🔄 Testing status update...');
            console.log('ℹ️ Note: This will fail without a valid appointment ID');
            
            fetch('/appointments/1/status', {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ status: 'completed' })
            })
            .then(response => {
                console.log('📡 Status update response status:', response.status);
                if (response.status === 404) {
                    console.log('ℹ️ 404 is expected if appointment ID 1 does not exist');
                    return { error: 'Appointment not found (expected)' };
                }
                return response.json();
            })
            .then(data => {
                console.log('📊 Status update data:', JSON.stringify(data));
            })
            .catch(error => {
                console.error('❌ Status update error:', error.message);
            });
        });

        console.log('🚀 Debug page loaded successfully');
        console.log('🔧 CSRF Token:', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
    </script>
</body>
</html>
