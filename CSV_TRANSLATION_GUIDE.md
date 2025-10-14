# CSV Translation Guide

## Overzicht

De CSV Translation functionaliteit in het admin panel maakt het mogelijk om vertalingen automatisch te genereren voor CSV bestanden met behulp van Google Cloud Translation API.

## Vereisten

- Admin rechten voor toegang tot `/admin/csv-translations`
- Google Cloud Service Account (zelfde als voor Gemini TTS)
- Google Cloud Translation API moet enabled zijn in je Google Cloud project

## CSV Formaat Vereisten

### Bestandsstructuur

- **Delimiter**: Puntkomma (`;`)
- **Max bestandsgrootte**: 10 MB
- **Max aantal rijen**: ~500 (anders kan timeout optreden)

### Kolom Structuur

| Kolom | Verplicht | Beschrijving |
|-------|-----------|--------------|
| `key` | Ja | Unieke identifier voor elke vertaling |
| `en` | Ja | Engels source text (brontaal) |
| Andere | Nee | Doeltaal kolommen (zie ondersteunde talen) |

### Voorbeeld CSV

```csv
key;en;es_AR;fr;de;it;nl
welcome;Welcome;;;;
hello;Hello world;;;;
goodbye;Goodbye;;;;
```

## Ondersteunde Talen

De volgende taal codes worden ondersteund als kolom headers:

| Code | Taal | Code | Taal |
|------|------|------|------|
| `es_AR` | Spanish (Argentina) | `ro` | Romanian |
| `fr` | French | `gr` | Greek |
| `de` | German | `sk` | Slovak |
| `it` | Italian | `lv` | Latvian |
| `nl` | Dutch | `bg` | Bulgarian |
| `fi` | Finnish | `al` | Albanian |
| `ca` | Catalan | | |

**Let op:** De codes `es_AR`, `gr`, en `al` worden automatisch gemapped naar de correcte Google Translate codes (`es`, `el`, `sq`).

## Gebruik

### Stap 1: Toegang tot CSV Translation

1. Log in als admin
2. Navigeer naar: **Admin Dashboard** → **CSV Translations**
3. Of ga direct naar: `https://your-domain.com/admin/csv-translations`

### Stap 2: CSV Bestand Voorbereiden

1. Maak een CSV bestand met de juiste structuur
2. Vul de `key` kolom met unieke identifiers
3. Vul de `en` kolom met de Engelse teksten
4. Laat doeltaal kolommen **leeg** voor nieuwe vertalingen
5. Bestaande vertalingen blijven behouden (worden niet overschreven)

### Stap 3: Upload en Vertaal

1. Klik op "Select CSV File"
2. Kies je CSV bestand
3. Klik op "Translate CSV"
4. Wacht terwijl de vertalingen gegenereerd worden (kan enkele minuten duren)
5. Het vertaalde bestand wordt automatisch gedownload

### Stap 4: Resultaat Controleren

Het gedownloade bestand heeft:
- Dezelfde structuur als het origineel
- Alle originele data intact
- Nieuwe vertalingen in de lege cellen
- Bestaande vertalingen ongewijzigd

## Verwerking Details

### Wat Gebeurt Er?

1. **Upload Validatie**
   - Controleert bestandsgrootte (max 10MB)
   - Valideert CSV formaat (delimiter, kolommen)
   - Controleert of `key` en `en` kolommen aanwezig zijn

2. **Parsing**
   - Leest CSV met semicolon delimiter
   - Maakt associatieve array van data

3. **Vertaling (Per Taal)**
   - Identificeert lege cellen per doeltaal
   - Batch translate alle lege cellen voor die taal
   - Update data met vertalingen
   - Herhaalt voor elke doeltaal

4. **Export**
   - Schrijft vertaalde data naar nieuwe CSV
   - Behoudt originele structuur en formatting
   - Download bestand wordt aangeboden

### Performance

- **Batch Processing**: Vertalingen worden per taal in batches verwerkt (max 100 teksten per batch)
- **Rate Limiting**: 0.5 seconde delay tussen batches
- **Timeout**: Max 60 seconden per request (voor grote bestanden kan dit te kort zijn)

