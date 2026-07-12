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

## Cron and Queue (currently not required, but read this before adding one)

As of this writing, nothing in the app implements `ShouldQueue` — mail (e.g. the school-admin
welcome email) sends synchronously on the request, and `routes/console.php` has no scheduled
tasks beyond the default Artisan `inspire` command. **A cron job is not required for the app to
work today.**

If a future feature queues a job or notification (`->onQueue()`, `implements ShouldQueue`, a
scheduled command in `routes/console.php`), two things become mandatory or that work will queue
up and silently never run:

1. A queue worker process. On shared cPanel hosting without shell/supervisor access, the
   practical option is a cron entry every minute running
   `php artisan queue:work --stop-when-empty --max-time=55`, which processes whatever is
   queued and exits before the next minute's cron fires it again.
2. If `routes/console.php` gains scheduled commands, a single cron entry is also needed for
   Laravel's scheduler:
   `* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1`

Set `QUEUE_CONNECTION=database` (already the default in `.env`) so queued jobs persist in the
`jobs` table between cron runs rather than requiring a long-running process.

## Moving session/cache/queue to Redis (do this once volume justifies it)

The `database` driver for sessions, cache, and queue (current default) is fine for a handful of
schools. Once dozens-to-hundreds of schools are active concurrently, every page load hitting the
`sessions`/`cache`/`jobs` tables on the same MySQL server as everything else becomes real
contention. Redis removes that load from MySQL entirely and is a pure config change — no code
changes needed, Laravel's `config/session.php`, `config/cache.php`, and `config/queue.php`
already support the `redis` driver out of the box, and `config/database.php` already has a
`redis` connection block reading `REDIS_HOST`/`REDIS_PORT`/`REDIS_PASSWORD` from `.env`.

**Before flipping this in production**, confirm the host actually provides Redis — this matters
because shared cPanel hosting frequently does *not* include it unless the plan is a VPS or the
reseller enabled a CloudLinux "Redis Selector" / WHM Redis add-on. Check with the host, or run
`redis-cli ping` on the server — if that returns `PONG`, it's available.

**The switch itself**, once a Redis instance is reachable from the app server:

1. Set in `.env`: `SESSION_DRIVER=redis`, `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`.
2. Fill in `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD` for that instance (`.env` already has
   placeholders for these).
3. Run `php artisan config:clear` (or `config:cache` in production) so the new driver takes
   effect immediately.
4. If a `QUEUE_CONNECTION=database` queue worker cron was already running, switch it to a
   long-running `php artisan queue:work` process (Redis queues are meant to be consumed by a
   persistent worker, not a `--stop-when-empty` cron poll) — this needs Supervisor or an
   equivalent process manager, which usually means a VPS rather than shared cPanel hosting.
5. Sanity-check: log in (confirms session), reload a page twice (confirms cache), and trigger
   any queued action if one exists (confirms queue) before considering the switch done.

Local development note: this was intentionally **not** flipped in this repo's own `.env` — doing
so requires a running Redis server, and there is no Redis server installed on this machine as of
this writing (no Homebrew/Docker install was performed here at the developer's request). The
config wiring above has been verified by reading Laravel's config files, not by running a live
Redis instance, so treat step 5 above as required before trusting this in production.
