# Adding a School's Subdomain in DirectAdmin

DirectAdmin support has confirmed wildcard subdomains (`*.yourdomain.com`) are not
allowed on this account as an account-level feature. In practice this doesn't matter
much: DNS for `*.ndsl.com.ng` was already wildcarded before this was investigated, and
the app already resolves tenants by hostname (Filament's `tenantDomain()` — see
`app/Models/School.php` and `config/app.php`'s `central_domain`), so any subdomain
already serves the right school's content over plain HTTP. The only thing actually
missing per school is a valid HTTPS certificate — nginx doesn't know to present the
existing wildcard cert (`CN=*.ndsl.com.ng`, already installed on the main domain) for a
hostname it has no vhost/SNI entry for, so it falls back to the shared server's default
cert and browsers throw a "connection not private" warning. That's what looked like "no
subdomain" to the client.

## Steps, per school

1. **Create the school in the app first** so you know its slug (e.g. `greenfield`).
   The subdomain must exactly match the slug: `greenfield.ndsl.com.ng`.

2. **Register the subdomain in DirectAdmin** — Domain Setup → Subdomain Management
   (or Domains → ndsl.com.ng → Subdomain Management) → add `greenfield`.

   On this host, creating a subdomain this way does **not** make a subfolder under
   `ndsl.com.ng`'s own `public_html` — it creates an entirely separate domain
   directory at `/home/ndslcomn/domains/greenfield.ndsl.com.ng/public_html/`,
   pre-populated with DA's placeholder `index.html` + `cgi-bin`. Dynamic PHP
   requests happen to still resolve to the real app (some catch-all fastcgi
   routing keyed off the `Host` header), which is why the login page itself loads
   — but nginx serves *static* files straight from that empty per-subdomain
   docroot, so every CSS/JS/image 404s and the page renders completely unstyled.

3. **Fix the docroot — required, not optional:**
   ```bash
   rm -rf /home/ndslcomn/domains/greenfield.ndsl.com.ng/public_html
   ln -s /home/ndslcomn/domains/ndsl.com.ng/school-erp/public \
         /home/ndslcomn/domains/greenfield.ndsl.com.ng/public_html
   ```
   (Adjust the source path if the app ever moves — it must point at the same
   real Laravel `public/` the main domain's own `public_html` symlinks to;
   check with `readlink /home/ndslcomn/domains/ndsl.com.ng/public_html` if unsure.)

4. **Wait ~2 minutes** after creating the subdomain (before or after step 3, doesn't
   matter) for DirectAdmin's background config-sync task to wire the existing
   wildcard cert to the new hostname's SNI entry — no manual SSL reissue needed.

5. **Verify — check both SSL *and* assets, not just the page loading:**
   ```bash
   echo | openssl s_client -connect greenfield.ndsl.com.ng:443 -servername greenfield.ndsl.com.ng 2>/dev/null | openssl x509 -noout -subject
   # should print: subject=CN=*.ndsl.com.ng
   curl -sI https://greenfield.ndsl.com.ng/portal/login          # 200, no -k needed
   curl -sI https://greenfield.ndsl.com.ng/build/manifest.json   # 200 — NOT enough on its own, see below
   curl -sI https://greenfield.ndsl.com.ng/js/filament/filament/app.js  # 200 — this is the one that actually catches the bug
   ```
   `build/manifest.json` alone returning 200 is misleading — it succeeds even
   when the docroot is still broken. Check a real nested static asset like the
   Filament JS/CSS above, or just load the login page in a browser and confirm
   it's actually styled.

## Doing this via the DirectAdmin API instead of the GUI

If SSH access to the server is available (see `~/.ssh/config` entry `whogohost`),
subdomains can be created via DA's HTTP API without logging into the GUI:

1. In DA's web GUI: Account Manager → Login Keys → Create Login Key. Generate a
   random Key Value (don't type your own), give it a short expiry, leave "Allow HTM"
   unchecked (API-only), restrict Commands to `CMD_API_SUBDOMAINS` (search for it —
   don't grant `ALL_USER`), and restrict Allowed IPs to `127.0.0.1` so the key is only
   usable from a call made from inside the server itself, not the open internet.
2. From the SSH session:
   ```bash
   curl -s -k -u "USERNAME:KEYVALUE" -X POST "https://127.0.0.1:2222/CMD_API_SUBDOMAINS" \
     --data-urlencode "domain=ndsl.com.ng" \
     --data-urlencode "action=create" \
     --data-urlencode "subdomain=greenfield"
   ```
   Response `error=0&text=Subdomain%20created` means success.
3. Delete/let the Login Key expire once done — it's single-purpose.

Note: batching multiple `curl` calls to `127.0.0.1:2222` in one SSH command sometimes
drops the SSH connection partway through (observed on 2026-09-01, cause unidentified —
possibly the DA daemon or connection tracking under load). Run one subdomain creation
per SSH invocation rather than a loop, and re-check `CMD_API_SUBDOMAINS?domain=ndsl.com.ng`
(GET) afterward to confirm what actually got created before retrying.

## If this gets tedious at scale

Once there are enough schools that doing this by hand (or via one API call each) every
time is a problem, the fix isn't more manual subdomains — it's revisiting wildcard
subdomain support with the host on a different plan, or moving DNS to Cloudflare
(proxy-level wildcard SSL, independent of what DA's own panel allows) while keeping
hosting on the current DA server.
