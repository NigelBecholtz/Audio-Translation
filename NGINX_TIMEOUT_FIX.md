# Nginx Timeout Fix voor CSV Translations

## Probleem
504 Gateway Timeout bij het verwerken van grote CSV bestanden met vertalingen.

## Oplossing

### 1. Update Nginx configuratie
Bewerk je Nginx site config (meestal in `/etc/nginx/sites-available/chatgpt`):

```nginx
server {
    # ... bestaande configuratie ...
    
    location ~ \.php$ {
        # ... bestaande PHP configuratie ...
        
        # Verhoog timeouts voor lange requests
        fastcgi_read_timeout 600;
        fastcgi_send_timeout 600;
        
        # Verhoog buffer sizes
        fastcgi_buffer_size 128k;
        fastcgi_buffers 256 16k;
        fastcgi_busy_buffers_size 256k;
        fastcgi_temp_file_write_size 256k;
    }
    
    # Algemene timeouts
    proxy_connect_timeout 600;
    proxy_send_timeout 600;
    proxy_read_timeout 600;
    send_timeout 600;
}
```

### 2. Update PHP-FPM configuratie
Bewerk `/etc/php/8.1/fpm/pool.d/www.conf` (of jouw PHP versie):

```ini
; Verhoog request timeout
request_terminate_timeout = 600

; Verhoog max execution time
php_admin_value[max_execution_time] = 600
```

### 3. Herstart services
```bash
sudo systemctl restart nginx
sudo systemctl restart php8.1-fpm
```

### 4. Test de configuratie
```bash
# Check Nginx configuratie
sudo nginx -t

# Check of services draaien
sudo systemctl status nginx
sudo systemctl status php8.1-fpm
```

## Code optimalisaties (al toegepast)

1. **Kleinere batches**: Verlaagd van 20 naar 5 teksten per API call
2. **Kortere prompts**: Minder tokens = snellere response
3. **Lower temperature**: 0.1 voor snellere generatie
4. **Minder max_tokens**: 2000 in plaats van 4000

## Monitoring

Bekijk de logs om te zien hoelang vertalingen duren:

```bash
# Laravel logs
tail -f storage/logs/laravel.log | grep -i translation

# Nginx logs
sudo tail -f /var/log/nginx/error.log
```

## Verwachte timing

- **5 teksten vertalen**: ~3-5 seconden per taal
- **45 teksten totaal**: ~30-45 seconden per taal
- **12 talen**: ~6-9 minuten totaal

Met timeouts van 600 seconden (10 minuten) zou dit voldoende moeten zijn.

