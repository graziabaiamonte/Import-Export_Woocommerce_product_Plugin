# WooCommerce Excel Import/Export Plugin

> Plugin WordPress per importare ed esportare prodotti WooCommerce tramite file Excel.



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

## Architettura e Scelte di Design

### 🏗️ Struttura del Codice

Il plugin è progettato seguendo i **principi SOLID** e le best practices di sviluppo enterprise PHP. Ogni classe ha una responsabilità specifica e ben definita.

```
src/
├── Plugin.php                  # Orchestrator principale (Dependency Injection Container)
├── AdminPage.php               # Controller per pagina admin import/export
├── TaxonomyRegistrar.php       # Gestione tassonomie custom e meta boxes
├── TaxonomyConfig.php          # Configurazione centralizzata tassonomie
├── ImportService.php           # Business logic import prodotti
├── ExportService.php           # Business logic export prodotti
├── ProductService.php          # CRUD operazioni prodotti WooCommerce
├── TaxonomyService.php         # Operazioni su termini tassonomie
├── ExcelReader.php             # Lettura file Excel (wrapper PHPSpreadsheet)
├── ExcelWriter.php             # Scrittura file Excel (wrapper PHPSpreadsheet)
├── ChunkReadFilter.php         # Filtro per lettura chunk-by-chunk (ottimizzazione memoria)
├── ImportReport.php            # Data object per report import
└── SecureFormHandler.php       # Trait per sicurezza (nonce + capabilities)

views/
└── admin-page.php              # Template HTML separato dalla logica

assets/
├── admin.css                   # Stili admin
└── admin.js                    # JavaScript per meta boxes tassonomie
```

### 🎯 Pattern Architetturali

#### 1. **Dependency Injection Container**

**Classe:** `Plugin.php`

**Perché:** Elimina l'accoppiamento forte tra classi e facilita il testing. Ogni servizio riceve le sue dipendenze nel costruttore.

```php
// Plugin.php - Orchestrator centrale
private ImportService $importService;
private ExportService $exportService;
private TaxonomyRegistrar $taxonomyRegistrar;

public function __construct()
{
    // Istanzia tutte le dipendenze in un unico punto
    $excelReader = new ExcelReader();
    $productService = new ProductService();
    $taxonomyService = new TaxonomyService();
    
    $this->importService = new ImportService(
        $excelReader, 
        $productService, 
        $taxonomyService
    );
    // ...
}
```

**Vantaggi:**
- ✅ Singolo punto di configurazione
- ✅ Facile sostituire implementazioni (es. mock per test)
- ✅ Chiare dipendenze tra classi

#### 2. **Service Layer Pattern**

**Classi:** `ImportService`, `ExportService`, `ProductService`, `TaxonomyService`

**Perché:** Separa la business logic dal controller (AdminPage). I service sono riutilizzabili e testabili indipendentemente dall'interfaccia.

**Esempio:**
```php
// ImportService.php - Business logic pura
public function importFromFile(string $filePath): ImportReport
{
    // 1. Leggi Excel
    // 2. Valida dati
    // 3. Crea/aggiorna prodotti
    // 4. Assegna tassonomie
    // 5. Genera report
}

// AdminPage.php - Solo coordinamento
if (isset($_FILES['excel_file'])) {
    $report = $this->importService->importFromFile($tmpPath);
    $this->renderPage(['report' => $report]);
}
```

**Vantaggi:**
- ✅ AdminPage non conosce i dettagli dell'import
- ✅ ImportService riutilizzabile in altri contesti (CLI, API, cron)
- ✅ Test unitari semplici (mock delle dipendenze)

#### 3. **Template View Pattern (MVC-like)**

**File:** `views/admin-page.php`

**Perché:** Separa HTML da PHP logic. Il controller prepara i dati, la view li mostra.

