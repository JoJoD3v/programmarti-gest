<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fattura #{{ $payment->invoice_number }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: linear-gradient(135deg, #002D4D 0%, #007BCE 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        
        .header p {
            margin: 10px 0 0 0;
            font-size: 16px;
            opacity: 0.9;
        }
        
        .content {
            background: #ffffff;
            padding: 30px;
            border: 1px solid #e0e0e0;
        }
        
        .invoice-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #007BCE;
        }
        
        .invoice-details h3 {
            margin: 0 0 15px 0;
            color: #007BCE;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding: 5px 0;
        }
        
        .detail-label {
            font-weight: bold;
            color: #666;
        }
        
        .amount-highlight {
            font-size: 24px;
            font-weight: bold;
            color: #007BCE;
            text-align: center;
            padding: 20px;
            background: #e3f2fd;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-radius: 0 0 10px 10px;
            border-top: 1px solid #e0e0e0;
        }
        
        .footer p {
            margin: 5px 0;
            font-size: 14px;
            color: #666;
        }
        
        .contact-info {
            margin: 20px 0;
            padding: 15px;
            background: #fff3cd;
            border-radius: 8px;
            border-left: 4px solid #ffc107;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #007BCE;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .btn:hover {
            background-color: #005B99;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>ProgrammArti</h1>
        <p>Fattura #{{ $payment->invoice_number }}</p>
    </div>

    <div class="content">
        <p>Gentile <strong>{{ $payment->client->full_name }}</strong>,</p>
        
        <p>Ti inviamo in allegato la fattura per il progetto <strong>"{{ $payment->project->name }}"</strong>.</p>

        <div class="invoice-details">
            <h3>Dettagli Fattura</h3>
            <div class="detail-row">
                <span class="detail-label">Numero Fattura:</span>
                <span>#{{ $payment->invoice_number }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Data Emissione:</span>
                <span>{{ now()->format('d/m/Y') }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Data Scadenza:</span>
                <span>{{ $payment->due_date->format('d/m/Y') }}</span>
            </div>
            @if($payment->paid_date)
            <div class="detail-row">
                <span class="detail-label">Data Pagamento:</span>
                <span>{{ $payment->paid_date->format('d/m/Y') }}</span>
            </div>
            @endif
            <div class="detail-row">
                <span class="detail-label">Progetto:</span>
                <span>{{ $payment->project->name }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Tipo Pagamento:</span>
                <span>{{ App\Models\Payment::getPaymentTypes()[$payment->payment_type] ?? $payment->payment_type }}</span>
            </div>
            @if($payment->installment_number)
            <div class="detail-row">
                <span class="detail-label">Rata:</span>
                <span>{{ $payment->installment_number }} di {{ $payment->project->installment_count }}</span>
            </div>
            @endif
        </div>

        <div class="amount-highlight">
            Importo Totale: €{{ number_format($payment->amount * 1.22, 2) }}
            <br>
            <small style="font-size: 14px; color: #666;">(IVA 22% inclusa)</small>
        </div>

        @if($payment->notes)
        <div class="contact-info">
            <strong>Note:</strong><br>
            {{ $payment->notes }}
        </div>
        @endif

        <p>La fattura è allegata a questa email in formato PDF.</p>

        <p>Se hai domande o necessiti di chiarimenti, non esitare a contattarci.</p>

        <div class="contact-info">
            <strong>Informazioni di Contatto:</strong><br>
            📧 Email: info@programmarti.com<br>
            📞 Telefono: +39 06 12345678<br>
            🏢 Indirizzo: Via Innovazione 123, 00100 Roma (RM)
        </div>

        <p>Grazie per aver scelto ProgrammArti per le tue esigenze digitali!</p>

        <p>Cordiali saluti,<br>
        <strong>Il Team ProgrammArti</strong></p>
    </div>

    <div class="footer">
        <p><strong>ProgrammArti</strong> - Soluzioni Digitali Innovative</p>
        <p>P.IVA: 12345678901 | info@programmarti.com | www.programmarti.com</p>
        <p style="font-size: 12px; margin-top: 15px;">
            Questa email è stata generata automaticamente dal nostro sistema gestionale.
        </p>
    </div>
</body>
</html>
