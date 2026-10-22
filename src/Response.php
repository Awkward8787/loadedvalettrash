<?php

declare(strict_types=1);

namespace Lvt;

final class Response
{
    public function __construct(
        public string $body,
        public int $status = 200,
        public array $headers = [],
    ) {
    }

    public static function html(string $html, int $status = 200): self
    {
        return new self($html, $status, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public static function redirect(string $to): self
    {
        return new self('', 302, ['Location' => $to]);
    }

    public static function text(string $body, int $status = 200, string $type = 'text/plain; charset=utf-8'): self
    {
        return new self($body, $status, ['Content-Type' => $type]);
    }

    public function withHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value, true);
        }
        echo $this->body;
    }
}
