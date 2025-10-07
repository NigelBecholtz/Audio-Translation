# 🗄️ MySQL Migration Guide

## **Waarom MySQL?**

**SQLite problemen in productie:**
- ❌ Database locks bij concurrent writes
- ❌ Geen multiple connections support
- ❌ Performance degradatie bij groei
- ❌ Queue workers + web requests = conflicts

**MySQL voordelen:**
- ✅ Multiple concurrent connections
- ✅ Production-ready voor hoge load
- ✅ Industry standard
- ✅ Betere backup/restore

---

## **🚀 Migratie Stappen**

### **1. MySQL Installatie (VPS)**

```bash
# Update packages
sudo apt update

# Installeer MySQL
sudo apt install mysql-server -y

# Secure installation
sudo mysql_secure_installation
```

**Antwoorden:**
- VALIDATE PASSWORD: No (of Yes als je wilt)
- Remove anonymous users: Yes
- Disallow root login remotely: Yes
- Remove test database: Yes
- Reload privilege tables: Yes

---

### **2. Database & User Aanmaken**

```bash
# Login als root
sudo mysql

# In MySQL console:
CREATE DATABASE audio_translation CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'admin'@'localhost' IDENTIFIED BY 'YOUR_STRONG_PASSWORD_HERE';
GRANT ALL PRIVILEGES ON audio_translation.* TO 'admin'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

### **3. Update .env (VPS)**

```bash
nano /var/www/chatgpt/.env
```

**Wijzig deze regels:**

```env
# OLD:
DB_CONNECTION=sqlite

# NEW:
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=audio_translation
DB_USERNAME=admin
DB_PASSWORD=YOUR_STRONG_PASSWORD_HERE
```

---

### **4. Data Export (van SQLite)**

**Op VPS (in je oude setup):**

```bash
cd /var/www/chatgpt

# Export users
php artisan tinker
```

In tinker:
```php
$users = \App\Models\User::all();
file_put_contents('users_backup.json', $users->toJson(JSON_PRETTY_PRINT));

$payments = \App\Models\Payment::all();
file_put_contents('payments_backup.json', $payments->toJson(JSON_PRETTY_PRINT));

$transactions = \App\Models\CreditTransaction::all();
file_put_contents('transactions_backup.json', $transactions->toJson(JSON_PRETTY_PRINT));

exit
```

**Download backups:**
```bash
# Download naar lokaal
scp your-user@your-vps:/var/www/chatgpt/*_backup.json ./
```

---

### **5. Run Migraties (MySQL)**

```bash
# Clear config
php artisan config:clear

# Test MySQL connection
php artisan migrate:status

# Run alle migraties
php artisan migrate --force

# Seed admin (als nodig)
php artisan db:seed --class=AdminUserSeeder
```

---

### **6. Data Import (optioneel)**

**Als je belangrijke data hebt:**

```bash
php artisan tinker
```

```php
// Import users
$usersData = json_decode(file_get_contents('users_backup.json'), true);
foreach ($usersData as $userData) {
    \App\Models\User::create($userData);
}

// Import payments
$paymentsData = json_decode(file_get_contents('payments_backup.json'), true);
foreach ($paymentsData as $paymentData) {
    \App\Models\Payment::create($paymentData);
}

// etc...
exit
```

---

### **7. Test & Verify**

```bash
# Test database
php artisan tinker
```

```php
\App\Models\User::count()
\App\Models\AudioFile::count()
exit
```

```bash
# Test app
curl http://chatgpt.optimasit.com

# Check logs
tail -f storage/logs/laravel.log
```

---

### **8. Restart Services**

```bash
# Restart workers
sudo supervisorctl restart audio-translation-worker:*

# Restart PHP-FPM
sudo systemctl restart php8.3-fpm

# Restart Nginx
sudo systemctl restart nginx
```

---

## **🔒 Security Tips**

1. **Backup .env:**
   ```bash
   cp .env .env.backup
   ```

2. **Strong password:**
   - Min 16 characters
   - Mix upper/lower/numbers/symbols

3. **Firewall:**
   ```bash
   # Only allow local MySQL connections
   sudo ufw deny 3306
   ```

---

## **📊 Performance Tuning (Later)**

Edit `/etc/mysql/mysql.conf.d/mysqld.cnf`:

```ini
[mysqld]
max_connections = 200
innodb_buffer_pool_size = 512M
innodb_log_file_size = 128M
query_cache_size = 64M
```

Restart:
```bash
sudo systemctl restart mysql
```

---

## **🔄 Rollback Plan**

Als er problemen zijn:

```bash
# Revert .env
nano /var/www/chatgpt/.env
# Change: DB_CONNECTION=sqlite

# Clear cache
php artisan config:clear

# Restart
sudo supervisorctl restart audio-translation-worker:*
sudo systemctl restart php8.3-fpm
```

---

## **✅ Checklist**

- [ ] MySQL geïnstalleerd
- [ ] Database + user aangemaakt
- [ ] .env geüpdatet
- [ ] Data gebackupt (SQLite)
- [ ] Migraties gerund
- [ ] Data geïmporteerd (indien nodig)
- [ ] App getest
- [ ] Services herstart
- [ ] Backup gemaakt

---

**Succes met de migratie!** 🚀

