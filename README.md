# WooCommerce Excel Import/Export

Plugin professionale per WordPress/WooCommerce che permette l'importazione e l'esportazione massiva di prodotti tramite file Excel.

## Caratteristiche

- **Import/Export Excel**: Carica e scarica prodotti in formato Excel (.xlsx, .xls, .csv)
- **Gestione Memoria Ottimizzata**: Chunk reading per file grandi (migliaia di righe senza "Out of Memory")
- **Gestione Tassonomie**: 26 tassonomie personalizzate per cataloghi medicali
- **Validazione Rigida**: Controllo automatico di header, SKU e prezzi con 38+ scenari gestiti
- **Gestione Errori Robusta**: File malformati, celle vuote, valori inattesi - tutti gestiti con messaggi chiari
- **Feedback Dettagliato**: Report completi con statistiche, righe ignorate e suggerimenti per risolvere errori
- **Sicurezza**: Gestione completa dei nonce WordPress e sanitizzazione dati
- **Filtri Avanzati**: Sistema di filtri per prodotti basato su tassonomie
- **Interfaccia Admin**: Pagina dedicata in WooCommerce con gestione import/export
- **Zero Crash**: Nessun errore ferma il sistema, tutto viene loggato e riportato all'utente

## Requisiti

- WordPress 6.0+
- WooCommerce 7.0+
- PHP 8.1+
- Composer (per sviluppo)

## Installazione

1. Vai su **WordPress Admin → Plugin → Aggiungi nuovo**
2. Clicca **Carica plugin**
3. Seleziona il file `woocommerce-excel-importer.zip`
4. Clicca **Installa ora**
5. Clicca **Attiva plugin**

## Utilizzo

### Import Prodotti

1. Vai su **WooCommerce → Excel Import/Export**
2. Nella sezione **Import Products from Excel**, clicca **Scegli file**
3. Seleziona il tuo file Excel (massimo 10MB)
4. Clicca **Import Products**

Il plugin processa il file e mostra un riepilogo con:
- Prodotti creati
- Prodotti aggiornati
- Termini tassonomia creati
- Righe ignorate (con motivazione)

**Gestione File Grandi:**
- File < 5MB: lettura veloce in memoria
- File > 5MB: lettura ottimizzata a chunk (100 righe alla volta)
- Nessun limite teorico sulla dimensione: il plugin gestisce file con migliaia di righe senza "Out of Memory"
- Per maggiori dettagli tecnici, vedi [MEMORY-OPTIMIZATION.md](MEMORY-OPTIMIZATION.md)

### Export Prodotti

1. Vai su **WooCommerce → Excel Import/Export**
2. Nella sezione **Export Products to Excel**, clicca **Export Products**
3. Il browser scarica automaticamente il file Excel

Il file generato contiene:
- Tutti i prodotti WooCommerce pubblicati
- Tutte le colonne richieste (SKU, TITLE, DESCRIPTION, PRICE)
- Tutte le 26 tassonomie con i valori assegnati
- Formato direttamente reimportabile

### Formato File Excel

Il file Excel **deve contenere esattamente queste colonne** nella prima riga:

**Formato Header:**
- Gli header devono terminare con punto e virgola `;` (es. `SKU;`, `TITLE;`)
- Il plugin rimuove automaticamente il `;` durante l'import
- Il file export genera già gli header nel formato corretto

**Colonne Obbligatorie:**
- `SKU;` - Codice prodotto univoco (alfanumerico, `._-` ammessi)
- `TITLE;` - Nome prodotto
- `DESCRIPTION;` - Descrizione prodotto
- `PRICE;` - Prezzo (formato: `10.50` o `10,50`)

**Colonne Tassonomie (tutte obbligatorie, con `;`):**
- `QUANTITY PER BOX;`
- `DISPOSABLE/REUSABLE;`
- `CATEGORY;`
- `STEEL & TITANIUM INSTRUMENTS FAMILIES;`
- `BACKFLUSH TYPE;`
- `BACKFLUSH TIP;`
- `BYPASS TYPE;`
- `CHANDELIERS TYPE;`
- `DOSAGE;`
- `PACKAGING;`
- `GAS TYPE;`
- `GAUGE;`
- `ILLUMINATION CONNECTOR FOR:;`
- `ILLUMINATION TYPE;`
- `KNIVES & BLADES;`
- `LASER CONNECTOR;`
- `LASER FIBER;`
- `MIXING RATIO;`
- `PIC TYPE;`
- `TIP ANGLE;`
- `TIP TYPE;`
- `TUBING TYPE;`
- `TWEEZER;`
- `TWEEZER TYPE;`
- `USE FOR;`
- `% NaCl;`

