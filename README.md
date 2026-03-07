# WooCommerce Excel Import/Export Plugin

Plugin WordPress per importare ed esportare prodotti WooCommerce tramite file Excel.



## 📋 Cosa Fa Il Plugin

Questo plugin ti permette di:

✅ **Importare prodotti** da file Excel (.xlsx, .xls, .csv) nel tuo negozio WooCommerce  
✅ **Esportare prodotti** esistenti verso Excel per modifiche offline  
✅ **Aggiornare prodotti** esistenti (il plugin riconosce prodotti già presenti tramite SKU)  
✅ **Gestire 26 tassonomie personalizzate** per catalogare prodotti medicali/chirurgici  
✅ **Gestire termini e tassonomie** direttamente dall'interfaccia nativa di WordPress (menu **Prodotti**)  
✅ **Assegnare tassonomie ai prodotti** tramite i metabox nativi di WordPress nella pagina di modifica prodotto  
✅ **Filtrare prodotti** nell'area admin per tassonomie  
✅ **Ricevere report dettagliati** dopo ogni import con successi ed errori

---

## Chi Può Usarlo

- **Shop Manager**: Gestione catalogo senza competenze tecniche
- **Amministratori WordPress**: Importazioni massive da file fornitori

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


#### Step 2: Importa il File

1. Vai in **WooCommerce** → **Excel Import/Export**
2. Nella sezione **"Import Products from Excel"**:
   - Clicca **Scegli file**
   - Seleziona il tuo file Excel
   - Clicca **Import Products**
3. Attendi qualche secondo 

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

## Gestione Tassonomie dall'Interfaccia WordPress

### Termini delle tassonomie (menu Prodotti)

Le tassonomie registrate dal plugin sono integrate nativamente in WordPress. Ogni tassonomia compare come voce di menu sotto **Prodotti**, esattamente come le categorie e i tag standard. Da lì puoi:

- **Aggiungere nuovi termini** (es. aggiungere un nuovo valore a "Gauge")
- **Modificare il nome** di un termine esistente
- **Eliminare termini** non più utilizzati
- **Visualizzare quanti prodotti** sono associati a ciascun termine

**Come accedere:**
1. Vai in **Prodotti** nel menu laterale di WordPress
2. Troverai una voce per ogni tassonomia registrata (es. **Gauge**, **Tip Type**, **Packaging**, ecc.)
3. Clicca sulla tassonomia per gestire i suoi termini

### Assegnazione tassonomie nella pagina prodotto

Nella pagina di modifica di un prodotto, le tassonomie del plugin sono assegnate tramite i **metabox nativi di WordPress** — gli stessi che WordPress genera automaticamente quando si associa una tassonomia gerarchica o non gerarchica a un post type.

Non vengono utilizzati metabox personalizzati: l'interfaccia è quella standard di WordPress, garantendo compatibilità e familiarità per l'utente.

**Comportamento:**
- Ogni metabox mostra i termini disponibili per quella tassonomia
- È possibile selezionare **un solo termine per tassonomia** (singola selezione)
- I termini vengono salvati esattamente come avviene per le categorie WooCommerce native

---

## Filtrare Prodotti per Tassonomie

Oltre a importare/esportare, il plugin aggiunge **filtri** nella lista prodotti admin.

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
- ✅ I nomi colonne siano **esattamente** quelli elencati sopra (es. `QUANTITY PER BOX`)
- ✅ I valori non contengano caratteri speciali (`<`, `>`, `"`, `'`)



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

## Architettura e Scelte di Design

###  Struttura del Codice

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


**Vantaggi:**
- ✅ Singolo punto di configurazione
- ✅ Facile sostituire implementazioni (es. mock per test)
- ✅ Chiare dipendenze tra classi

#### 2. **Service Layer Pattern**

**Classi:** `ImportService`, `ExportService`, `ProductService`, `TaxonomyService`

**Perché:** Separa la business logic dal controller (AdminPage). I service sono riutilizzabili e testabili indipendentemente dall'interfaccia.


**Vantaggi:**
- ✅ AdminPage non conosce i dettagli dell'import
- ✅ ImportService riutilizzabile in altri contesti (CLI, API, cron)
- ✅ Test unitari semplici (mock delle dipendenze)

#### 3. **Template View Pattern (MVC-like)**

**File:** `views/admin-page.php`

**Perché:** Separa HTML da PHP logic. Il controller prepara i dati, la view li mostra.


**Vantaggi:**
- ✅ Designer può modificare HTML senza toccare PHP
- ✅ Più leggibile e manutenibile
- ✅ Riutilizzo template 

#### 4. **Configuration Object Pattern**

**Classe:** `TaxonomyConfig.php`

**Perché:** Centralizza tutte le tassonomie in un unico punto. Prima erano sparse in più file.


**Vantaggi:**
- ✅ Aggiungere/modificare tassonomie in un solo file
- ✅ Nessuna duplicazione tra import/export/registrazione
- ✅ Facile generare documentazione automatica



#### 5. **Chunk Reading per File Grandi**

**Classe:** `ChunkReadFilter.php`

**Perché:** Importare file con 10,000+ righe senza esaurire la memoria PHP.

**Vantaggi:**
- ✅ File 100MB+ processabili con 128MB memoria PHP
- ✅ Nessun timeout per file enormi
- ✅ Progress tracking possibile (row X di Y)

#### 6. **Data Transfer Object (DTO)**

**Classe:** `ImportReport.php`

**Perché:** Trasferisce dati strutturati tra ImportService e AdminPage senza accoppiamento.

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


### 📦 Gestione Dipendenze

**Composer + PSR-4 Autoloading**

**Vantaggi:**
- ✅ Autoload automatico (no `require_once` manuale)
- ✅ Namespace evita conflitti con altri plugin
- ✅ Dipendenze isolate in `vendor/`


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


