<?php

declare(strict_types=1);

namespace Lvt;

final class App
{
    public function __construct(private Router $router)
    {
    }

    public function handle(Request $request): Response
    {
        Request::setCurrent($request);
        Auth::resetCache();
        Settings::flush();
        $GLOBALS['csp_nonce'] = rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');
        Session::start($request);
        $GLOBALS['_flashes'] = Session::pullFlashes();
        $GLOBALS['_old'] = Session::pullOld();
        $GLOBALS['_errors'] = [];
        try {
            if (!$this->installed() && !str_starts_with($request->path, '/assets/')) {
                $response = Response::html(
                    '<!doctype html><meta charset="utf-8"><title>Setup</title><h1>Database is not ready</h1><p>Run <code>php bin/migrate.php</code>, then <code>php bin/seed.php</code>.</p>',
                    503
                );
            } else {
                $response = $this->router->dispatch($request);
            }
        } catch (HttpException $e) {
            if ($e->status === 302) {
                $response = Response::redirect($e->getMessage());
            } else {
                $response = $this->errorPage($e->status, $e->getMessage());
            }
        } catch (\Throwable $e) {
            $this->log($e);
            $message = env('APP_DEBUG') === 'true' && env('APP_ENV') !== 'production'
                ? $e->getMessage()
                : 'Something went wrong. Please try again.';
            $response = $this->errorPage(500, $message);
        }
        $response->headers['X-Content-Type-Options'] = 'nosniff';
        $response->headers['X-Frame-Options'] = 'DENY';
        $response->headers['Referrer-Policy'] = 'strict-origin-when-cross-origin';
        $response->headers['Permissions-Policy'] = 'camera=(), geolocation=(), microphone=()';
        $nonce = (string) ($GLOBALS['csp_nonce'] ?? '');
        $response->headers['Content-Security-Policy'] = "default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self' 'nonce-{$nonce}'; form-action 'self'; base-uri 'self'; frame-ancestors 'none'; object-src 'none'";
        $response->headers['Set-Cookie'] = Session::cookieHeader();
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        return $response;
    }

    private function installed(): bool
    {
        try {
            return Database::value('SELECT COUNT(*) FROM settings WHERE id = 1') !== false
                && (int) Database::value('SELECT COUNT(*) FROM settings WHERE id = 1') === 1;
        } catch (\Throwable) {
            return false;
        }
    }

    private function errorPage(int $status, string $message): Response
    {
        $title = match ($status) {
            403 => 'Not allowed',
            404 => 'Page not found',
            419 => 'Form expired',
            422 => 'Check the form',
            default => 'Something went wrong',
        };
        try {
            return View::page('errors/status', [
                'title' => $title,
                'documentTitle' => $title,
                'description' => $message,
                'message' => $message,
                'code' => $status,
                'status' => $status,
            ]);
        } catch (\Throwable) {
            return Response::html('<!doctype html><meta charset="utf-8"><title>' . e($title) . '</title><p>' . e($message) . '</p>', $status);
        }
    }

    private function log(\Throwable $e): void
    {
        $dir = LVT_ROOT . '/storage/logs';
        if (!is_dir($dir)) {
            mkdir($dir, 0770, true);
        }
        $line = now_dt() . ' ' . $e::class . ' ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL;
        file_put_contents($dir . '/app.log', $line, FILE_APPEND);
    }
}
