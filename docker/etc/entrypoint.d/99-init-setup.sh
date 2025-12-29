#!/bin/sh

if [ -f "$APP_BASE_DIR/artisan" ]; then
  php "$APP_BASE_DIR/artisan" passport:keys -n || true
  php "$APP_BASE_DIR/artisan" app:install --name="Test User" --email="test@test.com" --password="password" -n
else
  echo "❌ Artisan file not found in $APP_BASE_DIR"
  exit 1
fi
