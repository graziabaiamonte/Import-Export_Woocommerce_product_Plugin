# WooCommerce Excel Import/Export Plugin

> Plugin WordPress per importare ed esportare prodotti WooCommerce tramite file Excel.

---

## 📋 Cosa Fa Il Plugin

Questo plugin ti permette di:

✅ **Importare prodotti** da file Excel (.xlsx, .xls, .csv) nel tuo negozio WooCommerce  
✅ **Esportare prodotti** esistenti verso Excel per modifiche offline  
✅ **Aggiornare prodotti** esistenti (il plugin riconosce prodotti già presenti tramite SKU)  
✅ **Gestire 26 tassonomie personalizzate** per catalogare prodotti medicali/chirurgici  
✅ **Filtrare prodotti** nell'area admin per tassonomie  
✅ **Ricevere report dettagliati** dopo ogni import con successi ed errori

---

## Chi Può Usarlo

- **Shop Manager**: Gestione catalogo senza competenze tecniche
- **Amministratori WordPress**: Importazioni massive da file fornitori
- **Data Entry**: Caricamento veloce centinaia/migliaia di prodotti

---

## Requisiti

Prima di installare il plugin, assicurati di avere:

| Requisito | Versione Minima |
|-----------|-----------------|
| WordPress | 6.0 o superiore |
| WooCommerce | 7.0 o superiore |
| PHP | 8.1 o superiore |
| Memoria PHP | 128MB o superiore (consigliato 256MB) |

**Come verificare:**
1. Vai in **WordPress** → **Dashboard** → **Home**
2. Controlla la versione nella sezione "A colpo d'occhio"
3. Per WooCommerce: vai in **WooCommerce** → **Stato** → **Sistema**

---

## Installazione

### Metodo 1: Caricamento ZIP (Consigliato)

1. **Scarica** il file `woocommerce-excel-importer.zip`
2. Accedi al **pannello WordPress** come Amministratore
3. Vai in **Plugin** → **Aggiungi nuovo**
4. Clicca **Carica plugin** in alto
5. Scegli il file ZIP e clicca **Installa ora**
6. Clicca **Attiva plugin**

### Metodo 2: Caricamento FTP

1. Estrai il file ZIP sul tuo computer
2. Carica la cartella `woocommerce-excel-importer` via FTP in `/wp-content/plugins/`
3. Vai in **WordPress** → **Plugin**
4. Trova "WooCommerce Excel Import/Export" e clicca **Attiva**

### Verifica Installazione

Dopo l'attivazione, dovresti vedere una nuova voce nel menu:

```
WooCommerce → Excel Import/Export
```

Se non vedi questa voce, controlla i **Requisiti** (specialmente PHP 8.1+ e WooCommerce attivo).

---

## Come Usare il Plugin

### Importare Prodotti da Excel

#### Step 1: Prepara il File Excel

Il tuo file Excel deve avere **30 colonne** in questo ordine:

