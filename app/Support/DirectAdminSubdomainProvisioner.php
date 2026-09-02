<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
        $host = config('services.directadmin.host');
        $username = config('services.directadmin.username');
        $loginKey = config('services.directadmin.login_key');
        $domain = config('services.directadmin.domain');

        if (blank($host) || blank($username) || blank($loginKey) || blank($domain)) {
            return false;
        }

        $port = config('services.directadmin.port', 2222);

        try {
            $response = Http::withBasicAuth($username, $loginKey)
                ->asForm()
                ->timeout(15)
                ->post("https://{$host}:{$port}/CMD_API_SUBDOMAINS", [
                    'action' => 'create',
                    'domain' => $domain,
                    'subdomain' => $slug,
                ]);
        } catch (Throwable $exception) {
            Log::warning("DirectAdmin subdomain provisioning failed for [{$slug}]: {$exception->getMessage()}");

            return false;
        }

        parse_str($response->body(), $result);

        $failed = ($result['error'] ?? '0') === '1';
        $alreadyExists = str_contains(strtolower($result['text'] ?? ''), 'exist');

        if ($failed && ! $alreadyExists) {
            Log::warning("DirectAdmin subdomain provisioning error for [{$slug}]: ".($result['text'] ?? $response->body()));

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
