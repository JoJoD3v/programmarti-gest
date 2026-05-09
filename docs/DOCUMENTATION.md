# ProgrammArti Gestionale — Documentazione Completa

> Documento consolidato di tutta la documentazione tecnica, guide di installazione, moduli implementati e verifica funzionalità.

---

## Indice

1. [Installazione e Setup](#1-installazione-e-setup)
2. [Moduli Implementati](#2-moduli-implementati)
3. [Guida Utente - Preventivi](#3-guida-utente---preventivi)
4. [Guida ai Test](#4-guida-ai-test)
5. [Verifica Finale](#5-verifica-finale)
6. [Fix Applicati](#6-fix-applicati)

---

## 1. Installazione e Setup

### Prerequisiti

- **MySQL Server** 8.0 o superiore
- **PHP** 8.1 o superiore
- **Composer** installato
- **Node.js** e **npm** installati

### Installazione Rapida

```bash
# 1. Clona il repository
git clone <repository-url>
cd programmarti-gestionale

# 2. Installa dipendenze PHP
composer install

# 3. Installa dipendenze JavaScript
npm install

# 4. Configura l'ambiente
cp .env.example .env
php artisan key:generate
```

### Configurazione Database

**Opzione A — Importazione SQL diretta (Consigliata)**

```bash
mysql -u root -p < database/install.sql
```

**Opzione B — Migrazioni Laravel**

```bash
mysql -u root -p -e "CREATE DATABASE programmarti_gestionale CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate
php artisan db:seed
```

### File .env — Configurazione Minima

```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=programmarti_gestionale
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="info@programmarti.com"
MAIL_FROM_NAME="ProgrammArti"

OPENAI_API_KEY=your-openai-api-key
```

> **Sicurezza**: Assicurarsi che `APP_DEBUG=false` in produzione.

### Completamento Setup

```bash
php artisan storage:link
npm run build
php artisan serve
```

Applicazione disponibile su: `http://127.0.0.1:8000`

### Credenziali di Default

> **Importante**: Cambiare tutte le password dopo il primo accesso.

| Ruolo | Email | Password |
|-------|-------|----------|
| Admin | admin@programmarti.com | password |
| Manager | manager@programmarti.com | password |
| Developer | developer@programmarti.com | password |
| Designer | designer@programmarti.com | password |

---

## 2. Moduli Implementati

### 2.1 Gestione Lavori (Works Management)

**Funzionalità:**
- Creazione e gestione attività per progetto
- Tipi: Bug, Miglioramenti, Da fare
- Stati: In Sospeso, Completato
- Assegnazione a utenti
- Filtri per progetto, stato, tipo
- Ricerca testuale

**Permessi richiesti:** `manage works` (admin, manager, employee)

**Tabella database:** `works` (project_id, name, description, type, assigned_user_id, status)

---

### 2.2 Gestione Appuntamenti (Appointments)

**Funzionalità:**
- Creazione appuntamenti con cliente e utente assegnato
- Stati: pending, completed, cancelled, absent
- Filtro per data e cliente (AJAX)
- Aggiornamento stato via AJAX

**Tabella database:** `appointments` (client_id, user_id, appointment_date, appointment_name, notes, status)

---

### 2.3 Modulo Preventivi

**Funzionalità:**
- Creazione preventivi con voci di lavoro
- Numerazione automatica formato `PREV-YYYY-NNNN`
- Calcolo totale con/senza IVA
- Miglioramento descrizioni voci tramite AI (OpenAI GPT-3.5)
- Generazione PDF con logo aziendale
- Download PDF
- Stati: draft, sent, accepted, rejected
- Filtri per cliente e stato

**Tabelle database:**
- `preventivi` (quote_number, client_id, description, total_amount, vat_enabled, vat_rate, subtotal_amount, vat_amount, status, ai_processed, pdf_path)
- `preventivo_items` (preventivo_id, description, cost, ai_enhanced_description)

**Permessi:** accessibile a tutti gli utenti autenticati.

---

### 2.4 Filtraggio AJAX Progetti

**Funzionalità:**
- Filtro real-time per nome, stato, tipo progetto
- Paginazione AJAX senza reload pagina
- Endpoint dedicato `GET /projects-filter`

---

### 2.5 Fatture PDF e Invio Email

**Funzionalità:**
- Generazione fattura PDF per pagamenti completati
- Numerazione fattura automatica (INV-YYYY-NNNN)
- Download diretto PDF
- Invio via email con allegato PDF

**Permessi:** `generate invoices`, `send emails`

---

### 2.6 Servizio OpenAI

Il servizio `App\Services\OpenAIService` utilizza GPT-3.5-turbo per migliorare le descrizioni delle voci di preventivo.

**Configurazione:**
```env
OPENAI_API_KEY=sk-...
```

In caso di errore API, il servizio utilizza descrizioni di fallback basate su keyword.

---

## 3. Guida Utente — Preventivi

### Creazione Preventivo

1. Vai su **Preventivi → Nuovo Preventivo**
2. Seleziona il cliente
3. Inserisci la descrizione generale del progetto
4. Aggiungi le voci di lavoro (descrizione + costo)
5. Attiva IVA se necessario (default 22%)
6. Salva il preventivo

### Miglioramento con AI

1. Apri un preventivo in stato **draft**
2. Clicca **"Migliora con AI"**
3. Il sistema invia le voci a OpenAI che genera descrizioni professionali
4. Le descrizioni originali vengono preservate; quelle AI sono aggiuntive
5. I totali IVA non vengono modificati dall'AI

### Generazione PDF

1. Apri il preventivo
2. Clicca **"Genera PDF"**
3. Il PDF viene salvato e reso disponibile per il download
4. Clicca **"Scarica PDF"** per scaricare

### Ciclo di Vita

```
draft → sent → accepted / rejected
```

---

## 4. Guida ai Test

### Test Preventivi

```bash
# Test calcolo IVA dopo AI
php test_ai_vat_preservation.php

# Test template PDF
php test_pdf_header.php

# Test fix applicati
php test_preventivo_fixes.php
```

> **Nota**: I file di test devono essere eseguiti dalla directory root del progetto, non esposti via web server.

### Test Database

```bash
# Verifica stato migrazioni
php artisan migrate:status

# Verifica connessione
php artisan tinker
>>> DB::connection()->getPdo();
```

### Test Comandi Artisan

```bash
# Controlla pagamenti in scadenza
php artisan payments:check-due

# Test scheduler
php artisan schedule:run

# Verifica rotte
php artisan route:list
```

### Test Email (richiede SMTP configurato)

```bash
php artisan tinker
>>> Mail::raw('Test', fn($m) => $m->to('test@test.com')->subject('Test'));
```

### Checklist Funzionalità

**Preventivi:**
- [ ] Creazione preventivo con voci
- [ ] Calcolo IVA corretto
- [ ] Miglioramento AI funzionante
- [ ] Generazione PDF
- [ ] Download PDF

**Pagamenti:**
- [ ] Pulsante PDF visibile (solo per pagamenti completati)
- [ ] Download fattura PDF
- [ ] Invio email fattura

**Lavori:**
- [ ] Creazione e assegnazione
- [ ] Filtri funzionanti
- [ ] Completamento lavoro

**Appuntamenti:**
- [ ] Filtro per data (AJAX)
- [ ] Aggiornamento stato
- [ ] Creazione e modifica

---

## 5. Verifica Finale

### Funzionalità Verificate

| Modulo | Stato |
|--------|-------|
| Gestione Utenti | ✅ Completo |
| Gestione Clienti | ✅ Completo |
| Gestione Progetti + filtri AJAX | ✅ Completo |
| Gestione Pagamenti + PDF + Email | ✅ Completo |
| Gestione Spese | ✅ Completo |
| Gestione Lavori | ✅ Completo |
| Gestione Appuntamenti + AJAX | ✅ Completo |
| Modulo Preventivi + AI + PDF | ✅ Completo |
| Database MySQL | ✅ Completo |

### Stack Tecnico

| Componente | Versione |
|-----------|---------|
| Laravel | 11.x |
| PHP | 8.1+ |
| MySQL | 8.0+ |
| Spatie Permissions | Ultima stabile |
| DomPDF | Ultima stabile |
| Tailwind CSS | 3.x |
| Vite | Ultima stabile |

### Permessi per Ruolo

| Permesso | Admin | Manager | Employee |
|----------|-------|---------|---------|
| manage users | ✅ | ❌ | ❌ |
| manage clients | ✅ | ✅ | ❌ |
| manage projects | ✅ | ✅ | ✅ |
| manage payments | ✅ | ✅ | ✅ |
| manage expenses | ✅ | ✅ | ✅ |
| manage works | ✅ | ✅ | ✅ |
| generate invoices | ✅ | ✅ | ❌ |
| send emails | ✅ | ✅ | ❌ |
| view dashboard | ✅ | ✅ | ✅ |

---

## 6. Fix Applicati

### Fix Preventivi

1. **Calcolo IVA dopo AI** — `calculateTotal()` viene chiamato dopo l'analisi AI per preservare i valori IVA
2. **Template PDF** — Rimossa sezione totale superiore duplicata; mantenuto totale in fondo
3. **Conflitti merge** risolti nel template `preventivi/pdf.blade.php`
4. **Messaggio risposta JavaScript** aggiornato

### Fix Pulsanti Preventivi

- Visibilità corretta dei pulsanti "Migliora con AI" e "Genera PDF"
- Stile coerente con il design system (#007BCE)

### Fix Routing Preventivi

- Parametro route corretto: `'preventivi' => 'preventivo'`
- Risolto conflitto tra resource route e route personalizzate

### Fix Works — Campo Description

- Aggiunta colonna `description` alla tabella `works`
- Visibile nell'interfaccia di creazione e modifica

### Fix Works — Visibilità Menu

- La voce "Gestione Lavori" è visibile nella sidebar per tutti i ruoli autorizzati

### Fix OpenAI API

- Gestione errori migliorata con fallback automatico
- Preservazione valori IVA durante l'elaborazione AI
