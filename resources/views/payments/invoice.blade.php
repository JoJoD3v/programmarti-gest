<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fattura #{{ $payment->invoice_number }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            line-height: 1.3;
            color: #333;
            margin: 0;
            padding: 15px;
        }

        .header {
            border-bottom: 3px solid #007BCE;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .company-info {
            float: left;
            width: 50%;
        }
        
        .invoice-info {
            float: right;
            width: 45%;
            text-align: right;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #007BCE;
            margin-bottom: 5px;
        }
        
        .company-tagline {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .invoice-title {
            font-size: 28px;
            font-weight: bold;
            color: #007BCE;
            margin-bottom: 10px;
        }
        
        .invoice-number {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
        
        .client-info {
            margin: 30px 0;
            padding: 20px;
            background-color: #f8f9fa;
            border-left: 4px solid #007BCE;
        }
        
        .client-info h3 {
            margin: 0 0 10px 0;
            color: #007BCE;
            font-size: 16px;
        }
        
        .project-details {
            margin: 15px 0;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .project-details h3 {
            margin: 0 0 15px 0;
            color: #007BCE;
            font-size: 16px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .detail-label {
            font-weight: bold;
            color: #666;
        }
        
        .payment-summary {
            margin: 15px 0;
            padding: 12px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        
        .payment-summary h3 {
            margin: 0 0 15px 0;
            color: #007BCE;
            font-size: 18px;
        }
        
        .amount-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
        }
        
        .total-amount {
            border-top: 2px solid #007BCE;
            padding-top: 10px;
            font-size: 18px;
            font-weight: bold;
            color: #007BCE;
        }
        
        .footer {
            margin-top: 25px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 10px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }
        
        .notes {
            margin: 15px 0;
            padding: 12px;
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            border-radius: 0 5px 5px 0;
        }
        
        .notes h4 {
            margin: 0 0 10px 0;
            color: #856404;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header clearfix">
        <div class="company-info">
            <div style="display: flex; align-items: center; margin-bottom: 10px;">
                <div style="width: 40px; height: 40px; background: white; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-right: 15px; overflow: hidden;">
                    <img src="{{ public_path('img/logo/LOGO.jpg') }}" alt="ProgrammArti Logo" style="width: 100%; height: 100%; object-fit: contain;">
                </div>
                <div>
                    <div class="company-name">ProgrammArti</div>
                    <div class="company-tagline">Soluzioni Digitali Innovative</div>
                </div>
            </div>
            <div style="margin-top: 15px; font-size: 11px; color: #666;">
                <strong>Indirizzo:</strong> Via Innovazione 123, 00100 Roma (RM)<br>
                <strong>P.IVA:</strong> 12345678901<br>
                <strong>Email:</strong> info@programmarti.com<br>
                <strong>Tel:</strong> +39 06 12345678
            </div>
        </div>
        
        <div class="invoice-info">
            <div class="invoice-title">FATTURA</div>
            <div class="invoice-number">#{{ $payment->invoice_number }}</div>
            <div style="margin-top: 10px; font-size: 11px;">
                <strong>Data Emissione:</strong> {{ now()->format('d/m/Y') }}<br>
                <strong>Data Scadenza:</strong> {{ $payment->due_date->format('d/m/Y') }}<br>
                @if($payment->paid_date)
                    <strong>Data Pagamento:</strong> {{ $payment->paid_date->format('d/m/Y') }}
                @endif
            </div>
            <div style="margin-top: 10px;">
                <span class="status-badge status-completed">Pagato</span>
            </div>
        </div>
    </div>

    <!-- Client Information -->
    <div class="client-info">
        <h3>Fatturato a:</h3>
        <div><strong>{{ $payment->client->full_name }}</strong></div>
        @if($payment->client->entity_type === 'business')
            <div>{{ $payment->client->first_name }} {{ $payment->client->last_name }}</div>
        @endif
        <div>{{ $payment->client->email }}</div>
        @if($payment->client->phone)
            <div>Tel: {{ $payment->client->phone }}</div>
        @endif
        @if($payment->client->vat_number)
            <div><strong>P.IVA:</strong> {{ $payment->client->vat_number }}</div>
        @endif
        @if($payment->client->tax_code)
            <div><strong>Codice Fiscale:</strong> {{ $payment->client->tax_code }}</div>
        @endif
        @if($payment->client->address)
            <div style="margin-top: 8px;">{{ $payment->client->address }}</div>
        @endif
    </div>

    <!-- Project Details -->
    <div class="project-details">
        <h3>Dettagli Progetto</h3>
        <div class="detail-row">
            <span class="detail-label">Nome Progetto:</span>
            <span>{{ $payment->project->name }}</span>
        </div>
        @if($payment->project->description)
        <div class="detail-row">
            <span class="detail-label">Descrizione:</span>
            <span>{{ $payment->project->description }}</span>
        </div>
        @endif
        <div class="detail-row">
            <span class="detail-label">Tipo Progetto:</span>
            <span>{{ App\Models\Project::getProjectTypes()[$payment->project->project_type] ?? $payment->project->project_type }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Data Inizio:</span>
            <span>{{ $payment->project->start_date->format('d/m/Y') }}</span>
        </div>
        @if($payment->project->end_date)
        <div class="detail-row">
            <span class="detail-label">Data Fine Prevista:</span>
            <span>{{ $payment->project->end_date->format('d/m/Y') }}</span>
        </div>
        @endif
        @if($payment->project->assignedUser)
        <div class="detail-row">
            <span class="detail-label">Responsabile Progetto:</span>
            <span>{{ $payment->project->assignedUser->full_name }}</span>
        </div>
        @endif
    </div>

    <!-- Payment Summary -->
    <div class="payment-summary">
        <h3>Riepilogo Pagamento</h3>
        <div class="amount-row">
            <span class="detail-label">Tipo Pagamento:</span>
            <span>{{ App\Models\Payment::getPaymentTypes()[$payment->payment_type] ?? $payment->payment_type }}</span>
        </div>
        @if($payment->installment_number)
        <div class="amount-row">
            <span class="detail-label">Numero Rata:</span>
            <span>{{ $payment->installment_number }} di {{ $payment->project->installment_count }}</span>
        </div>
        @endif
        <div class="amount-row">
            <span class="detail-label">Importo Base:</span>
            <span>€{{ number_format($payment->amount, 2) }}</span>
        </div>
        <div class="amount-row">
            <span class="detail-label">IVA (22%):</span>
            <span>€{{ number_format($payment->amount * 0.22, 2) }}</span>
        </div>
        <div class="amount-row total-amount">
            <span>TOTALE FATTURA:</span>
            <span>€{{ number_format($payment->amount * 1.22, 2) }}</span>
        </div>
    </div>

    <!-- Notes -->
    @if($payment->notes)
    <div class="notes">
        <h4>Note:</h4>
        <p>{{ $payment->notes }}</p>
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p>Questa fattura è stata generata automaticamente dal sistema gestionale ProgrammArti.</p>
        <p>Per qualsiasi domanda o chiarimento, contattaci all'indirizzo info@programmarti.com</p>
        <p style="margin-top: 15px; font-weight: bold;">Grazie per aver scelto ProgrammArti!</p>
    </div>
</body>
</html>
