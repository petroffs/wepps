#!/bin/bash
# Generate M2M API templates for goods
# Usage: ./generate-m2m-templates.sh [--limit 3]

set -e

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PLATFORM_ROOT="$(dirname "$SCRIPT_DIR")"

echo "🔄 M2M Templates Generator"
echo "   Platform: $PLATFORM_ROOT"

# Проверяем что config.php существует
if [ ! -f "$PLATFORM_ROOT/config.php" ]; then
    echo "❌ config.php not found at $PLATFORM_ROOT/config.php"
    exit 1
fi

# Запускаем PHP скрипт
php "$SCRIPT_DIR/generate-m2m-templates.php" "$@"

echo ""
echo "📁 Templates location:"
echo "   $PLATFORM_ROOT/.tools/bruno/WeppsPlatformV1/clientM2M/tests/goods"
echo ""
echo "📖 For usage instructions, see README.md in that directory"
