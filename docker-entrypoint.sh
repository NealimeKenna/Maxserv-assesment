#!/bin/sh
set -e

# Run composer install
composer install --no-interaction --optimize-autoloader

# Execute the original entrypoint (Apache)
exec apache2-foreground
