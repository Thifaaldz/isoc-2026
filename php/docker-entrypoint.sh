#!/bin/bash
set -e

echo "🚀 Starting Laravel container setup..."

# Step 1: Create Laravel project if not already present
if [ -z "$(find /var/www/html -mindepth 1 -not -path '/var/www/html/.gitkeep' -print -quit)" ]; then
  echo "📦 Creating Laravel project (fila-starter)..."
  composer create-project --prefer-dist raugadh/fila-starter:2.0 . --no-interaction
else
  echo "✅ Laravel project already exists. Skipping create-project."
fi

# Step 2: Buat .env dari .env.example proyek (sekali saja), lalu sesuaikan untuk Docker.
ENV_FILE=/var/www/html/.env
set_env() {
  # set_env KEY VALUE: ganti baris KEY=... atau tambahkan bila belum ada.
  if grep -q "^$1=" "$ENV_FILE"; then
    sed -i "s|^$1=.*|$1=$2|" "$ENV_FILE"
  else
    echo "$1=$2" >> "$ENV_FILE"
  fi
}

if [ ! -f "$ENV_FILE" ]; then
  echo "📄 Creating .env from .env.example..."
  cp /var/www/html/.env.example "$ENV_FILE"
  set_env APP_NAME "\"${PROJECT_NAME}\""
  set_env APP_URL "https://${PROJECT_NAME}.test"
  set_env DB_HOST db
  set_env DB_DATABASE "${PROJECT_NAME}"
  set_env REDIS_HOST redis
  set_env CACHE_STORE redis
  set_env SESSION_DRIVER redis
else
  # .env yang sudah ada (mis. pengaturan produksi) tidak ditimpa.
  echo "📄 .env file already exists, keeping it."
fi

# Step 3: Wait for DB connection (host should match DB_HOST in .env)
DB_HOST=$(grep DB_HOST /var/www/html/.env | cut -d '=' -f2)
DB_PORT=$(grep DB_PORT /var/www/html/.env | cut -d '=' -f2)

DB_HOST=${DB_HOST:-db}
DB_PORT=${DB_PORT:-3306}

echo "⏳ Waiting for database at $DB_HOST:$DB_PORT..."

# Timeout after 30 attempts (1 minute)
RETRIES=30
until nc -z "$DB_HOST" "$DB_PORT"; do
  if [ "$RETRIES" -le 0 ]; then
    echo "❌ Timeout waiting for database. Exiting."
    exit 1
  fi
  echo "Waiting for DB..."
  sleep 2
  RETRIES=$((RETRIES - 1))
done

echo "✅ Database is ready!"

# Step 4: composer install bila vendor belum ada atau composer.lock berubah sejak instalasi terakhir.
LOCK_HASH_FILE=/var/www/html/vendor/.composer-lock.md5
LOCK_HASH=$(md5sum /var/www/html/composer.lock 2>/dev/null | cut -d ' ' -f1)
if [ ! -f /var/www/html/vendor/autoload.php ] || [ "$LOCK_HASH" != "$(cat "$LOCK_HASH_FILE" 2>/dev/null)" ]; then
  echo "📦 Installing composer dependencies..."
  composer install --no-interaction --prefer-dist --optimize-autoloader
  echo "$LOCK_HASH" > "$LOCK_HASH_FILE"
else
  echo "✅ Composer dependencies up to date."
fi

# Step 5: Generate APP_KEY hanya bila masih kosong (key yang berganti membuat sesi & data terenkripsi tidak terbaca).
if ! grep -q "^APP_KEY=base64:" "$ENV_FILE"; then
  echo "🔐 Generating Laravel app key..."
  php artisan key:generate --force
fi

# Step 6: Create necessary folders and set permissions
echo "🔧 Fixing permissions..."
mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache \
  /var/www/html/storage/app/public/evidences /var/www/html/storage/app/public/learning-materials
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Step 7 & 8: migrasi lalu seeder awal.
#  SEED_ON_START=auto   (default) seeder hanya dijalankan saat database masih kosong (belum ada user).
#  SEED_ON_START=always seeder dijalankan setiap container start (semua seeder aman diulang).
#  SEED_ON_START=never  seeder tidak dijalankan otomatis.
SEED_ON_START=${SEED_ON_START:-auto}
echo "🗃️ Running migrations..."
php artisan migrate --force

if [ "$SEED_ON_START" = "always" ]; then
  echo "🌱 Running database seeders (SEED_ON_START=always)..."
  php artisan db:seed --force || echo "❌ Seeder gagal, cek log di atas."
elif [ "$SEED_ON_START" != "never" ]; then
  echo "🌱 Seeding database bila masih kosong (project:init)..."
  php artisan project:init || echo "❌ project:init gagal, cek log di atas. Jalankan ulang: docker compose exec php php artisan project:init"
fi

# Step 8b: Produksi: cache config, route, view, event, dan komponen Filament untuk respons lebih cepat.
if grep -q "^APP_ENV=production" /var/www/html/.env; then
  echo "⚡ Caching config/routes/views for production..."
  php artisan optimize
  php artisan filament:optimize
fi

# Step 9: Create storage symbolic link
echo "🔗 Creating storage link..."
[ -L /var/www/html/public/storage ] || php artisan storage:link || true

# Step 10: Start cron
echo "🕒 Starting cron service..."
service cron start

# Step 11: Export development variables from .env to shell
ENV_FILE="/var/www/html/.env"
for VAR in XDEBUG PHP_IDE_CONFIG REMOTE_HOST; do
  if [ -z "${!VAR}" ] && [ -f "$ENV_FILE" ]; then
    VALUE=$(grep ^$VAR= "$ENV_FILE" | cut -d '=' -f 2-)
    if [ -n "$VALUE" ]; then
      sed -i "/$VAR/d" ~/.bashrc
      echo "export $VAR=$VALUE" >> ~/.bashrc
    fi
  fi
done
. ~/.bashrc

# Step 12: Set REMOTE_HOST default if still not defined
if [ -z "$REMOTE_HOST" ]; then
  REMOTE_HOST="host.docker.internal"
  sed -i "/REMOTE_HOST/d" ~/.bashrc
  echo "export REMOTE_HOST=\"$REMOTE_HOST\"" >> ~/.bashrc
  . ~/.bashrc
fi

# Step 13: Toggle Xdebug support
XDEBUG_CONFIG="/usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini"

if [ "$XDEBUG" == "true" ] && [ ! -f "$XDEBUG_CONFIG" ]; then
  echo "🐞 Enabling Xdebug..."
  sed -i '/PHP_IDE_CONFIG/d' /etc/cron.d/laravel-scheduler
  if [ -n "$PHP_IDE_CONFIG" ]; then
    echo -e "PHP_IDE_CONFIG=\"$PHP_IDE_CONFIG\"\n$(cat /etc/cron.d/laravel-scheduler)" > /etc/cron.d/laravel-scheduler
  fi
  docker-php-ext-enable xdebug
  {
    echo "xdebug.remote_enable=1"
    echo "xdebug.remote_autostart=1"
    echo "xdebug.remote_connect_back=0"
    echo "xdebug.remote_host=$REMOTE_HOST"
  } >> "$XDEBUG_CONFIG"
elif [ -f "$XDEBUG_CONFIG" ]; then
  echo "🔧 Disabling Xdebug..."
  sed -i '/PHP_IDE_CONFIG/d' /etc/cron.d/laravel-scheduler
  rm -f "$XDEBUG_CONFIG"
fi

echo "✅ Laravel container setup complete. Ready to serve!"

exec "$@"