**Note Importanti:**
- Tutte le colonne devono essere presenti (anche se vuote)
- Gli header devono terminare con `;` (punto e virgola)
- L'ordine delle colonne non è importante
- File generato dall'export è già nel formato corretto con `;`
- Celle vuote alla fine della riga header sono ignorate automaticamente

### Filtri Prodotti

Nella lista prodotti di WooCommerce (**Prodotti → Tutti i prodotti**) è disponibile un filtro **"Filtra per tassonomie"** che permette di:
- Filtrare prodotti per una o più tassonomie
- Selezionare multipli termini per tassonomia tramite checkbox
- Combinare filtri di tassonomie diverse

### Gestione Tassonomie

Nella pagina di modifica prodotto, ogni tassonomia appare come meta box laterale con:
- **Dropdown select**: per scegliere un termine esistente
- **Pulsante "Aggiungi nuovo"**: per creare termini al volo
- **Un termine per tassonomia**: ogni prodotto può avere solo un termine assegnato per tassonomia

### Impostazioni

Vai su **WooCommerce → Import/Export Settings** per configurare:

**Data Retention:**
- Checkbox per eliminare dati del plugin durante la disinstallazione
- Se attivo: rimuove tassonomie, termini e impostazioni
- I prodotti WooCommerce **non vengono mai eliminati**

## Validazione e Sanitizzazione

### Header File Excel
- Verifica presenza di tutte le colonne obbligatorie
- Blocca colonne extra non registrate
- Richiede tutte le 26 tassonomie

### Campo SKU
- Solo caratteri alfanumerici + `._-`
- Lunghezza massima 100 caratteri
- Rimozione automatica caratteri speciali

### Campo PRICE
- Deve essere numerico positivo
- Conversione automatica virgola → punto
- Rimozione simboli valuta e caratteri non numerici

### Tassonomie
- Solo tassonomie registrate sono accettate
- Creazione automatica termini durante import
- Un termine per prodotto per tassonomia

## Sicurezza

- **Nonce WordPress**: Tutti i form protetti con nonce
- **Capabilities**: Solo utenti con `manage_woocommerce` possono accedere
- **Sanitizzazione**: Tutti i dati POST/GET sanitizzati
- **Validazione**: Input validati prima del salvataggio

## Impostazioni Plugin

### Gestione Dati alla Disinstallazione

Il plugin include un'opzione per controllare cosa succede ai dati quando elimini il plugin:

1. Vai su **WooCommerce → Excel Import/Export**
2. Clicca su **⚙️ Plugin Settings** in alto
3. Nella sezione **Uninstall Options** puoi scegliere:

**Opzione: "Delete all plugin data when uninstalling"**
- ✓ **Se SPUNTATA**: Quando elimini il plugin, tutte le 26 tassonomie personalizzate e i loro termini verranno eliminati permanentemente dal database
- ✓ **Se NON SPUNTATA** (predefinito): Le tassonomie e i termini rimarranno nel database anche dopo l'eliminazione del plugin

**Importante:**
- I prodotti WooCommerce NON verranno MAI eliminati, solo i dati delle tassonomie
- Si consiglia di lasciare l'opzione non spuntata se prevedi di reinstallare il plugin in futuro
- Questa impostazione può essere modificata in qualsiasi momento prima della disinstallazione

## Risoluzione Problemi

### Errori Upload File

**File troppo grande**
- Limite: 10MB
- Soluzione: Dividi il file in parti più piccole

**File corrotto o non leggibile**
- Il file potrebbe essere danneggiato
- Soluzione: Ri-genera il file Excel o usa l'export come template

**Tipo file non valido**
- Formato supportato: .xlsx, .xls, .csv
- Soluzione: Converti il file nel formato corretto

