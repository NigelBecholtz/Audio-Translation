# 🚦 Gemini TTS Rate Limiting

## Overzicht

Rate limiting is geïmplementeerd voor **Gemini 2.5 Pro TTS** om API quota problemen te voorkomen. Bij rate limit overschrijding wordt automatisch gefallback naar OpenAI TTS.

## Features

✅ **Per-user rate limiting** - Elk user heeft eigen rate limit  
✅ **Configureerbaar** - Pas limiten aan via `.env`  
✅ **Automatische fallback** - Schakelt over naar OpenAI TTS bij quota issues  
✅ **Detailed logging** - Alle rate limit events worden gelogd  
✅ **Cache-based** - Gebruikt Laravel cache voor tracking

## Standaard Configuratie

**Standaard limiet:** 60 requests per minuut (per user)

```env
# In .env (optioneel - standaard waarden)
GEMINI_RATE_LIMIT_ATTEMPTS=60
GEMINI_RATE_LIMIT_DECAY=1
```

## Hoe het werkt

### Flow Diagram
```
User maakt TTS request
    ↓
Check rate limit (60/min)
    ↓
[ONDER LIMIET]          [BOVEN LIMIET]
    ↓                        ↓
Gemini TTS             OpenAI TTS Fallback
    ↓                        ↓
Audio gegenereerd      Audio gegenereerd
```

### Per-User Tracking

Rate limits zijn **per user**:
- User A: 60 requests/min
- User B: 60 requests/min  
- etc.

Dit voorkomt dat één user anderen blokkeert.

## Configuratie Aanpassen

### Strengere Limieten (Google Free Tier)
```env
# Voor gratis Google Cloud accounts
GEMINI_RATE_LIMIT_ATTEMPTS=10
GEMINI_RATE_LIMIT_DECAY=1
```

### Hogere Limieten (Paid Plan)
```env
# Voor betaalde accounts met hogere quota
GEMINI_RATE_LIMIT_ATTEMPTS=100
GEMINI_RATE_LIMIT_DECAY=1
```

### Langere Time Windows
```env
# 300 requests per 5 minuten
GEMINI_RATE_LIMIT_ATTEMPTS=300
GEMINI_RATE_LIMIT_DECAY=5
```

## Monitoring

### Check Logs
Alle rate limit events worden gelogd:

```bash
# Windows
Get-Content storage/logs/laravel.log -Tail 50 | Select-String "rate limit"

# Linux/Mac  
tail -f storage/logs/laravel.log | grep "rate limit"
```

### Log Voorbeelden

**Rate limit bereikt:**
```
[2025-01-08 14:30:15] local.WARNING: Gemini TTS rate limit hit, using fallback
{"user_id":123,"error":"Rate limit exceeded for 'gemini_tts_123'"}
```

**Rate limit overschrijding:**
```
[2025-01-08 14:30:15] local.WARNING: Rate limit exceeded
{"key":"gemini_tts_123","attempts":60,"max_attempts":60,"decay_minutes":1}
```

## Fallback Behavior

Bij rate limit overschrijding:
1. ⚠️ Log warning met user info
2. 🔄 Switch automatisch naar OpenAI TTS
3. ✅ Audio wordt nog steeds gegenereerd
4. 📊 User merkt minimale vertraging

**Voordelen:**
- Geen failed requests
- Betere user experience
- Voorkomt quota errors

## Testing

### Handmatig Rate Limit Testen

```php
// In tinker: php artisan tinker
$rateLimiter = new \App\Services\RateLimiter();

// Check current attempts
$rateLimiter->remaining('gemini_tts_1', 60);

// Simulate hits
for ($i = 0; $i < 61; $i++) {
    $rateLimiter->hit('gemini_tts_1', 1);
}

// Clear rate limit
$rateLimiter->clear('gemini_tts_1');
```

## Cache Requirements

Rate limiting gebruikt Laravel's cache systeem:

### File Cache (Standaard - OK)
```env
CACHE_DRIVER=file
```
✅ Werkt, maar performance kan beter bij hoog volume

### Redis (Aanbevolen voor Productie)
```env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```
✅ Beste performance  
✅ Persistent over server restarts  
✅ Support voor distributed systems

## Google Cloud Quota

Check je quota in Google Cloud Console:
https://console.cloud.google.com/apis/api/texttospeech.googleapis.com/quotas

**Typische Quota's:**
- **Free Tier:** 100 requests/min, 1M characters/month
- **Paid Tier:** Hoger, afhankelijk van account

## Error Handling

### Scenario 1: Rate Limit Bereikt
```
User upload → Gemini rate limited → OpenAI TTS → Success ✅
```

### Scenario 2: Beide Services Down
```
User upload → Gemini fails → OpenAI fails → Error message ❌
```
*"Audio generation failed: Gemini error (Fallback also failed)"*

## Best Practices

1. **Monitor je quota** in Google Cloud Console
2. **Start conservatief** (10-20 req/min voor free tier)
3. **Verhoog geleidelijk** als je paid plan hebt
4. **Check logs regelmatig** voor rate limit patterns
5. **Overweeg Redis** voor productie

## Troubleshooting

### Rate limit te strikt?
```env
# Verhoog limiet
GEMINI_RATE_LIMIT_ATTEMPTS=100
```

### Te veel fallbacks naar OpenAI?
```env
# Verlaag limiet of upgrade Google plan
GEMINI_RATE_LIMIT_ATTEMPTS=30
```

### Rate limit reset niet?
```bash
# Clear cache
php artisan cache:clear

# Of via tinker
$rateLimiter = new \App\Services\RateLimiter();
$rateLimiter->clear('gemini_tts_' . $userId);
```

## API Costs

**Gemini TTS (Google):**
- Free tier: €0 tot quota op
- Paid: ~€16 per 1M characters

**OpenAI TTS (Fallback):**
- $0.015 per 1K characters
- €0.014 per 1K characters

Met rate limiting voorkom je onverwachte kosten! 💰

---

**Geïmplementeerd in Audio Translation Project**
