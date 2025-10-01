<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preventivo {{ $preventivo->quote_number }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }

        .header {
            display: table;
            width: 100%;
            margin-bottom: 30px;
            border-bottom: 2px solid #007BCE;
            padding-bottom: 20px;
        }

        .header-left,
        .header-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .header-left {
            text-align: left;
        }

        .header-right {
            text-align: right;
            padding-left: 20px;
        }

        .company-logo {
            max-width: 150px;
            max-height: 80px;
            margin-bottom: 10px;
        }

        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #007BCE;
            margin-bottom: 5px;
        }

        .company-subtitle {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
        }

        .company-info {
            font-size: 11px;
            line-height: 1.6;
            color: #333;
        }

        .company-info-row {
            margin-bottom: 3px;
        }

        .company-info-label {
            font-weight: bold;
            color: #007BCE;
            display: inline-block;
            width: 80px;
        }

        .quote-info {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }

        .quote-info-left,
        .quote-info-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .quote-info-right {
            text-align: right;
        }

        .quote-number {
            font-size: 18px;
            font-weight: bold;
            color: #007BCE;
            margin-bottom: 10px;
        }

        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #007BCE;
            margin: 20px 0 10px 0;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }

        .client-info {
            background-color: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid #007BCE;
        }

        .info-row {
            margin-bottom: 8px;
        }

        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 120px;
        }

        .description-box {
            background-color: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .work-items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .work-items-table th,
        .work-items-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        .work-items-table th {
            background-color: #007BCE;
            color: white;
            font-weight: bold;
        }

        .work-items-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .cost-column {
            text-align: right;
            font-weight: bold;
        }

        .ai-enhanced {
            background-color: #f8f9fa;
            border-left: 4px solid #007BCE;
            border-radius: 4px;
            padding: 12px;
            margin-top: 8px;
            font-style: italic;
        }

        .ai-enhanced-header {
            color: #007BCE;
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .total-row {
            background-color: #007BCE !important;
            color: white;
            font-weight: bold;
            font-size: 14px;
        }

        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-draft { background-color: #f3f4f6; color: #374151; }
        .status-sent { background-color: #dbeafe; color: #1e40af; }
        .status-accepted { background-color: #dcfce7; color: #166534; }
        .status-rejected { background-color: #fee2e2; color: #dc2626; }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <!-- Left Column: Logo and Company Name -->
        <div class="header-left">
            <img src="{{ public_path('img/logo/LOGO.jpg') }}" alt="Company Logo" class="company-logo">
            <div class="company-name">ProgrammArti</div>
            <div class="company-subtitle">Gestionale - Preventivo</div>
        </div>

        <!-- Right Column: Company Information -->
        <div class="header-right">
            <div class="company-info">
                <div class="company-info-row">
                    ProgrammArti S.r.l.
                </div>
                <div class="company-info-row">
                    Via Val Maggia 123, 00100 Roma (RM)
                </div>
                <div class="company-info-row">
                    IT12345678901
                </div>
                <div class="company-info-row">
                    info@programmarti.it
                </div>
                <div class="company-info-row">
                    +39 06 1234567
                </div>
            </div>
        </div>
    </div>

    <!-- Quote Information -->
    <div class="quote-info">
        <div class="quote-info-left">
            <div class="quote-number">{{ $preventivo->quote_number }}</div>
            <div>Data: {{ $preventivo->created_at->format('d/m/Y') }}</div>
            <div>
                Stato:
                <span class="status-badge status-{{ $preventivo->status }}">
                    {{ $preventivo->status_label }}
                </span>
            </div>
        </div>
        <!-- Rimossa la sezione del totale superiore come richiesto -->
    </div>

    <!-- Client Information -->
    <div class="section-title">Informazioni Cliente</div>
    <div class="client-info">
        <div class="info-row">
            <span class="info-label">Nome:</span>
            {{ $preventivo->client->full_name }}
        </div>
        <div class="info-row">
            <span class="info-label">Email:</span>
            {{ $preventivo->client->email }}
        </div>
        @if($preventivo->client->phone)
        <div class="info-row">
            <span class="info-label">Telefono:</span>
            {{ $preventivo->client->phone }}
        </div>
        @endif
        @if($preventivo->client->address)
        <div class="info-row">
            <span class="info-label">Indirizzo:</span>
            {{ $preventivo->client->address }}
        </div>
        @endif
        @if($preventivo->client->tax_code)
        <div class="info-row">
            <span class="info-label">Codice Fiscale:</span>
            {{ $preventivo->client->tax_code }}
        </div>
        @endif
        @if($preventivo->client->vat_number)
        <div class="info-row">
            <span class="info-label">Partita IVA:</span>
            {{ $preventivo->client->vat_number }}
        </div>
        @endif
    </div>

    <!-- Job Description -->
    <div class="section-title">Descrizione del Lavoro</div>
    <div class="description-box">
        {!! nl2br(e($preventivo->description)) !!}
    </div>

    <!-- Work Items -->
    <div class="section-title">Dettaglio Voci di Lavoro</div>
    <table class="work-items-table">
        <thead>
            <tr>
                <th style="width: 70%;">Descrizione</th>
                <th style="width: 30%;">Costo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($preventivo->items as $item)
            <tr>
                <td>
                    <div style="font-weight: bold; margin-bottom: 8px;">
                        {!! nl2br(e($item->description)) !!}
                    </div>
                    @if($item->ai_enhanced_description)
                    <div class="ai-enhanced">
                        <div class="ai-enhanced-header">Descrizione Dettagliata</div>
                        <div>{!! nl2br(e($item->ai_enhanced_description)) !!}</div>
                    </div>
                    @endif
                </td>
                <td class="cost-column">
                    €{{ number_format($item->cost, 2, ',', '.') }}
                </td>
            </tr>
            @endforeach

            <!-- Subtotal Row -->
            <tr style="background-color: #f8f9fa; font-weight: bold;">
                <td style="text-align: right; font-size: 14px; padding: 8px 12px;">
                    <strong>SUBTOTALE:</strong>
                </td>
                <td class="cost-column" style="font-size: 14px; padding: 8px 12px;">
                    <strong>€{{ number_format($preventivo->subtotal_amount, 2, ',', '.') }}</strong>
                </td>
            </tr>

            @if($preventivo->vat_enabled)
            <!-- VAT Row -->
            <tr style="background-color: #e3f2fd; color: #1976d2; font-weight: bold;">
                <td style="text-align: right; font-size: 14px; padding: 8px 12px;">
                    <strong>IVA ({{ $preventivo->vat_rate }}%):</strong>
                </td>
                <td class="cost-column" style="font-size: 14px; padding: 8px 12px;">
                    <strong>€{{ number_format($preventivo->vat_amount, 2, ',', '.') }}</strong>
                </td>
            </tr>
            @endif

            <!-- Total Row -->
            <tr class="total-row">
                <td style="text-align: right; font-size: 16px;">
                    <strong>TOTALE PREVENTIVO:</strong>
                </td>
                <td class="cost-column" style="font-size: 16px;">
                    <strong>€{{ number_format($preventivo->total_amount, 2, ',', '.') }}</strong>
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Footer -->
    <div class="footer">
        <p>Preventivo generato automaticamente il {{ now()->format('d/m/Y H:i') }}</p>
        <p>ProgrammArti - Gestionale | Questo documento è stato generato elettronicamente</p>
    </div>
</body>
</html>
