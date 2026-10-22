<?php

declare(strict_types=1);

namespace Lvt\Tests;

use Lvt\App;
use Lvt\Request;
use Lvt\Response;

final class HttpClient
{
    private static ?App $app = null;
    public string $cookie = '';
    public string $ip = '127.0.0.1';
    public string $lastBody = '';

    public function get(string $path): Response
    {
        return $this->request('GET', $path);
    }

    public function post(string $path, array $post): Response
    {
        return $this->request('POST', $path, $post);
    }

    public function follow(Response $response, int $hops = 5): Response
    {
        $current = $response;
        while ($hops-- > 0 && $current->status >= 300 && $current->status < 400) {
            $this->absorb($current);
            $location = (string) ($current->headers['Location'] ?? '');
            if ($location === '') {
                break;
            }
            $current = $this->get($location);
        }
        $this->absorb($current);
        $this->lastBody = $current->body;
        return $current;
    }

    public function csrf(): string
    {
        if (preg_match('/name="_csrf" value="([^"]+)"/', $this->lastBody, $match) === 1) {
            return $match[1];
        }
        throw new \RuntimeException('No CSRF token on the last page.');
    }

    private function request(string $method, string $target, array $post = []): Response
    {
        $parts = parse_url($target);
        $path = (string) ($parts['path'] ?? '/');
        if ($path === '') {
            $path = '/';
        }
        $query = [];
        if (!empty($parts['query'])) {
            parse_str((string) $parts['query'], $query);
        }
        $cookies = [];
        if ($this->cookie !== '') {
            $cookies[(string) env('SESSION_NAME', 'lvt_session')] = $this->cookie;
        }
        $request = new Request(
            $method,
            $path,
            $query,
            $post,
            [],
            $cookies,
            [
                'REMOTE_ADDR' => $this->ip,
                'HTTP_USER_AGENT' => 'phpunit',
                'REQUEST_METHOD' => $method,
                'REQUEST_URI' => $path,
            ]
        );
        $response = self::app()->handle($request);
        $this->absorb($response);
        $this->lastBody = $response->body;
        return $response;
    }

    private function absorb(Response $response): void
    {
        $header = (string) ($response->headers['Set-Cookie'] ?? '');
        $name = (string) env('SESSION_NAME', 'lvt_session');
        if (preg_match('/' . preg_quote($name, '/') . '=([^;]+)/', $header, $match) === 1) {
            $this->cookie = $match[1];
        }
    }

    private static function app(): App
    {
        if (!self::$app instanceof App) {
            $app = require dirname(__DIR__) . '/bootstrap/app.php';
            if (!$app instanceof App) {
                throw new \RuntimeException('bootstrap/app.php did not return the application.');
            }
            self::$app = $app;
        }
        return self::$app;
    }
}
