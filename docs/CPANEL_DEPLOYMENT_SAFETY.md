# cPanel Deployment Safety

Use this when redeploying so production uploads, `.env`, and server-only Apache rules are not replaced by Git.

## What Must Stay Outside Git

- `.env`
- root `.htaccess` created by cPanel or hosting support
- uploaded school logos, student photos, staff photos, receipts, PDFs, and Livewire temporary uploads
- `storage/` runtime files, logs, cache, sessions, and compiled views
- `public/storage` symlink

The repository should contain code and committed assets only. Production data should live in persistent server folders.

## Recommended cPanel Folder Layout

Example:

```text
/home/USERNAME/
  school-erp-filament/          # Git-deployed Laravel code
  school-erp-storage/           # Persistent uploads/runtime storage
  school-erp-env/.env           # Backed-up production env file
  public_html/                  # Web root
```

Recommended persistent folders:

```text
/home/USERNAME/school-erp-storage/app/public
/home/USERNAME/school-erp-storage/app/private
/home/USERNAME/school-erp-storage/framework/cache
/home/USERNAME/school-erp-storage/framework/sessions
/home/USERNAME/school-erp-storage/framework/views
/home/USERNAME/school-erp-storage/logs
```

## First-Time Setup

From the app folder on the server:

```bash
cp .env.example .env
php artisan key:generate
```

Then edit `.env` with production DB, mail, app URL, Paystack keys, and storage settings.

Move/keep the real `.env` outside the Git deploy folder or back it up:

```bash
mkdir -p ~/school-erp-env
cp .env ~/school-erp-env/.env
```

Keep uploads persistent:

```bash
mkdir -p ~/school-erp-storage
rsync -a storage/ ~/school-erp-storage/
```

After deploy, restore or symlink:

```bash
rm -rf storage
ln -s ~/school-erp-storage storage
cp ~/school-erp-env/.env .env
php artisan storage:link
```

If cPanel does not allow symlinking the whole `storage` directory, keep the normal `storage` folder but never delete it during deployment. At minimum, protect:

```text
storage/app/public
storage/app/private
public/storage
```

## Redeploy Checklist

Before pulling or uploading new code:

```bash
cp .env ~/school-erp-env/.env
cp .htaccess ~/school-erp-env/.htaccess 2>/dev/null || true
cp public/.htaccess ~/school-erp-env/public.htaccess 2>/dev/null || true
```

Deploy code from Git.

After deploy:

```bash
cp ~/school-erp-env/.env .env
cp ~/school-erp-env/.htaccess .htaccess 2>/dev/null || true
cp ~/school-erp-env/public.htaccess public/.htaccess 2>/dev/null || true
php artisan migrate --force
php artisan storage:link
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Make sure `public/storage` points to persistent uploads:

```bash
ls -la public/storage
```

## Git Rules

Do not commit:

- `.env`
- `.htaccess` at the project root
- uploaded images/files
- `storage` runtime files
- server backups

It is okay to commit:

- `public/.htaccess` if it is the app’s default Laravel public rewrite file
- static app assets such as `public/images/branding/*`
- `.env.example` with placeholder values only

## Safer Upload Strategy

When uploading manually through cPanel File Manager, do not upload over these paths:

```text
.env
.htaccess
storage/
public/storage
```

Upload code folders/files only:

```text
app/
bootstrap/
config/
database/
public/          # except public/storage and custom public/.htaccess
resources/
routes/
composer.json
composer.lock
artisan
```

Then run the post-deploy commands above.
