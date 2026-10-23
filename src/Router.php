<?php

declare(strict_types=1);

namespace Lvt;

final class Router
{
    /** @var list<array{method:string,pattern:string,regex:string,keys:list<string>,handler:array{0:class-string,1:string},middleware:list<string>}> */
    private array $routes = [];

    public function get(string $path, array $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    private function add(string $method, string $path, array $handler, array $middleware): void
    {
        $keys = [];
        $regex = preg_replace_callback('/\{([a-z_]+)\}/', function (array $m) use (&$keys): string {
            $keys[] = $m[1];
            return '([^/]+)';
        }, $path);
        $this->routes[] = [
            'method' => $method,
            'pattern' => $path,
            'regex' => '#^' . $regex . '$#',
            'keys' => $keys,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->method === 'HEAD' ? 'GET' : $request->method;
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            if (preg_match($route['regex'], $request->path, $matches) !== 1) {
                continue;
            }
            $params = [];
            foreach ($route['keys'] as $i => $key) {
                $params[$key] = $matches[$i + 1];
            }
            $this->runMiddleware($route['middleware']);
            [$class, $action] = $route['handler'];
            $controller = new $class();
            $response = $controller->{$action}($params);
            if (!$response instanceof Response) {
                throw new \RuntimeException('Controller did not return a response.');
            }
            if ($request->method === 'HEAD') {
                $response->body = '';
            }
            return $response;
        }
        throw new HttpException(404, 'That page is not on this site.');
    }

    private function runMiddleware(array $middleware): void
    {
        foreach ($middleware as $item) {
            if ($item === 'auth') {
                $this->requireAuth();
                continue;
            }
            if (str_starts_with($item, 'role:')) {
                $roles = explode(',', substr($item, 5));
                $user = Auth::user();
                if (!$user || !in_array($user['role'], $roles, true)) {
                    throw new HttpException(403, 'You do not have access to that page.');
                }
            }
        }
    }

    private function requireAuth(): void
    {
        if (Auth::user()) {
            return;
        }
        $path = Request::current()->path;
        if (Auth::enrolling() && ($path === '/account/security' || $path === '/logout')) {
            return;
        }
        if (Auth::enrolling()) {
            throw new HttpException(302, '/account/security');
        }
        $next = rawurlencode(Request::current()->path);
        throw new HttpException(302, '/login?next=' . $next);
    }
}
