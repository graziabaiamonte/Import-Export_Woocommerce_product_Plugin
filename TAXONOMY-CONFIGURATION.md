# Taxonomy Configuration Guide

## File di Configurazione

Il file `src/TaxonomyConfig.php` contiene tutte le definizioni delle tassonomie conosciute dal plugin.

## Struttura di una Tassonomia

Ogni tassonomia è definita con:

```php
'NOME COLONNA EXCEL' => [
    'slug' => 'slug_wordpress',           // Identificatore WordPress (max 32 caratteri)
    'hierarchical' => false,              // true = come categorie, false = come tag
    'label' => 'Nome Visualizzato',      // Nome mostrato nell'interfaccia admin
],
```

## Come Aggiungere una Nuova Tassonomia

### 1. Apri il file di configurazione

Apri `src/TaxonomyConfig.php`

### 2. Aggiungi la definizione

Nel metodo `getKnownTaxonomies()`, aggiungi la nuova tassonomia nell'array:

```php
public static function getKnownTaxonomies(): array
{
    return [
        // ... tassonomie esistenti ...
        
        'NUOVA TASSONOMIA' => [
            'slug' => 'nuova_tassonomia',
            'hierarchical' => false,
            'label' => 'Nuova Tassonomia',
        ],
    ];
}
```

### 3. Regole per lo Slug

Lo slug deve:
- Essere **univoco**
- Contenere solo **lettere minuscole, numeri e underscore**
- Essere lungo **massimo 32 caratteri**
- Essere **descrittivo** e facile da ricordare

❌ **NON validi:**
- `Nuova-Tassonomia` (contiene maiuscole e trattini)
- `nuova tassonomia` (contiene spazi)
- `questo_slug_è_troppo_lungo_per_wordpress` (oltre 32 caratteri)

✅ **Validi:**
- `nuova_tassonomia`
- `product_color`
- `material_type`

### 4. Hierarchical: True o False?

**`hierarchical: true`** - Tassonomia gerarchica (come Categorie)
- I termini possono avere termini "figli"
- Utile per classificazioni ad albero
- Esempio: Categorie (Elettronica → Smartphone → iPhone)

**`hierarchical: false`** - Tassonomia piatta (come Tag)
- Tutti i termini sono allo stesso livello
- Più semplice e veloce
- Esempio: Colori, Taglie, Materiali

💡 **Consiglio:** Usa `false` a meno che non serva una vera gerarchia.

### 5. Rebuilda il Plugin

Dopo aver modificato `TaxonomyConfig.php`:

```bash
./build-plugin.sh
```

### 6. Reinstalla il Plugin su WordPress

1. Disattiva il plugin in WordPress
2. Cancella il vecchio plugin
3. Carica il nuovo file ZIP dalla cartella `build/`
4. Attiva il plugin

Le nuove tassonomie saranno create automaticamente all'attivazione.

## Esempio Completo

Voglio aggiungere una tassonomia per il "Colore del Prodotto":

```php
'PRODUCT COLOR' => [
    'slug' => 'product_color',
    'hierarchical' => false,
    'label' => 'Product Color',
],
```

Nel file Excel, la colonna sarà: `PRODUCT COLOR;`

I valori potrebbero essere: `Red`, `Blue`, `Green`, ecc.

## Modificare una Tassonomia Esistente

⚠️ **ATTENZIONE:** Modificare lo slug di una tassonomia esistente cancellerà tutti i termini associati!

Se devi modificare:
- **Label:** Puoi modificare liberamente, è solo il nome visualizzato
- **Hierarchical:** Puoi modificare, ma potrebbe causare problemi se hai già termini gerarchici
- **Slug:** ⚠️ NON modificare se hai già dati in produzione!

## Rimuovere una Tassonomia

Per rimuovere una tassonomia:

1. Rimuovi la sua definizione da `TaxonomyConfig.php`
2. Rebuilda il plugin
3. Reinstalla

⚠️ **NOTA:** I dati (termini) rimarranno nel database a meno che non attivi l'opzione "Delete all data on uninstall" nelle impostazioni del plugin.

## Metodi Utili della Classe

```php
// Ottieni tutte le tassonomie
TaxonomyConfig::getKnownTaxonomies();

// Ottieni slug di una colonna
TaxonomyConfig::getSlugByColumnName('PRODUCT COLOR'); // 'product_color'

// Ottieni nome colonna da slug
TaxonomyConfig::getColumnNameBySlug('product_color'); // 'PRODUCT COLOR'

// Verifica se una colonna è una tassonomia nota
TaxonomyConfig::isKnownTaxonomy('PRODUCT COLOR'); // true

// Ottieni tutti gli slug
TaxonomyConfig::getAllSlugs();

// Ottieni tutti i nomi colonna
TaxonomyConfig::getAllColumnNames();
```

## FAQ

**Q: Posso avere spazi nel nome della colonna Excel?**
A: Sì, il nome della colonna può contenere spazi e caratteri speciali.

**Q: Cosa succede se uso lo stesso slug per due tassonomie diverse?**
A: WordPress userà solo la prima, la seconda sarà ignorata. Gli slug devono essere univoci.

**Q: Posso cambiare il nome della colonna Excel senza cambiare lo slug?**
A: Sì, ma dovrai aggiornare anche i file Excel che usi per l'import.

**Q: Le tassonomie custom (create dinamicamente) possono essere aggiunte qui?**
A: No, questo file è solo per tassonomie "conosciute". Le tassonomie custom vengono gestite dinamicamente dal plugin.
