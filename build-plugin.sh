#!/bin/bash
#
# Script di packaging automatico per WooCommerce Excel Import/Export Plugin
# Crea un file ZIP pronto per l'installazione su WordPress
#

set -e

# Colori per output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Directory del plugin
PLUGIN_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PLUGIN_NAME="woocommerce-excel-importer"
BUILD_DIR="${PLUGIN_DIR}/build"
TEMP_DIR="${BUILD_DIR}/${PLUGIN_NAME}"
OUTPUT_FILE="${BUILD_DIR}/${PLUGIN_NAME}.zip"

echo -e "${GREEN}╔══════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║  WooCommerce Excel Import/Export - Build Script         ║${NC}"
echo -e "${GREEN}╚══════════════════════════════════════════════════════════╝${NC}"
echo ""

# Controllo Composer
if ! command -v composer &> /dev/null; then
    echo -e "${RED}✗ Errore: Composer non trovato${NC}"
    echo "  Installa Composer: brew install composer"
    exit 1
fi
echo -e "${GREEN}✓${NC} Composer trovato"

# Controllo PHP
if ! command -v php &> /dev/null; then
    echo -e "${RED}✗ Errore: PHP non trovato${NC}"
    exit 1
fi
PHP_VERSION=$(php -r 'echo PHP_VERSION;')
echo -e "${GREEN}✓${NC} PHP ${PHP_VERSION} trovato"

# Pulizia build precedente
if [ -d "${BUILD_DIR}" ]; then
    echo -e "${YELLOW}→${NC} Pulizia build precedente..."
    rm -rf "${BUILD_DIR}"
fi

# Creazione directory di build
echo -e "${YELLOW}→${NC} Creazione directory di build..."
mkdir -p "${TEMP_DIR}"

# Copia file del plugin
echo -e "${YELLOW}→${NC} Copia file del plugin..."
cp -r "${PLUGIN_DIR}/src" "${TEMP_DIR}/"
cp -r "${PLUGIN_DIR}/assets" "${TEMP_DIR}/"
cp "${PLUGIN_DIR}/plugin.php" "${TEMP_DIR}/"
cp "${PLUGIN_DIR}/composer.json" "${TEMP_DIR}/"
cp "${PLUGIN_DIR}/README.md" "${TEMP_DIR}/"
echo -e "${GREEN}✓${NC} File copiati"

# Installazione dipendenze Composer
echo -e "${YELLOW}→${NC} Installazione dipendenze Composer..."
cd "${TEMP_DIR}"
composer install --no-dev --optimize-autoloader --no-interaction --quiet
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓${NC} Dipendenze installate"
else
    echo -e "${RED}✗ Errore nell'installazione delle dipendenze${NC}"
    exit 1
fi

# Rimozione file non necessari
echo -e "${YELLOW}→${NC} Rimozione file non necessari..."
rm -f "${TEMP_DIR}/composer.json"
rm -f "${TEMP_DIR}/composer.lock"
find "${TEMP_DIR}" -name ".DS_Store" -delete
find "${TEMP_DIR}" -name ".git*" -delete
echo -e "${GREEN}✓${NC} Pulizia completata"

# Creazione ZIP
echo -e "${YELLOW}→${NC} Creazione file ZIP..."
cd "${BUILD_DIR}"
zip -r -q "${PLUGIN_NAME}.zip" "${PLUGIN_NAME}"
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓${NC} ZIP creato con successo"
else
    echo -e "${RED}✗ Errore nella creazione dello ZIP${NC}"
    exit 1
fi

# Pulizia directory temporanea
rm -rf "${TEMP_DIR}"

# Informazioni finali
FILE_SIZE=$(du -h "${OUTPUT_FILE}" | cut -f1)
echo ""
echo -e "${GREEN}╔══════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║  Build completata con successo!                          ║${NC}"
echo -e "${GREEN}╚══════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "  ${GREEN}File ZIP:${NC} ${OUTPUT_FILE}"
echo -e "  ${GREEN}Dimensione:${NC} ${FILE_SIZE}"
echo ""
echo -e "${YELLOW}Come installare:${NC}"
echo -e "  1. Vai su WordPress Admin → Plugin → Aggiungi nuovo"
echo -e "  2. Clicca 'Carica plugin'"
echo -e "  3. Seleziona il file: ${PLUGIN_NAME}.zip"
echo -e "  4. Clicca 'Installa ora'"
echo -e "  5. Clicca 'Attiva plugin'"
echo ""
echo -e "${GREEN}Il plugin sarà immediatamente funzionante!${NC}"
echo -e "Vai su: ${YELLOW}WooCommerce → Excel Import/Export${NC}"
echo ""
