# Large CSV Translation - Queue-Based Background Processing

## Problem Solved
Previously, translating large CSV files (283,000+ rows) would timeout with a 504 Gateway Timeout error because:
- Processing took 20-30+ minutes
- Nginx timeout was ~60 seconds
- All processing happened synchronously during the HTTP request

## Solution Implemented
Queue-based background processing with real-time progress tracking.

## What Changed

### 1. Database Migration
- **New table**: `csv_translation_jobs`
- Tracks job status, progress, and output files
- Stores: status, progress, total items, processed items, target languages, error messages

### 2. New Model
- **CsvTranslationJob** model (`app/Models/CsvTranslationJob.php`)
- Methods for checking status: `isCompleted()`, `isFailed()`, `isProcessing()`
- Automatic progress percentage calculation

### 3. Queue Job
- **ProcessCsvTranslationJob** (`app/Jobs/ProcessCsvTranslationJob.php`)
- Handles translation in the background
- 1 hour timeout (configurable)
- Updates progress every 100 items
- Supports both standard and smart fallback modes

### 4. Updated Controller
- **CsvTranslationController** now dispatches jobs instead of processing synchronously
- New methods:
  - `status()` - Show translation status page
  - `statusApi()` - API endpoint for polling status
  - `download()` - Download completed translation

### 5. New Routes
```php
Route::get('/csv-translations/{job}/status', 'status')
Route::get('/csv-translations/{job}/status-api', 'statusApi')
Route::get('/csv-translations/{job}/download', 'download')
```

### 6. New Status View
- **status.blade.php** - Real-time progress tracking page
- Auto-refreshes every 3 seconds
- Shows progress bar, item counts, and completion status
- Download button appears when complete

### 7. Updated Upload Page
- File size limit increased: 10MB → 100MB
- Notice about background processing
- Users are redirected to status page after upload

## How It Works

1. **Upload**: User uploads CSV file (up to 100MB)
2. **Job Creation**: System creates a `CsvTranslationJob` record
3. **Queue Dispatch**: Job is sent to queue for background processing
4. **Redirect**: User is redirected to status page
5. **Polling**: Status page polls API every 3 seconds for updates
6. **Progress Updates**: Job updates progress in database every 100 items
7. **Completion**: Download button appears when finished

## Benefits

✅ **No more timeouts** - Processing happens in background  
✅ **Handle huge files** - 283,000+ rows, no problem  
✅ **Real-time progress** - See exactly how many items are translated  
✅ **Close browser** - Come back later to download  
✅ **Better user experience** - Clear status and progress indicators  
✅ **10x larger files** - 100MB vs 10MB limit  

## Configuration Required

### Queue Driver
The app needs a queue worker running. Options:

#### Option 1: Database Queue (Recommended for this setup)
Already configured in `config/queue.php`. Just need to:

```bash
# Run migration to create jobs table (if not exists)
php artisan queue:table
php artisan migrate

# Start queue worker
php artisan queue:work --timeout=3600
```

#### Option 2: Keep Using Sync (Immediate)
If you want to use the sync driver (processes immediately but still shows progress page):
- Set `QUEUE_CONNECTION=sync` in `.env`
- Job will process during the request but user sees status page

### Increase PHP Limits (php.ini)
For very large files:
```ini
max_execution_time = 3600
memory_limit = 512M
upload_max_filesize = 100M
post_max_size = 100M
```

### Nginx Configuration
Update `client_max_body_size`:
```nginx
client_max_body_size 100M;
```

## Running Queue Worker in Production

### Option 1: Supervisor (Recommended)
```bash
sudo apt-get install supervisor

# Create config: /etc/supervisor/conf.d/laravel-worker.conf
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --sleep=3 --tries=1 --timeout=3600
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/worker.log
```

### Option 2: Systemd Service
```bash
# Create /etc/systemd/system/laravel-queue.service
[Unit]
Description=Laravel Queue Worker

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /path/to/artisan queue:work --timeout=3600

[Install]
WantedBy=multi-user.target
```

### Option 3: Cron Job (Simple)
Add to crontab:
```bash
* * * * * cd /path/to/app && php artisan queue:work --stop-when-empty --timeout=3600
```

## Testing

1. Upload a large CSV file (283,000+ rows)
2. Watch the status page update in real-time
3. Progress bar shows completion percentage
4. Download button appears when finished

## Files Modified

- `app/Http/Controllers/Admin/CsvTranslationController.php` - Queue integration
- `resources/views/admin/csv-translations/index.blade.php` - Upload page updates
- `routes/web.php` - New routes

## Files Created

- `database/migrations/2025_10_16_065500_create_csv_translation_jobs_table.php`
- `app/Models/CsvTranslationJob.php`
- `app/Jobs/ProcessCsvTranslationJob.php`
- `resources/views/admin/csv-translations/status.blade.php`

## Notes

- Old `processSmartFallback()` method removed from controller (now in job)
- Job records are kept in database for audit/history
- Failed jobs show error message on status page
- Can implement job cleanup/pruning if needed