| # | Colonna | Descrizione | Esempio |
|---|---------|-------------|---------|
| 1 | SKU | Codice univoco prodotto | `PROD-001` |
| 2 | TITLE | Nome prodotto | `Forbici chirurgiche` |
| 3 | DESCRIPTION | Descrizione completa | `Forbici in acciaio...` |
| 4 | PRICE | Prezzo (€) | `45.90` |
| 5-30 | Tassonomie | Vedi [Tassonomie Supportate](#tassonomie-supportate) | `10` |

**Importante:**
- La **prima riga** deve contenere i nomi delle colonne (header)
- **SKU** deve essere univoco (se esiste già, il prodotto viene aggiornato)
- **PRICE** può usare punto o virgola come decimale (`45.90` o `45,90`)
- Le colonne **vuote** sono ignorate (il prodotto non avrà quella tassonomia)

**Scarica il template:**
Per facilitare la preparazione, **esporta prima i prodotti esistenti** (vedi sotto). Il file esportato può essere modificato e reimportato.

#### Step 2: Importa il File

1. Vai in **WooCommerce** → **Excel Import/Export**
2. Nella sezione **"Import Products from Excel"**:
   - Clicca **Scegli file**
   - Seleziona il tuo file Excel
   - Clicca **Import Products**
3. Attendi qualche secondo (per file grandi può richiedere 1-2 minuti)

#### Step 3: Leggi il Report

Al termine dell'import vedrai un **report dettagliato**:

**Esempio report successo:**

```
✅ Import Completato

Statistiche:
- Prodotti creati: 85
- Prodotti aggiornati: 15
- Nuovi termini creati: 23
- Righe ignorate: 0
```

**Esempio report con errori:**

```
⚠️ Import Completato con Avvisi

Statistiche:
- Prodotti creati: 90
- Prodotti aggiornati: 8
- Nuovi termini creati: 18
- Righe ignorate: 2

Righe Ignorate (clicca per dettagli):
▼ Mostra 2 righe ignorate

| Riga | SKU | Motivo |
|------|-----|--------|
| 12 | PROD-123 | SKU già presente alla riga 5 |
| 25 | | SKU vuoto o mancante |
```

**Cosa fare se ci sono righe ignorate:**
1. Leggi il **motivo** per ogni riga
2. Correggi il file Excel
3. Reimporta il file (solo le righe corrette verranno elaborate)

### Esportare Prodotti verso Excel

1. Vai in **WooCommerce** → **Excel Import/Export**
2. Nella sezione **"Export Products to Excel"**:
   - Clicca **Export All Products**
3. Il browser scaricherà automaticamente il file `products-export-YYYY-MM-DD-HHMMSS.xlsx`

**Il file esportato contiene:**
- Tutti i prodotti WooCommerce pubblicati
- Tutte le 30 colonne (4 base + 26 tassonomie)
- Formato compatibile per re-import

**Usa l'export per:**
- 📥 Backup del catalogo
- ✏️ Modifiche offline (es. aggiornamento massivo prezzi)
- 🔄 Sincronizzazione con altri sistemi
- 📋 Template per nuovi prodotti (duplica e modifica righe)

---

## Tassonomie Supportate

Il plugin gestisce **26 tassonomie personalizzate** specifiche per cataloghi medicali/chirurgici:

| # | Tassonomia | Esempi Valori |
|---|------------|---------------|
| 1 | Quantity Per Box | `1`, `10`, `50`, `100`, `500` |
| 2 | Disposable/Reusable | `Disposable`, `Reusable` |
| 3 | Category | `Surgical`, `Ophthalmic`, `Dental` |
| 4 | Family of Instruments | `Scissors`, `Forceps`, `Tweezers`, `Retractors` |
| 5 | Tips Shape | `Straight`, `Curved`, `Angular` |
| 6 | Tips Type | `Blunt`, `Sharp`, `Atraumatic` |
| 7 | Working Type | `Standard`, `Micro`, `Delicate` |
| 8 | Tips Measurements | `0.3mm`, `0.5mm`, `1mm`, `2mm` |
| 9 | Material | `Stainless Steel`, `Titanium`, `Plastic` |
| 10 | Function | `Cutting`, `Grasping`, `Holding`, `Clamping` |
| 11 | Serrations | `Yes`, `No`, `Partial` |
| 12 | Handle Type | `Ring`, `Straight`, `Pistol Grip` |
| 13 | Lock Mechanism | `Ratchet`, `Box Lock`, `No Lock` |
| 14 | Sterilization | `Autoclavable`, `ETO`, `Gamma` |
| 15 | Standard | `ISO`, `CE`, `FDA` |
| 16 | Brand | `Grazia`, `Reverse Studio`, `Generic` |
| 17 | Origin | `Italy`, `Germany`, `USA`, `China` |
| 18 | Warranty | `1 Year`, `2 Years`, `Lifetime` |
| 19 | Packaging Type | `Blister`, `Pouch`, `Box`, `Tray` |
| 20 | Units Per Package | `1`, `5`, `10`, `50` |
| 21 | Storage Conditions | `Room Temp`, `Controlled Temp`, `Refrigerated` |
| 22 | Shelf Life | `1 Year`, `3 Years`, `5 Years`, `Unlimited` |
| 23 | Regulatory Class | `Class I`, `Class IIa`, `Class IIb`, `Class III` |
| 24 | Target Anatomy | `Eye`, `Skin`, `Bone`, `Tissue`, `Vascular` |
| 25 | Procedure Type | `Microsurgery`, `General Surgery`, `Ophthalmic` |
| 26 | Special Features | `Autoclave Safe`, `MRI Safe`, `Latex Free` |

**Note:**
- Ogni prodotto può avere **un valore per tassonomia** (es. un prodotto ha `Disposable` oppure `Reusable`, non entrambi)
- I termini vengono **creati automaticamente** se non esistono (es. se scrivi `Curved` la prima volta, viene creato)
- Le tassonomie **vuote** nel file Excel non vengono assegnate al prodotto

---

## Filtrare Prodotti per Tassonomie

Oltre a importare/esportare, il plugin aggiunge **filtri potenti** nella lista prodotti admin.

### Come Usare i Filtri

1. Vai in **Prodotti** → **Tutti i prodotti**
2. Sopra la lista prodotti, clicca **Filter by Taxonomies** (con badge numero filtri attivi)
3. Si apre un pannello con tutte le 26 tassonomie
4. **Seleziona** i termini che ti interessano (puoi selezionare più termini in più tassonomie)
5. Clicca **Filtra** (o premi Invio)
6. La lista si aggiorna mostrando solo prodotti che corrispondono

**Esempio:**
```
Voglio vedere solo forbici chirurgiche monouso:

✓ Family of Instruments: Scissors
✓ Disposable/Reusable: Disposable

Risultato: Solo prodotti che hanno ENTRAMBI questi attributi
```

**Reset filtri:**
- Clicca **Resetta** nel pannello filtri
- Oppure rimuovi i parametri dall'URL

---

## Domande Frequenti (FAQ)

### Generale

**Q: Il plugin funziona con prodotti variabili?**  
A: No, attualmente supporta solo **prodotti semplici** (Simple Product). I prodotti variabili richiederebbero colonne aggiuntive per gestire le variazioni.

**Q: Posso usare il plugin con altri tipi di catalogo (non medicale)?**  
A: Sì, ma le tassonomie sono specifiche per prodotti medicali. Per altri cataloghi, dovresti modificare il codice sorgente (`TaxonomyRegistrar.php`) per cambiare le tassonomie.

**Q: Il plugin supporta lingue diverse dall'italiano?**  
A: Attualmente le label sono in inglese nel codice. Il plugin è predisposto per traduzione tramite `.pot` file (vedi `text-domain: woo-excel-importer`).

**Q: Posso importare immagini prodotto?**  
A: No, nella versione attuale il plugin gestisce solo dati testuali (SKU, titolo, descrizione, prezzo, tassonomie). Le immagini vanno caricate manualmente o tramite altri plugin.

### Problemi Comuni

**Q: L'import fallisce con "Out of memory"**  
A: Aumenta il limite memoria PHP:
1. Modifica `php.ini`: `memory_limit = 256M`
2. Oppure in `wp-config.php`: `define('WP_MEMORY_LIMIT', '256M');`
3. Riavvia il server web

**Q: L'import si blocca senza errori**  
A: Probabilmente timeout PHP. Aumenta:
1. In `php.ini`: `max_execution_time = 300`
2. Oppure via `.htaccess`: `php_value max_execution_time 300`

**Q: Vedo "You do not have sufficient permissions"**  
A: Solo utenti con ruolo **Shop Manager** o **Administrator** possono usare il plugin. Verifica il tuo ruolo in **Utenti**.

**Q: Il file Excel non viene caricato**  
A: Controlla:
- ✅ Dimensione max: 10MB (modifica `upload_max_filesize` in `php.ini` se serve di più)
- ✅ Estensione: deve essere `.xlsx`, `.xls` o `.csv`
- ✅ MIME type: controlla che sia un vero file Excel (non .txt rinominato)

**Q: Alcuni prodotti non vengono importati**  
A: Leggi il report! Ogni riga ignorata ha un **motivo specifico**:
- `SKU vuoto` → aggiungi SKU
- `SKU già presente alla riga X` → duplicato nel file, rimuovi una delle due righe
- `Prezzo non valido` → controlla che il prezzo sia numerico (es. `45.90`, non `45,90 €`)
- `Titolo vuoto` → aggiungi un nome prodotto

**Q: Le tassonomie non vengono assegnate**  
A: Verifica che:
- ✅ I nomi colonne siano **esattamente** quelli elencati sopra (es. `QUANTITY PER BOX;` con punto e virgola)
- ✅ Le celle non siano vuote (celle vuote = nessuna assegnazione)
- ✅ I valori non contengano caratteri speciali (`<`, `>`, `"`, `'`)

### Performance

**Q: Quanto tempo ci vuole per importare 1000 prodotti?**  
A: Dipende dal server, ma in media:
- File piccolo (<1000 righe): 10-30 secondi
- File medio (1000-5000 righe): 1-3 minuti
- File grande (5000-10000 righe): 3-5 minuti

**Q: Posso importare mentre il sito è online?**  
A: Sì, ma per import molto grandi (>5000 prodotti) è consigliabile:
- Fare l'import in orari di basso traffico
- Disabilitare temporaneamente cache plugin
- Mettere il sito in "Manutenzione" se possibile

### Sicurezza

**Q: È sicuro lasciare il plugin attivato sempre?**  
A: Sì, il plugin implementa misure di sicurezza enterprise:
- Protezione CSRF (nonce WordPress)
- Controllo permessi (`manage_woocommerce`)
- Validazione input rigorosa
- Sanitizzazione completa dati

**Q: Chi può accedere alla funzione import/export?**  
A: Solo utenti con capability `manage_woocommerce`:
- Administrator ✅
- Shop Manager ✅
- Editor ❌
- Contributor ❌
- Subscriber ❌

---

## Risoluzione Problemi

### Errore: "WooCommerce non è installato o attivo"

**Causa:** Il plugin richiede WooCommerce per funzionare.

**Soluzione:**
1. Vai in **Plugin** → **Aggiungi nuovo**
2. Cerca "WooCommerce"
3. Installa e attiva WooCommerce
4. Riattiva questo plugin

### Errore: "Questo plugin richiede PHP 8.1 o superiore"

**Causa:** Il server usa una versione PHP obsoleta.

**Soluzione:**
1. Contatta il tuo hosting provider
2. Richiedi upgrade a **PHP 8.1** o superiore
3. Se hai accesso cPanel, vai in **Select PHP Version** e cambia versione

### Errore: "File troppo grande" durante upload

**Causa:** Limite upload PHP troppo basso.

**Soluzione:**

**Metodo 1: Modifica php.ini**
```ini
upload_max_filesize = 20M
post_max_size = 20M
```

**Metodo 2: Via .htaccess**
```apache
php_value upload_max_filesize 20M
php_value post_max_size 20M
```

**Metodo 3: Via wp-config.php**
```php
@ini_set('upload_max_filesize', '20M');
@ini_set('post_max_size', '20M');
```

### Import lento o timeout

**Causa:** Timeout PHP troppo basso per file grandi.

**Soluzione:**

**Metodo 1: php.ini**
```ini
max_execution_time = 300
max_input_time = 300
```

**Metodo 2: .htaccess**
```apache
php_value max_execution_time 300
```

**Metodo 3: Nel codice** (solo sviluppatori)
```php
set_time_limit(300);
```

### Prodotti creati ma tassonomie non assegnate

**Causa:** Nomi colonne Excel non corrispondono.

**Soluzione:**
1. Esporta un prodotto esistente per avere il template corretto
2. Verifica che i nomi colonne siano **identici** (incluso `;` finale)
3. Esempio corretto: `QUANTITY PER BOX;` (con punto e virgola)

### Menu plugin non compare

**Causa:** Permessi insufficienti o conflitto plugin.

**Soluzione:**
1. Verifica di essere **Administrator** o **Shop Manager**
2. Disattiva temporaneamente altri plugin WooCommerce
3. Riattiva questo plugin
4. Se persiste, controlla il file `debug.log` in `wp-content/`

---

## Supporto

Per assistenza tecnica o segnalazione bug:

- 📧 Email: [tuo-email@example.com]
- 💬 Crea una Issue su GitHub: [tuo-repo-github]
- 📖 Documentazione completa: [tuo-link-documentazione]

---

## Changelog

### Versione 1.1.0 (2026-02-27)
- ✨ Aggiunta freccetta CSS ai filtri tassonomie (consistenza UI WordPress)
- 🐛 Fix arrow indicator toggle state
- 📝 Documentazione completa README + SPECIFICHE.md

### Versione 1.0.0 (2026-02-25)
- 🎉 Release iniziale
- ✅ Import/Export prodotti WooCommerce
- ✅ 26 tassonomie personalizzate
- ✅ Gestione file grandi (chunk reading)
- ✅ Validazione rigorosa con report dettagliati
- ✅ Filtri admin per tassonomie

---

## Licenza

Questo plugin è distribuito sotto licenza **GPL v2 o successiva**.

---

## Crediti

Sviluppato da **Grazia Baiamonte** per **Reverse Studio**

### Librerie utilizzate

- **[PHPSpreadsheet](https://github.com/PHPOffice/PhpSpreadsheet)** - Gestione file Excel (lettura/scrittura .xlsx, .xls, .csv)
- **WordPress** - Framework CMS
- **WooCommerce** - Piattaforma e-commerce

### Perché Composer per le dipendenze?

Questo plugin utilizza **Composer** per gestire PHPSpreadsheet invece di includere direttamente la libreria nella cartella del plugin. Ecco perché questa è la scelta migliore:

#### ✅ Vantaggi di usare Composer

**1. Gestione automatica delle dipendenze**
- PHPSpreadsheet richiede altre 15+ librerie (dipendenze)
- Composer installa automaticamente tutto ciò che serve
- Senza Composer: dovresti scaricare e includere manualmente tutte le librerie

**2. Aggiornamenti semplici e sicuri**
```bash
# Con Composer (1 comando)
composer update phpoffice/phpspreadsheet

# Senza Composer: 
# - Scarica nuova versione manualmente
# - Sostituisci tutti i file
# - Controlla che funzioni
# - Rischio di dimenticare dipendenze
```

**3. Autoloading automatico (PSR-4)**
- Composer genera autoload ottimizzato per tutte le classi
- Il plugin carica solo le classi effettivamente utilizzate
- **Risultato:** Memoria risparmiata, caricamento più veloce

**4. Version locking per stabilità**
- Il file `composer.lock` garantisce che tutti usino le stesse versioni
- Previene il problema "funziona sul mio computer ma non sul tuo"
- Installazioni identiche su sviluppo, staging e produzione

**5. Dimensione plugin ridotta nel repository**
- Repository Git contiene solo il codice del plugin
- Le librerie esterne vengono scaricate al momento del build
- **Risultato:** Repository più leggero, Git più veloce

#### ❌ Svantaggi di includere la libreria direttamente

**1. Dimensione enorme**
- PHPSpreadsheet + dipendenze = **~8 MB** di codice
- Il plugin passerebbe da 50 KB a 8+ MB
- Upload più lenti, backup più pesanti

**2. Manutenzione difficile**
- Ogni aggiornamento richiede sostituzione manuale di centinaia di file
- Rischio di sovrascrivere modifiche personalizzate per errore
- Difficile capire quale versione è installata

**3. Conflitti con altri plugin**
- Se un altro plugin include PHPSpreadsheet con versione diversa
- **Risultato:** Errori "Cannot redeclare class" e crash del sito
- Composer gestisce automaticamente questi conflitti

**4. Autoload manuale**
- Dovresti scrivere 50+ righe di `require_once` per caricare tutte le classi
- Codice fragile e difficile da mantenere
- Caricamento più lento (tutte le classi vengono caricate anche se non usate)

#### 📦 Come funziona il build del plugin

Quando esegui `./build-plugin.sh`, il processo è questo:

```bash
1. Composer installa PHPSpreadsheet in /vendor/
2. Include solo le dipendenze di produzione (no dev tools)
3. Ottimizza l'autoloader per performance
4. Crea il file ZIP con tutto incluso
5. Risultato: Plugin completo e pronto per WordPress
```

**Per l'utente finale:** Il file ZIP contiene già tutto. Non serve installare Composer sul sito WordPress.

**Per lo sviluppatore:** Usa Composer per gestire le dipendenze in modo professionale.

#### 🔧 Nota tecnica

Il plugin finale contiene la cartella `vendor/` con PHPSpreadsheet già installato. Gli utenti WordPress non vedranno mai Composer - è uno strumento solo per sviluppatori durante il processo di build.

---

**🎉 Grazie per aver scelto WooCommerce Excel Import/Export Plugin!**

Se il plugin ti è utile, lascia una ⭐ su GitHub e condividilo con altri sviluppatori/shop manager!
