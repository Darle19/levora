# Production Deployment Checklist — Levora

## Environment Variables (.env)

These MUST be changed from development defaults before going live:

```env
# CRITICAL - Security
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-production-domain.com

# CRITICAL - Move sessions/cache off SQLite to reduce write contention
SESSION_DRIVER=file
CACHE_STORE=file

# Mail - Configure real SMTP provider
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host.com
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="Levora"

# Amadeus - Switch to production credentials when ready
AMADEUS_BASE_URL=https://api.amadeus.com
AMADEUS_CLIENT_ID=your-production-key
AMADEUS_CLIENT_SECRET=your-production-secret
```

## Server Setup

```bash
# 1. Ensure storage directories exist and are writable
mkdir -p storage/framework/sessions
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/views
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# 2. Run migrations
php artisan migrate --force

# 3. Cache configuration (speeds up boot by ~50%)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Create storage symlink
php artisan storage:link

# 5. Set up log rotation (add to /etc/logrotate.d/laravel)
# /var/www/levora/storage/logs/*.log {
#     daily
#     rotate 14
#     compress
#     missingok
#     notifempty
# }
```

## PHP-FPM Configuration

Recommended for 4GB RAM / 2 cores:

```ini
; /etc/php/8.3/fpm/pool.d/www.conf
pm = dynamic
pm.max_children = 20
pm.start_servers = 5
pm.min_spare_servers = 3
pm.max_spare_servers = 10
pm.max_requests = 500
```

## Supervisor (Queue Worker)

```ini
; /etc/supervisor/conf.d/levora-worker.conf
[program:levora-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/levora/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/levora/storage/logs/worker.log
stopwaitsecs=3600
```

## Security Checklist

- [ ] APP_DEBUG=false
- [ ] APP_ENV=production
- [ ] HTTPS enabled (SSL certificate)
- [ ] Database file NOT publicly accessible
- [ ] .env file NOT committed to git
- [ ] APP_KEY is set and unique
- [ ] Amadeus test credentials replaced with production
- [ ] BCRYPT_ROUNDS=12 (default, do not lower)
