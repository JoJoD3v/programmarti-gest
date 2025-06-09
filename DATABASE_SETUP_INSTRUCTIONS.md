# 🗄️ ProgrammArti Gestionale - Istruzioni Setup Database MySQL

## 📋 Prerequisiti

- **MySQL Server** 8.0 o superiore
- **PHP** 8.1 o superiore
- **Composer** installato
- **Node.js** e **npm** installati

## 🚀 Installazione Rapida

### 1. Clona il Repository
```bash
git clone <repository-url>
cd programmarti-gestionale
```

### 2. Installa le Dipendenze
```bash
# Dipendenze PHP
composer install

# Dipendenze JavaScript
npm install
```

### 3. Configura l'Ambiente
```bash
# Copia il file di configurazione
cp .env.example .env

# Genera la chiave dell'applicazione
php artisan key:generate
```

### 4. Configura il Database MySQL

#### Opzione A: Importazione Automatica (Consigliata)
```bash
# 1. Accedi a MySQL
mysql -u root -p

# 2. Importa il database completo
mysql -u root -p < database_setup.sql

# 3. Esci da MySQL
exit
```

#### Opzione B: Setup Manuale
```bash
# 1. Crea il database
mysql -u root -p -e "CREATE DATABASE programmarti_gestionale CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. Esegui le migrazioni Laravel
php artisan migrate

# 3. Esegui i seeder per i dati di esempio
php artisan db:seed
```

### 5. Configura il File .env

Modifica il file `.env` con le tue credenziali MySQL:

```env
# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=programmarti_gestionale
DB_USERNAME=root
DB_PASSWORD=your_mysql_password

# Email Configuration (Opzionale)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="info@programmarti.com"
MAIL_FROM_NAME="ProgrammArti"

# Pusher Configuration (Opzionale - per notifiche real-time)
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your-pusher-app-id
PUSHER_APP_KEY=your-pusher-app-key
PUSHER_APP_SECRET=your-pusher-app-secret
PUSHER_APP_CLUSTER=eu
```

### 6. Configura lo Storage
```bash
# Crea il link simbolico per lo storage
php artisan storage:link
```

### 7. Compila gli Asset
```bash
# Sviluppo
npm run dev

# Produzione
npm run build
```

### 8. Avvia l'Applicazione
```bash
# Avvia il server di sviluppo
php artisan serve
```

L'applicazione sarà disponibile su: `http://127.0.0.1:8000`

## 👥 Utenti di Default

Il database include questi utenti di esempio:

| Ruolo | Email | Password | Descrizione |
|-------|-------|----------|-------------|
| **Admin** | admin@programmarti.com | password | Accesso completo |
| **Manager** | manager@programmarti.com | password | Gestione progetti e pagamenti |
| **Developer** | developer@programmarti.com | password | Sviluppatore |
| **Designer** | designer@programmarti.com | password | Designer |

## 📊 Dati di Esempio Inclusi

Il database viene popolato con:

- ✅ **4 Utenti** con ruoli diversi
- ✅ **4 Clienti** (2 aziende, 2 privati)
- ✅ **4 Progetti** in stati diversi
- ✅ **9 Pagamenti** (alcuni completati, altri in attesa)
- ✅ **8 Spese** in categorie diverse
- ✅ **4 Notifiche** di esempio
- ✅ **Permessi e Ruoli** configurati

## 🔧 Configurazioni Avanzate

### Notifiche Real-time (Opzionale)

Per abilitare le notifiche push in tempo reale:

1. **Registrati su Pusher**: https://pusher.com/
2. **Crea una nuova app** e ottieni le credenziali
3. **Aggiorna il .env** con le credenziali Pusher
4. **Riavvia il server**

### Email (Opzionale)

Per abilitare l'invio email delle fatture:

1. **Configura SMTP** (Gmail consigliato)
2. **Genera App Password** per Gmail
3. **Aggiorna il .env** con le credenziali email
4. **Testa l'invio** dalle impostazioni

### Scheduler (Produzione)

Per le notifiche automatiche dei pagamenti in scadenza:

```bash
# Aggiungi al crontab
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## 🛠️ Comandi Utili

```bash
# Controlla pagamenti in scadenza manualmente
php artisan payments:check-due

# Pulisci cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Reset database (ATTENZIONE: cancella tutti i dati)
php artisan migrate:fresh --seed
```

## 🔒 Sicurezza

- ✅ **Cambia le password** degli utenti di default
- ✅ **Configura HTTPS** in produzione
- ✅ **Aggiorna APP_KEY** nel .env
- ✅ **Configura firewall** MySQL
- ✅ **Backup regolari** del database

## 📞 Supporto

Per problemi o domande:

- 📧 **Email**: info@programmarti.com
- 🌐 **Website**: www.programmarti.com
- 📱 **Telefono**: +39 06 12345678

## 📝 Note Tecniche

- **Laravel**: 11.x
- **PHP**: 8.1+
- **MySQL**: 8.0+
- **Bootstrap**: 5.x
- **Font**: Inter
- **Colori**: #007BCE (Primary Blue)

---

**🎉 Buon lavoro con ProgrammArti Gestionale!**