**Prima (❌ tutto insieme):**
```php
public function renderPage() {
    echo '<h1>Import</h1>';
    if ($report) {
        echo '<p>Prodotti: ' . $report->created . '</p>';
    }
    echo '<form>...</form>';
}
```

**Dopo (✅ separato):**
```php
// AdminPage.php
public function renderPage(array $data = []) {
    include __DIR__ . '/../views/admin-page.php';
}

// views/admin-page.php
<h1><?php echo esc_html__('Import Products', 'woo-excel-importer'); ?></h1>
<?php if (isset($report)): ?>
    <p>Prodotti creati: <?php echo esc_html($report->created); ?></p>
<?php endif; ?>
```

**Vantaggi:**
- ✅ Designer può modificare HTML senza toccare PHP
- ✅ Più leggibile e manutenibile
- ✅ Riutilizzo template (es. per shortcode frontend)

#### 4. **Configuration Object Pattern**

**Classe:** `TaxonomyConfig.php`

**Perché:** Centralizza tutte le tassonomie in un unico punto. Prima erano sparse in più file.

```php
// TaxonomyConfig.php
final class TaxonomyConfig
{
    public static function getKnownTaxonomies(): array
    {
        return [
            'QUANTITY PER BOX' => [
                'slug' => 'quantity_per_box',
                'hierarchical' => false,
                'label' => 'Quantity Per Box',
            ],
            // ... altre 25 tassonomie
        ];
    }
}
```

**Vantaggi:**
- ✅ Aggiungere/modificare tassonomie in un solo file
- ✅ Nessuna duplicazione tra import/export/registrazione
- ✅ Facile generare documentazione automatica

#### 5. **Trait per DRY (Don't Repeat Yourself)**

**Trait:** `SecureFormHandler.php`

**Perché:** Evita duplicazione del codice di sicurezza in AdminPage e TaxonomyRegistrar.

**Prima (❌ duplicato):**
```php
// AdminPage.php
if (!current_user_can('manage_woocommerce')) {
    wp_die('Insufficient permissions');
}
if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'import_action')) {
    wp_die('Invalid nonce');
}

// TaxonomyRegistrar.php
if (!current_user_can('manage_woocommerce')) { // DUPLICATO!
    wp_die('Insufficient permissions');
}
```

**Dopo (✅ trait):**
```php
// SecureFormHandler.php
trait SecureFormHandler
{
    protected function verifySecureRequest(string $nonceAction): void
    {
        $this->checkCapabilities();
        $this->verifyNonce($nonceAction);
    }
}

// AdminPage.php e TaxonomyRegistrar.php
use SecureFormHandler;

public function handleImport() {
    $this->verifySecureRequest('import_products');
    // ... logica import
}
```

**Vantaggi:**
- ✅ Zero duplicazione (4 metodi riutilizzati in 2 classi)
- ✅ Modifiche di sicurezza in un solo punto
- ✅ Consistenza garantita

#### 6. **Chunk Reading per File Grandi**

**Classe:** `ChunkReadFilter.php`

**Perché:** Importare file con 10,000+ righe senza esaurire la memoria PHP.

```php
// ChunkReadFilter.php - Legge solo 1000 righe alla volta
class ChunkReadFilter implements IReadFilter
{
    public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
    {
        return ($row >= $this->startRow && $row < $this->endRow);
    }
}

// ExcelReader.php - Processa in chunk
for ($startRow = 2; $startRow <= $totalRows; $startRow += $chunkSize) {
    $filter = new ChunkReadFilter($startRow, $startRow + $chunkSize);
    $spreadsheet = $reader->load($filePath, $filter);
    // Processa solo questo chunk
}
```

**Vantaggi:**
- ✅ File 100MB+ processabili con 128MB memoria PHP
- ✅ Nessun timeout per file enormi
- ✅ Progress tracking possibile (row X di Y)

#### 7. **Data Transfer Object (DTO)**

**Classe:** `ImportReport.php`