### Errori Headers

**Errore: "Missing required columns"**
- Il file non contiene tutte le colonne obbligatorie (SKU, TITLE, DESCRIPTION, PRICE + 26 tassonomie)
- Soluzione: Usa il file generato dall'export come template

**Errore: "Unexpected columns found"**
- Il file contiene colonne non registrate
- Soluzione: Rimuovi colonne extra o usa solo quelle supportate dall'export

**Errore: "Duplicate column headers"**
- Stesso nome colonna appare più volte
- Soluzione: Verifica che ogni colonna abbia un nome univoco

### Errori Dati

**Righe Ignorate: "Invalid or empty SKU"**
- SKU vuoto, solo spazi, o contiene caratteri non ammessi
- Soluzione: SKU deve contenere solo alfanumerici, punti, trattini, underscore (max 100 caratteri)

**Righe Ignorate: "Invalid price format"**
- Prezzo vuoto, non numerico, o negativo
- Soluzione: Usa formato numerico positivo `10.50` o `10,50`

**Righe Ignorate: "Product title cannot be empty"**
- Campo TITLE vuoto o solo spazi
- Soluzione: Ogni prodotto deve avere un nome (max 200 caratteri)

**Righe Ignorate: "Duplicate SKU in file"**
- Stesso SKU appare più volte nel file
- Soluzione: Ogni SKU deve essere univoco nel file

**Righe Ignorate: "Empty row"**
- Riga completamente vuota
- Soluzione: Rimuovi righe vuote dal file Excel

### Import Report

Il plugin fornisce sempre un report dettagliato:
- ✓ **Prodotti Creati**: Nuovi prodotti aggiunti
- ↻ **Prodotti Aggiornati**: Prodotti esistenti modificati
- + **Termini Creati**: Nuove voci tassonomia
- ⚠ **Righe Ignorate**: Righe con errori (clicca per dettagli)

**Nessun Prodotto Importato**
- Se tutte le righe sono ignorate, controlla la tabella degli errori
- Correggi i problemi nel file Excel
- Ri-importa: i prodotti esistenti saranno aggiornati, non duplicati

### Export Vuoto

- Verifica di avere prodotti WooCommerce pubblicati
- Controlla che i prodotti siano di tipo "Semplice"
- Verifica che WooCommerce sia attivo e configurato

## Sviluppo

### Struttura File

```
src/
├── AdminPage.php          - Interfaccia admin
├── Plugin.php             - Bootstrap plugin
├── TaxonomyRegistrar.php  - Gestione tassonomie
├── TaxonomyService.php    - Operazioni CRUD termini
├── ProductService.php     - Gestione prodotti WooCommerce
├── ImportService.php      - Logica import
├── ExportService.php      - Logica export
├── ExcelReader.php        - Lettura file Excel (con chunk reading)
├── ExcelWriter.php        - Scrittura file Excel
├── ChunkReadFilter.php    - Filtro lettura a chunk per memoria ottimizzata
└── ImportReport.php       - Report import
```

### Dipendenze

- `phpoffice/phpspreadsheet`: ^2.0 - Gestione file Excel

### Build

```bash
# Installa dipendenze
composer install

# Genera ZIP per distribuzione
./build-plugin.sh
```

## Licenza

GPL v2 or later

## Supporto

Per problemi o domande:
- Controlla la sezione "Risoluzione Problemi"
- Verifica che WooCommerce sia attivo
- Verifica versione PHP >= 8.1

## Changelog

### 1.1.0
- Aggiunta gestione memoria ottimizzata con chunk reading
- File grandi (>5MB) ora processati senza "Out of Memory"
- Implementato IReadFilter per lettura a pezzi (100 righe alla volta)
- Aggiunta documentazione tecnica in MEMORY-OPTIMIZATION.md
- Ottimizzazioni cache PhpSpreadsheet
- Garbage collection automatica tra chunk

### 1.0.0
- Release iniziale
- Import/Export prodotti via Excel
- 26 tassonomie personalizzate
- Sistema filtri avanzato
- Validazione e sanitizzazione completa
- Gestione sicurezza con nonce WordPress
