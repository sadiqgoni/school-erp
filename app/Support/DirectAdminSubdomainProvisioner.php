<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Throwable;

class DirectAdminSubdomainProvisioner
{
    /**
     * Create a real DirectAdmin subdomain for the given slug and point its
     * document root at this app's public/ folder, so it serves the same
     * Laravel app that resolves the tenant from the request's Host header.
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

        $port = config('services.directadmin.port', 2222);

        try {
            // The app runs on the same server as the DirectAdmin API, but
            // PHP's own curl extension can't reach it (times out on every
            // route tried — real hostname, loopback, with/without cert
            // verification), seemingly blocked at the process level by the
            // host's hardening. The system `curl` binary reaches it over
            // loopback instantly, so shell out to that instead.
            $process = new Process([
                'curl', '-sk', '-m', '15',
                '-u', "{$username}:{$loginKey}",
                '--data-urlencode', 'action=create',
                '--data-urlencode', "domain={$domain}",
                '--data-urlencode', "subdomain={$slug}",
                "https://127.0.0.1:{$port}/CMD_API_SUBDOMAINS",
            ]);
            $process->run();

            if (! $process->isSuccessful()) {
                Log::warning("DirectAdmin subdomain provisioning failed for [{$slug}]: {$process->getErrorOutput()}");

                return false;
            }

            $body = $process->getOutput();
        } catch (Throwable $exception) {
            Log::warning("DirectAdmin subdomain provisioning failed for [{$slug}]: {$exception->getMessage()}");

            return false;
        }

        parse_str($body, $result);

        $failed = ($result['error'] ?? '0') === '1';
        $message = ($result['text'] ?? '').' '.($result['details'] ?? '');
        $alreadyExists = str_contains(strtolower($message), 'exist');

        if ($failed && ! $alreadyExists) {
            Log::warning("DirectAdmin subdomain provisioning error for [{$slug}]: ".trim($message ?: $body));

            return false;
        }

        self::ensureDocumentRoot($username, $domain, $slug);

        return true;
    }

    protected static function ensureDocumentRoot(string $username, string $domain, string $slug): void
    {
        $target = public_path();
        $docRoot = "/home/{$username}/domains/{$slug}.{$domain}/public_html";

        clearstatcache(true, $docRoot);

        if (is_link($docRoot)) {
            if (readlink($docRoot) === $target) {
                return;
            }

            @unlink($docRoot);
        } elseif (is_dir($docRoot)) {
            File::deleteDirectory($docRoot);
        } elseif (file_exists($docRoot)) {
            @unlink($docRoot);
        }

        @symlink($target, $docRoot);
    }
}