### Verwachte Tijd

| Rijen | Talen | Geschatte Tijd |
|-------|-------|----------------|
| 10 | 12 | ~10-15 seconden |
| 50 | 12 | ~30-45 seconden |
| 100 | 12 | ~1-2 minuten |
| 200 | 12 | ~2-4 minuten |
| 500 | 12 | ~5-10 minuten |

## Kosten

Google Cloud Translation API kosten:
- **Prijs**: ~$20 per 1 miljoen karakters
- **Voorbeeld**: 100 rijen x 20 karakters gemiddeld x 12 talen = 24.000 karakters ≈ $0.48

**Tip**: Test eerst met een klein bestand (5-10 rijen) om kosten te controleren.

## Troubleshooting

### "Invalid CSV: Missing required column: en"

**Probleem**: CSV mist de `en` kolom  
**Oplossing**: Zorg dat de tweede kolom header `en` is

### "Translation API failed: 403 Forbidden"

**Probleem**: Translation API niet enabled in Google Cloud  
**Oplossing**:
1. Ga naar Google Cloud Console
2. Enable "Cloud Translation API"
3. Probeer opnieuw

### "Translation API failed: 429 Too Many Requests"

**Probleem**: API quota overschreden  
**Oplossing**:
1. Wacht enkele minuten
2. Check je quota in Google Cloud Console
3. Overweeg quota verhogen of bestand splitsen

### "Maximum execution time exceeded"

**Probleem**: Bestand te groot voor synchrone verwerking  
**Oplossing**:
1. Split CSV in kleinere bestanden (< 200 rijen per bestand)
2. Verwerk bestanden afzonderlijk
3. Merge handmatig na verwerking

### "File size must not exceed 10MB"

**Probleem**: CSV bestand te groot  
**Oplossing**:
1. Verwijder onnodige kolommen
2. Split in meerdere kleinere bestanden
3. Comprimeer niet - blijft CSV format

### Lege vertalingen in output

**Probleem**: Sommige cellen zijn nog steeds leeg na vertaling  
**Mogelijke Oorzaken**:
1. API error voor die specifieke taal (check logs)
2. Bron tekst was leeg in `en` kolom
3. Rate limit bereikt tijdens verwerking

**Oplossing**: Check `storage/logs/laravel.log` voor details

## Best Practices

### 1. Backup Maken
Bewaar altijd een kopie van je originele CSV voordat je vertaalt.

### 2. Incrementeel Werken
Voor grote datasets:
- Vertaal in batches van 100-200 rijen
- Merge resultaten handmatig
- Dit voorkomt timeouts en maakt debugging makkelijker

### 3. Test Eerst
Upload eerst een klein test bestand (5-10 rijen) om:
- Formaat te valideren
- Vertaalkwaliteit te controleren
- Kosten te schatten

### 4. Bestaande Vertalingen
Het systeem overschrijft GEEN bestaande vertalingen. Je kunt dus:
- Handmatige vertalingen behouden
- Alleen ontbrekende vertalingen aanvullen
- Meerdere keren hetzelfde bestand uploaden

### 5. Kwaliteit Controle
Controleer altijd de output:
- Automatische vertalingen zijn niet perfect
- Review kritieke teksten handmatig
- Pas waar nodig aan

## Logging

Alle vertaalacties worden gelogd in `storage/logs/laravel.log`:

```
[2025-01-15 14:30:15] local.INFO: CSV translation started
{"file":"translations.csv","rows":50,"languages":["es_AR","fr","de"]}

[2025-01-15 14:30:45] local.INFO: Batch translation completed
{"count":50,"target_language":"es"}

[2025-01-15 14:31:20] local.INFO: CSV translation completed
{"translations_needed":150,"translations_completed":150}
```

## Support

Bij problemen:
1. Check `storage/logs/laravel.log` voor error details
2. Valideer CSV formaat met online validator
3. Test met kleiner bestand
4. Contacteer development team met:
   - Error message
   - Log excerpt
   - Sample van CSV (eerste 5 regels)

---

**Versie**: 1.0  
**Laatst bijgewerkt**: Januari 2025

