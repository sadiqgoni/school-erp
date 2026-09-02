<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

class DirectAdminSubdomainProvisioner
{
    /**
     * Queue a DirectAdmin subdomain to be created for the given slug, with
     * its document root pointed at this app's public/ folder.
     *
     * PHP's own network calls to the DirectAdmin API time out on this host,
     * even a `curl` subprocess spawned by PHP — CloudLinux's CageFS appears
     * to sandbox network access for PHP's whole process tree, while the
     * exact same command run outside PHP (SSH, cron) reaches it instantly.
     * So this just appends to a plain-text queue file; a cron-driven shell
     * script (deploy/provision-subdomains.sh) does the actual API call
     * outside PHP's process tree.
     *
     * Silently disabled (returns false) when DirectAdmin credentials aren't
     * configured, e.g. in local development.
     */
    public static function ensure(string $slug): bool
    {
        $username = config('services.directadmin.username');
        $loginKey = config('services.directadmin.login_key');
        $domain = config('services.directadmin.domain');

        if (blank($username) || blank($loginKey) || blank($domain)) {
            return false;
        }

        File::append(storage_path('app/directadmin-pending-subdomains.txt'), $slug.PHP_EOL);

        return true;
    }
}
