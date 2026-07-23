# Production Deployment Guide

Since we have merged everything into a single, unified application, deploying to a fresh production instance (like a VPS or Laravel Forge) is now incredibly simple. 

You no longer need to worry about custom packages, symlinks, or private repositories. Your entire app lives in one place on GitHub: `https://github.com/Af1ah/unified-attedence`.

## Prerequisites

On your fresh production server (e.g. Ubuntu 22.04/24.04), ensure you have installed:
- PHP 8.2+
- Composer
- MySQL 8+ or MariaDB
- Nginx or Apache

## Step-by-Step Deployment

### 1. Clone the Repository
SSH into your production server and navigate to your web directory (e.g. `/var/www/html`), then clone your repository:
```bash
git clone https://github.com/Af1ah/unified-attedence.git .
```

### 2. Install Dependencies
Install all required PHP packages optimized for production:
```bash
composer install --optimize-autoloader --no-dev
```

### 3. Environment Configuration
Copy the example environment file and generate your application key:
```bash
cp .env.example .env
php artisan key:generate
```

Now, open the `.env` file using a text editor like `nano`:
```bash
nano .env
```
Update your database credentials to match your production MySQL database:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_production_db_name
DB_USERNAME=your_production_db_user
DB_PASSWORD=your_production_db_password
```

> [!IMPORTANT]
> Make sure you change `APP_ENV=local` to `APP_ENV=production` and `APP_DEBUG=true` to `APP_DEBUG=false` in your `.env` file!

### 4. Run Migrations & Setup Database
Run the database migrations to create all your tables (Users, Devices, Attendance Logs, etc.):
```bash
php artisan migrate --force
```

Create your initial Admin user so you can log into the Filament dashboard:
```bash
php artisan make:filament-user
```

### 5. Optimize Caches
To ensure your production application runs as fast as possible, cache your configurations, routes, and views:
```bash
php artisan optimize
php artisan filament:optimize
```

### 6. Storage Link & Permissions
Ensure Nginx/Apache has permission to read and write to the storage folders, and link the public storage directory:
```bash
php artisan storage:link
sudo chown -R www-data:www-data storage bootstrap/cache
```
php artisan queue:work
php artisan serve --host=0.0.0.0 --port=8000

## Configuring the Attendance Devices

Once your application is live on your domain (e.g. `https://zkteco.ariise.cloud`), you need to configure your physical ZKTeco attendance devices.

On the device menu, navigate to **Cloud Server Settings** or **ADMS Settings** and enter:
- **Server Address:** `zkteco.ariise.cloud`
- **Server Port:** `443` (if using HTTPS) or `80`
- **Server URL:** `http://zkteco.ariise.cloud/api`

> [!WARNING]
> Do **not** forget the `/api` at the end of the Server URL! All of the ADMS endpoints (like `/iclock/cdata`) are correctly registered under Laravel's native `/api` prefix.
