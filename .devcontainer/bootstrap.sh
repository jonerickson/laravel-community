#!/bin/bash
set -e

cd /var/www/html

export DEVCONTAINER_SETUP=1

echo "Running project setup via Composer script..."
composer run setup
