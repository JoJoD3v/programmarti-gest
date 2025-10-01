<x-app-layout>
    <x-slot name="header">
        Preventivo {{ $preventivo->quote_number }}
    </x-slot>

    <div class="space-y-6">
        <!-- Header Card -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">{{ $preventivo->quote_number }}</h2>
                        <p class="text-sm text-gray-500 mt-1">Creato il {{ $preventivo->created_at ? $preventivo->created_at->format('d/m/Y H:i') : 'N/A' }}</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <span class="px-3 py-1 text-sm font-medium rounded-full {{ $preventivo->status_color }}">
                            {{ $preventivo->status_label }}
                        </span>
                        @if($preventivo->ai_processed)
                            <span class="px-3 py-1 text-sm font-medium rounded-full bg-purple-100 text-purple-800">
                                <i class="fas fa-robot mr-1"></i>
                                AI Processato
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons-container">
                <div class="flex flex-wrap button-gap">
                    <a href="{{ route('preventivi.edit', $preventivo) }}"
                       class="text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200"
                       style="background-color: #007BCE;"
                       onmouseover="this.style.backgroundColor='#005B99'"
                       onmouseout="this.style.backgroundColor='#007BCE'">
                        <i class="fas fa-edit mr-2"></i>
                        Modifica
                    </a>

                    @if(!$preventivo->ai_processed)
                        <button id="enhanceWithAI"
                                class="px-4 py-2 rounded-lg font-medium transition-colors duration-200 border-0"
                                style="background-color: #7c3aed !important; color: white !important; border: none !important;"
                                onmouseover="this.style.backgroundColor='#6d28d9'"
                                onmouseout="this.style.backgroundColor='#7c3aed'">
                            <i class="fas fa-robot mr-2"></i>
                            Analizza con AI
                        </button>
                    @endif

                    <button id="generatePDF"
                            class="px-4 py-2 rounded-lg font-medium transition-colors duration-200 border-0"
                            style="background-color: #059669 !important; color: white !important; border: none !important;"
                            onmouseover="this.style.backgroundColor='#047857'"
                            onmouseout="this.style.backgroundColor='#059669'">
                        <i class="fas fa-file-pdf mr-2"></i>
                        Salva come PDF
                    </button>

                    @if($preventivo->pdf_path)
                        <a href="{{ route('preventivi.download-pdf', $preventivo) }}"
                           class="inline-block px-4 py-2 rounded-lg font-medium transition-colors duration-200 text-decoration-none border-0"
                           style="background-color: #2563eb !important; color: white !important; text-decoration: none !important; border: none !important;"
                           onmouseover="this.style.backgroundColor='#1d4ed8'"
                           onmouseout="this.style.backgroundColor='#2563eb'">
                            <i class="fas fa-download mr-2"></i>
                            Scarica PDF
                        </a>
                    @endif

                    <a href="{{ route('preventivi.index') }}"
                       class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 font-medium">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Torna alla Lista
                    </a>
                </div>
            </div>
        </div>

        <!-- Client Info -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Informazioni Cliente</h3>
            <div class="space-y-3">
                <div>
                    <span class="text-sm font-medium text-gray-500">Nome:</span>
                    <p class="text-sm text-gray-900">{{ $preventivo->client->full_name }}</p>
                </div>
                <div>
                    <span class="text-sm font-medium text-gray-500">Email:</span>
                    <p class="text-sm text-gray-900">{{ $preventivo->client->email }}</p>
                </div>
                @if($preventivo->client->phone)
                    <div>
                        <span class="text-sm font-medium text-gray-500">Telefono:</span>
                        <p class="text-sm text-gray-900">{{ $preventivo->client->phone }}</p>
                    </div>
                @endif
                @if($preventivo->client->address)
                    <div>
                        <span class="text-sm font-medium text-gray-500">Indirizzo:</span>
                        <p class="text-sm text-gray-900">{{ $preventivo->client->address }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Job Description -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Descrizione del Lavoro</h3>
            <p class="text-gray-700 whitespace-pre-wrap">{{ $preventivo->description }}</p>
        </div>

        <!-- Work Items -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Voci di Lavoro</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Descrizione
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Costo
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($preventivo->items as $item)
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900 mb-2">
                                        {{ $item->description }}
                                    </div>
                                    @if($item->ai_enhanced_description)
                                        <div class="text-sm text-gray-700 bg-gray-50 p-4 rounded-lg border-l-4 border-blue-500 mt-3">
                                            <div class="flex items-center mb-2">
                                                <i class="fas fa-robot text-blue-600 mr-2"></i>
                                                <span class="font-semibold text-blue-800 text-xs uppercase tracking-wide">Descrizione Dettagliata</span>
                                            </div>
                                            <p class="whitespace-pre-wrap italic leading-relaxed">{{ preg_replace('/^.*?€[\d,.]+ ?[-–]? ?/u', '', trim($item->ai_enhanced_description)) }}</p>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium text-gray-900">
                                    €{{ number_format($item->cost, 2, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <!-- Subtotal Row -->
                        <tr>
                            <td class="px-6 py-3 text-right text-sm font-semibold text-gray-700">
                                Subtotale:
                            </td>
                            <td class="px-6 py-3 text-right text-sm font-semibold text-gray-900">
                                €{{ number_format($preventivo->subtotal_amount, 2, ',', '.') }}
                            </td>
                        </tr>

                        @if($preventivo->vat_enabled)
                        <!-- VAT Row -->
                        <tr class="bg-blue-50">
                            <td class="px-6 py-3 text-right text-sm font-semibold text-blue-700">
                                IVA ({{ $preventivo->vat_rate }}%):
                            </td>
                            <td class="px-6 py-3 text-right text-sm font-semibold text-blue-900">
                                €{{ number_format($preventivo->vat_amount, 2, ',', '.') }}
                            </td>
                        </tr>
                        @endif

                        <!-- Total Row -->
                        <tr class="bg-gray-100">
                            <td class="px-6 py-4 text-right text-lg font-bold text-gray-900">
                                Totale:
                            </td>
                            <td class="px-6 py-4 text-right text-lg font-bold text-gray-900">
                                €{{ number_format($preventivo->total_amount, 2, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Loading Modal -->
    <div id="loadingModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-sm mx-auto">
            <div class="flex items-center">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mr-3"></div>
                <span id="loadingText">Elaborazione in corso...</span>
            </div>
        </div>
    </div>

    <!-- Custom Styles for Button Visibility -->
    <style>
        /* Force button visibility and styling */
        #enhanceWithAI, #generatePDF {
            display: inline-block !important;
            visibility: visible !important;
            opacity: 1 !important;
            min-height: 40px !important;
            cursor: pointer !important;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06) !important;
        }

        #enhanceWithAI:hover, #generatePDF:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
            transform: translateY(-1px);
        }

        /* Ensure icons are visible */
        .fas {
            color: inherit !important;
            opacity: 1 !important;
        }

        /* Action buttons container */
        .action-buttons-container {
            background-color: #f9fafb !important;
            padding: 1.5rem !important;
            border-bottom: 1px solid #e5e7eb !important;
        }

        /* Button gap */
        .button-gap {
            gap: 0.75rem !important;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const enhanceBtn = document.getElementById('enhanceWithAI');
            const generatePDFBtn = document.getElementById('generatePDF');
            const loadingModal = document.getElementById('loadingModal');
            const loadingText = document.getElementById('loadingText');

            // Debug: Check button visibility
            console.log('🔍 Button Visibility Check:');
            console.log('AI Button:', enhanceBtn ? 'Found' : 'Not Found');
            console.log('PDF Button:', generatePDFBtn ? 'Found' : 'Not Found');

            if (enhanceBtn) {
                console.log('AI Button styles:', window.getComputedStyle(enhanceBtn));
            }
            if (generatePDFBtn) {
                console.log('PDF Button styles:', window.getComputedStyle(generatePDFBtn));
            }

            function showLoading(text) {
                loadingText.textContent = text;
                loadingModal.classList.remove('hidden');
                loadingModal.classList.add('flex');
            }

            function hideLoading() {
                loadingModal.classList.add('hidden');
                loadingModal.classList.remove('flex');
            }

            // Enhance with AI
            if (enhanceBtn) {
                enhanceBtn.addEventListener('click', function() {
                    showLoading('Analisi con AI in corso...');

                    fetch(`{{ route('preventivi.enhance-ai', $preventivo) }}`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        hideLoading();
                        if (data.success) {
                            console.log('AI Enhancement completed:', data);
                            let message = '✅ Analisi AI completata! Le descrizioni sono state migliorate con successo.';

                            // Show that totals have been recalculated to ensure correct VAT display
                            if (data.totals_recalculated) {
                                message += '\n📋 I totali sono stati ricalcolati per garantire la corretta visualizzazione dell\'IVA.';
                            }

                            if (data.current_totals && data.current_totals.vat_enabled) {
                                message += `\n💰 Totale con IVA (${data.current_totals.vat_rate}%): €${parseFloat(data.current_totals.total_amount).toLocaleString('it-IT', {minimumFractionDigits: 2})}`;
                            } else if (data.current_totals) {
                                message += `\n💰 Totale: €${parseFloat(data.current_totals.total_amount).toLocaleString('it-IT', {minimumFractionDigits: 2})}`;
                            }

                            alert(message);
                            location.reload(); // Reload to show enhanced descriptions
                        } else {
                            alert('❌ Errore durante l\'analisi AI: ' + data.message);
                        }
                    })
                    .catch(error => {
                        hideLoading();
                        console.error('Error:', error);
                        alert('❌ Errore durante l\'analisi con AI. Riprova più tardi.');
                    });
                });
            }

            // Generate PDF
            generatePDFBtn.addEventListener('click', function() {
                showLoading('Salvataggio PDF in corso...');

                fetch(`{{ route('preventivi.generate-pdf', $preventivo) }}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    hideLoading();
                    if (data.success) {
                        // Show success message and reload to show download button
                        alert('📄 PDF salvato con successo! Ora puoi scaricarlo.');
                        location.reload();
                    } else {
                        alert('❌ Errore durante il salvataggio del PDF: ' + data.message);
                    }
                })
                .catch(error => {
                    hideLoading();
                    console.error('Error:', error);
                    alert('❌ Errore durante il salvataggio del PDF. Riprova più tardi.');
                });
            });
        });
    </script>
</x-app-layout>
