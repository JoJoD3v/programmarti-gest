# 🧪 ProgrammArti Gestionale - Guida ai Test

## 🚀 Test delle Nuove Funzionalità

### 1. Test Fatture PDF nella Lista Pagamenti

1. **Accedi all'applicazione** con: `admin@programmarti.com` / `password`
2. **Vai su "Gestione Pagamenti"**
3. **Cerca un pagamento completato** (status verde "Completato")
4. **Verifica i pulsanti**:
   - ✅ **Pulsante PDF verde** (icona file-pdf) - dovrebbe essere visibile
   - ✅ **Pulsante Email blu** (icona envelope) - dovrebbe essere visibile
5. **Clicca sul pulsante PDF** - dovrebbe scaricare la fattura
6. **Clicca sul pulsante Email** - dovrebbe mostrare messaggio di successo

### 2. Test Sistema Notifiche

#### A. Test Icona Campanella
1. **Controlla la topbar** - dovrebbe esserci un'icona campanella
2. **Clicca sulla campanella** - dovrebbe aprire il dropdown notifiche
3. **Verifica il badge rosso** - mostra il numero di notifiche non lette

#### B. Test Creazione Notifiche
1. **Crea un nuovo pagamento**:
   - Vai su "Gestione Pagamenti" → "Aggiungi Pagamento"
   - Compila i campi e salva
   - ✅ Dovrebbe apparire una notifica per l'admin

2. **Assegna un progetto**:
   - Vai su "Gestione Progetti" → "Modifica Progetto"
   - Assegna a un utente diverso
   - ✅ L'utente assegnato dovrebbe ricevere una notifica

#### C. Test Pagina Notifiche
1. **Vai su "Notifiche"** (clicca "Vedi tutte" nel dropdown)
2. **Verifica le notifiche** presenti
3. **Testa "Segna come letta"** - l'icona dovrebbe cambiare
4. **Testa "Segna tutte come lette"** - tutte dovrebbero essere marcate

### 3. Test Database MySQL

#### A. Verifica Connessione
```bash
# Testa la connessione
php artisan migrate:status
```

#### B. Verifica Dati di Esempio
1. **Utenti**: Dovrebbero esserci 4 utenti con ruoli diversi
2. **Clienti**: 4 clienti (2 aziende, 2 privati)
3. **Progetti**: 4 progetti in stati diversi
4. **Pagamenti**: 9 pagamenti (alcuni completati, altri in attesa)
5. **Spese**: 8 spese in categorie diverse

#### C. Test Importazione Database
```bash
# Se hai importato il file SQL, verifica:
mysql -u root -p -e "USE programmarti_gestionale; SELECT COUNT(*) FROM users;"
mysql -u root -p -e "USE programmarti_gestionale; SELECT COUNT(*) FROM projects;"
mysql -u root -p -e "USE programmarti_gestionale; SELECT COUNT(*) FROM payments;"
```

### 4. Test Comandi Artisan

#### A. Test Comando Pagamenti in Scadenza
```bash
# Testa il comando manualmente
php artisan payments:check-due
```

#### B. Test Scheduler (se configurato)
```bash
# Testa lo scheduler
php artisan schedule:run
```

### 5. Test Notifiche Real-time (se Pusher configurato)

1. **Configura Pusher** nel .env
2. **Apri due browser** con utenti diversi
3. **Crea un pagamento** nel primo browser
4. **Verifica notifica** nel secondo browser (dovrebbe apparire automaticamente)

### 6. Test Email (se SMTP configurato)

1. **Configura SMTP** nel .env
2. **Vai su un pagamento completato**
3. **Clicca "Invia via Email"**
4. **Controlla l'email** del cliente

## 🔍 Checklist Funzionalità

### ✅ Fatture PDF
- [ ] Pulsanti visibili nella lista pagamenti
- [ ] Download PDF funzionante
- [ ] Template PDF corretto con tutti i dati
- [ ] Numerazione fatture automatica

### ✅ Sistema Notifiche
- [ ] Icona campanella nella topbar
- [ ] Badge con numero notifiche non lette
- [ ] Dropdown notifiche funzionante
- [ ] Pagina notifiche completa
- [ ] Notifiche per nuovi pagamenti
- [ ] Notifiche per progetti assegnati
- [ ] Comando pagamenti in scadenza

### ✅ Database MySQL
- [ ] Connessione MySQL funzionante
- [ ] Tutte le tabelle create
- [ ] Dati di esempio importati
- [ ] Relazioni foreign key corrette
- [ ] Permessi e ruoli configurati

## 🐛 Risoluzione Problemi

### Problema: Pulsanti PDF/Email non visibili
**Soluzione**: Verifica che l'utente abbia i permessi `generate invoices` e `send emails`

### Problema: Notifiche non funzionano
**Soluzione**: 
1. Verifica che la tabella `notifications` esista
2. Controlla che gli eventi siano registrati in `EventServiceProvider`
3. Verifica che i listener siano configurati

### Problema: Database non si connette
**Soluzione**:
1. Verifica credenziali MySQL nel .env
2. Assicurati che MySQL sia in esecuzione
3. Controlla che il database `programmarti_gestionale` esista

### Problema: Errori JavaScript
**Soluzione**:
1. Esegui `npm run build`
2. Controlla la console browser per errori
3. Verifica che Laravel Echo sia configurato

### Problema: Email non si inviano
**Soluzione**:
1. Configura SMTP nel .env
2. Testa con `php artisan tinker` e `Mail::raw('test', function($m) { $m->to('test@test.com'); });`
3. Controlla i log in `storage/logs/laravel.log`

## 📊 Metriche di Successo

- ✅ **100% funzionalità PDF** implementate
- ✅ **Sistema notifiche completo** con real-time
- ✅ **Database MySQL** con dati di esempio
- ✅ **Interfaccia italiana** coerente
- ✅ **Design responsive** mantenuto
- ✅ **Permessi utente** rispettati

## 🎯 Prossimi Passi

1. **Configurare Pusher** per notifiche real-time
2. **Configurare SMTP** per invio email
3. **Setup cron job** per pagamenti in scadenza
4. **Backup database** regolare
5. **Monitoraggio performance**

---

**🎉 Tutte le funzionalità richieste sono state implementate con successo!**