**Perché:** Trasferisce dati strutturati tra ImportService e AdminPage senza accoppiamento.

```php
// ImportReport.php
final class ImportReport
{
    public int $totalRows = 0;
    public int $created = 0;
    public int $updated = 0;
    public int $skipped = 0;
    public array $errors = [];
    
    public function toArray(): array { /* ... */ }
}

// ImportService ritorna DTO
return new ImportReport([
    'created' => $created,
    'updated' => $updated,
    // ...
]);

// AdminPage usa DTO tipizzato
$report = $this->importService->importFromFile($file);
echo $report->created; // Autocomplete funziona!
```

**Vantaggi:**
- ✅ Type safety (PHP 8.1+ strict types)
- ✅ IDE autocomplete per proprietà
- ✅ Validazione dati centralizzata

### 🔒 Sicurezza

Il plugin implementa **4 livelli di sicurezza**:

1. **Capability Check:** Solo `manage_woocommerce` può importare/esportare
2. **Nonce Verification:** Protezione CSRF su ogni form submit
3. **Input Validation:** Tutti i dati Excel sono validati (tipo, lunghezza, caratteri)
4. **Output Escaping:** Tutti i dati in output usano `esc_html()`, `esc_attr()`, `esc_url()`

```php
// Esempio completo di sicurezza
$this->verifySecureRequest('import_products');           // Capability + Nonce
$sku = $this->validateSku($rawSku);                      // Validation
echo esc_html($product->get_title());                    // Escaping
```

### 📦 Gestione Dipendenze

**Composer + PSR-4 Autoloading**

```json
{
    "autoload": {
        "psr-4": {
            "WooExcelImporter\\": "src/"
        }
    },
    "require": {
        "phpoffice/phpspreadsheet": "^1.29"
    }
}
```

**Vantaggi:**
- ✅ Autoload automatico (no `require_once` manuale)
- ✅ Namespace evita conflitti con altri plugin
- ✅ Dipendenze isolate in `vendor/`

### 🧪 Testabilità

Ogni classe è progettata per essere testabile:

```php
// Test esempio (PHPUnit)
public function test_import_creates_product()
{
    $mockReader = $this->createMock(ExcelReader::class);
    $mockReader->method('read')->willReturn([
        ['SKU-001', 'Product 1', 'Description', '10.00', ...]
    ]);
    
    $service = new ImportService($mockReader, ...);
    $report = $service->importFromFile('test.xlsx');
    
    $this->assertEquals(1, $report->created);
}
```

### 🚀 Performance

**Ottimizzazioni implementate:**

1. **Chunk Reading:** Max 1000 righe in memoria per volta
2. **Lazy Loading:** Tassonomie registrate solo quando necessarie
3. **Database Queries Ottimizzate:** Batch insert/update dove possibile
4. **Cache WordPress:** Usa `wp_cache_*` per termini tassonomie frequenti

### 🔄 Estensibilità

Il design permette facilmente di:

- **Aggiungere tassonomie:** Modifica solo `TaxonomyConfig.php`
- **Aggiungere colonne Excel:** Estendi `ImportService::processRow()`
- **Cambiare formato export:** Estendi `ExcelWriter` (es. CSV, JSON)
- **Aggiungere validazioni:** Estendi `ImportService::validateRow()`

### 📚 Principi SOLID Applicati

| Principio | Come Applicato |
|-----------|----------------|
| **S**ingle Responsibility | Ogni classe ha un solo motivo per cambiare (AdminPage→UI, ImportService→business logic) |
| **O**pen/Closed | Estendibile senza modificare codice esistente (es. TaxonomyConfig) |
| **L**iskov Substitution | I service sono sostituibili con mock nei test |
| **I**nterface Segregation | Trait SecureFormHandler fornisce solo metodi necessari |
| **D**ependency Inversion | Dipendenze iniettate, non istanziate internamente |

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
