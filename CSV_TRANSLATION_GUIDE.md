# CSV Translation Guide

## Overzicht

De CSV Translation feature in het Admin panel maakt het mogelijk om automatisch vertaalbestanden te genereren voor meerdere talen. De tool gebruikt OpenAI's GPT-3.5 om Engelse teksten te vertalen naar verschillende doeltalen.

## Toegang

**URL:** `/admin/csv-translations`

**Rechten:** Alleen toegankelijk voor admins

## CSV Formaat

### Structuur

Het CSV bestand moet de volgende structuur hebben:

```csv
key;en;es_AR;fr;de;it;nl;ro;gr;sk;lv;bg;fi;al;ca
create_support_ticket_wizard;Create a support ticket;;;;;;;;;;;;;
ticket_initiation_text1;Provide context related to your support request:;;;;;;;;;;;;;
```

### Vereisten

1. **Delimiter:** Puntkomma (`;`)
2. **Eerste rij:** Header met kolomnamen
3. **Eerste kolom:** `key` - Unieke identifier voor de vertaling
4. **Tweede kolom:** `en` - Engelse tekst (bron voor vertaling)
5. **Overige kolommen:** Taalcodes voor doeltalen (kunnen leeg zijn)
6. **Maximum grootte:** 10MB

### Ondersteunde Taalcodes

| Code    | Taal                  |
|---------|-----------------------|
| en      | English               |
| es_AR   | Spanish (Argentina)   |
| es      | Spanish               |
| fr      | French                |
| de      | German                |
| it      | Italian               |
| nl      | Dutch                 |
| ro      | Romanian              |
| gr      | Greek                 |
| sk      | Slovak                |
| lv      | Latvian               |
| bg      | Bulgarian             |
| fi      | Finnish               |
| al      | Albanian              |
| ca      | Catalan               |
| pt      | Portuguese            |
| ru      | Russian               |
| ja      | Japanese              |
| ko      | Korean                |
| zh      | Chinese               |
| ar      | Arabic                |
| hi      | Hindi                 |

## Gebruik

### Stap 1: Voorbereiding

1. Maak een CSV bestand met de vereiste structuur
2. Vul de `key` en `en` kolommen in
3. Voeg taalcodes toe als extra kolommen voor gewenste vertalingen

### Stap 2: Upload

1. Ga naar Admin Panel → CSV Translations
2. Klik op "Choose File" en selecteer je CSV bestand
3. Klik op "Upload & Translate"
4. Je krijgt een bevestiging dat het bestand wordt verwerkt

### Stap 3: Verwerking

- De vertaling gebeurt op de achtergrond (queue job)
- Grote bestanden kunnen enkele minuten duren
- Elke regel wordt vertaald naar alle opgegeven talen
- Er is een kleine delay tussen vertalingen om rate limiting te voorkomen

### Stap 4: Download

1. Wacht tot de status "Completed" is
2. Klik op "Download" in de Translation History tabel
3. Je ontvangt een CSV met alle vertalingen ingevuld

## Technische Details

### Processing Flow

```
1. Upload CSV → Opgeslagen in storage/app/public/csv-translations/input/
2. Dispatch TranslateCsvJob → Queue worker pakt job op
3. Parse CSV → Lees rijen en kolommen
4. Voor elke rij:
   - Lees English text
   - Vertaal naar elke target language
   - Sla vertaling op in memory
5. Export → Schrijf naar storage/app/public/csv-translations/
6. Status → Maak status.json bestand aan
```

### API Gebruik

- **Translation Engine:** OpenAI GPT-3.5 Turbo
- **Temperature:** 0.3 (voor consistente vertalingen)
- **Max Tokens:** 500 per vertaling
- **Rate Limiting:** 0.1 seconde delay tussen vertalingen

### Error Handling

Als een vertaling mislukt:
- De cel blijft leeg
- Processing gaat door met volgende vertaling
- Error wordt gelogd in `storage/logs/laravel.log`
- Status bestand toont error details

## Best Practices

### 1. Tekst Voorbereiding

✅ **Goed:**
- "Welcome to our application"
- "Click here to continue"
- "Your account has been created successfully"

❌ **Vermijd:**
- HTML tags: `<strong>Welcome</strong>` → gebruik plain text
- Code variabelen: `Hello {name}` → vertaal niet de variable names
- Zeer lange teksten (>500 woorden) → split in meerdere keys

### 2. Keys

✅ **Goed:**
- `welcome_message`
- `button_submit`
- `error_invalid_email`

❌ **Vermijd:**
- Spaties: `welcome message`
- Speciale tekens: `welcome@message`
- Te lange keys: `this_is_a_very_long_key_name_that_is_hard_to_read`

### 3. Batch Grootte

- **Klein bestand (<50 rijen):** ~2-5 minuten processing
- **Middelgroot (50-200 rijen):** ~5-15 minuten
- **Groot (>200 rijen):** ~15-30 minuten

Overweeg grote bestanden op te splitsen voor snellere processing.

## Troubleshooting

### "Upload failed: File too large"

**Oplossing:** 
- Bestand is groter dan 10MB
- Split het bestand in kleinere delen
- Of verwijder onnodige rijen

### "Processing stuck at X%"

**Oplossing:**
- Check of queue worker draait: `php artisan queue:work`
- Check logs: `storage/logs/laravel.log`
- Herstart queue worker indien nodig

### "Some translations are empty"

**Mogelijke oorzaken:**
- API rate limiting
- Ongeldige taalcode
- OpenAI API issue

**Oplossing:**
- Check logs voor specifieke errors
- Upload opnieuw voor ontbrekende vertalingen

### "Invalid CSV format"

**Oplossing:**
- Controleer delimiter (moet `;` zijn)
- Controleer dat eerste rij header is
- Controleer dat eerste twee kolommen `key` en `en` zijn

## Voorbeeld CSV

Zie `missingTranslations dev1-example.csv` in de root directory voor een voorbeeld bestand.

## Kosten

Elke vertaling kost:
- ~$0.0001 - $0.0003 per vertaling (GPT-3.5 Turbo)
- Voor 100 rijen × 10 talen = 1000 vertalingen ≈ $0.10 - $0.30

## Support

Bij problemen:
1. Check de logs: `storage/logs/laravel.log`
2. Check queue status: `php artisan queue:failed`
3. Contact de systeembeheerder

---

**Gemaakt voor Audio Translation Application**
**Versie:** 1.0
**Laatst bijgewerkt:** {{ now()->format('Y-m-d') }}

