<?php

declare(strict_types=1);

namespace ISER\Core\Http;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use GuzzleHttp\Psr7\ServerRequest;

/**
 * Request - Implementación PSR-7
 *
 * Wrapper para ServerRequest que implementa PSR-7 HTTP Message Interface
 *
 * @package ISER\Core\Http
 */
class Request implements ServerRequestInterface
{
    private ServerRequestInterface $request;

    /**
     * Constructor
     *
     * @param ServerRequestInterface|null $request Request PSR-7 (null para crear desde globales)
     */
    public function __construct(?ServerRequestInterface $request = null)
    {
        $this->request = $request ?? ServerRequest::fromGlobals();
    }

    /**
     * Crear Request desde variables globales de PHP
     *
     * @return self
     */
    public static function createFromGlobals(): self
    {
        return new self(ServerRequest::fromGlobals());
    }

    /**
     * {@inheritDoc}
     */
    public function getProtocolVersion(): string
    {
        return $this->request->getProtocolVersion();
    }

    /**
     * {@inheritDoc}
     */
    public function withProtocolVersion(string $version): static
    {
        $new = clone $this;
        $new->request = $this->request->withProtocolVersion($version);
        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function getHeaders(): array
    {
        return $this->request->getHeaders();
    }

    /**
     * {@inheritDoc}
     */
    public function hasHeader(string $name): bool
    {
        return $this->request->hasHeader($name);
    }

    /**
     * {@inheritDoc}
     */
    public function getHeader(string $name): array
    {
        return $this->request->getHeader($name);
    }

    /**
     * {@inheritDoc}
     */
    public function getHeaderLine(string $name): string
    {
        return $this->request->getHeaderLine($name);
    }

    /**
     * {@inheritDoc}
     */
    public function withHeader(string $name, $value): static
    {
        $new = clone $this;
        $new->request = $this->request->withHeader($name, $value);
        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function withAddedHeader(string $name, $value): static
    {
        $new = clone $this;
        $new->request = $this->request->withAddedHeader($name, $value);
        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function withoutHeader(string $name): static
    {
        $new = clone $this;
        $new->request = $this->request->withoutHeader($name);
        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function getBody(): StreamInterface
    {
        return $this->request->getBody();
    }

    /**
     * {@inheritDoc}
     */
    public function withBody(StreamInterface $body): static
    {
        $new = clone $this;
        $new->request = $this->request->withBody($body);
        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function getRequestTarget(): string
    {
        return $this->request->getRequestTarget();
    }

    /**
     * {@inheritDoc}
     */
    public function withRequestTarget(string $requestTarget): static
    {
        $new = clone $this;
        $new->request = $this->request->withRequestTarget($requestTarget);
        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function getMethod(): string
    {
        return $this->request->getMethod();
    }

    /**
     * {@inheritDoc}
     */
    public function withMethod(string $method): static
    {
        $new = clone $this;
        $new->request = $this->request->withMethod($method);
        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function getUri(): UriInterface
    {
        return $this->request->getUri();
    }

    /**
     * {@inheritDoc}
     */
    public function withUri(UriInterface $uri, bool $preserveHost = false): static
    {
        $new = clone $this;
        $new->request = $this->request->withUri($uri, $preserveHost);
        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function getServerParams(): array
    {
        return $this->request->getServerParams();
    }

    /**
     * {@inheritDoc}
     */
    public function getCookieParams(): array
    {
        return $this->request->getCookieParams();
    }

    /**
     * {@inheritDoc}
     */
    public function withCookieParams(array $cookies): static
    {
        $new = clone $this;
        $new->request = $this->request->withCookieParams($cookies);
        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function getQueryParams(): array
    {
        return $this->request->getQueryParams();
    }

    /**
     * {@inheritDoc}
     */
    public function withQueryParams(array $query): static
    {
        $new = clone $this;
        $new->request = $this->request->withQueryParams($query);
        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function getUploadedFiles(): array
    {
        return $this->request->getUploadedFiles();
    }

    /**
     * {@inheritDoc}
     */
    public function withUploadedFiles(array $uploadedFiles): static
    {
        $new = clone $this;
        $new->request = $this->request->withUploadedFiles($uploadedFiles);
        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function getParsedBody()
    {
        return $this->request->getParsedBody();
    }

    /**
     * {@inheritDoc}
     */
    public function withParsedBody($data): static
    {
        $new = clone $this;
        $new->request = $this->request->withParsedBody($data);
        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function getAttributes(): array
    {
        return $this->request->getAttributes();
    }

    /**
     * {@inheritDoc}
     */
    public function getAttribute(string $name, $default = null)
    {
        return $this->request->getAttribute($name, $default);
    }

    /**
     * {@inheritDoc}
     */
    public function withAttribute(string $name, $value): static
    {
        $new = clone $this;
        $new->request = $this->request->withAttribute($name, $value);
        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function withoutAttribute(string $name): static
    {
        $new = clone $this;
        $new->request = $this->request->withoutAttribute($name);
        return $new;
    }

    // ===== Métodos de conveniencia =====

    /**
     * Obtener un valor del query string
     *
     * @param string $key Clave
     * @param mixed $default Valor por defecto
     * @return mixed
     */
    public function query(string $key, $default = null)
    {
        $params = $this->getQueryParams();
        return $params[$key] ?? $default;
    }

    /**
     * Obtener un valor del body
     *
     * @param string $key Clave
     * @param mixed $default Valor por defecto
     * @return mixed
     */
    public function input(string $key, $default = null)
    {
        $body = $this->getParsedBody();

        if (is_array($body)) {
            return $body[$key] ?? $default;
        }

        if (is_object($body) && isset($body->$key)) {
            return $body->$key;
        }

        return $default;
    }

    /**
     * Verificar si es una solicitud AJAX
     *
     * @return bool
     */
    public function isAjax(): bool
    {
        return $this->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * Verificar si es una solicitud JSON
     *
     * @return bool
     */
    public function wantsJson(): bool
    {
        $accept = $this->getHeaderLine('Accept');
        return str_contains($accept, 'application/json');
    }

    /**
     * Obtener la IP del cliente
     *
     * @return string|null
     */
    public function getClientIp(): ?string
    {
        $server = $this->getServerParams();

        if (!empty($server['HTTP_CLIENT_IP'])) {
            return $server['HTTP_CLIENT_IP'];
        }

        if (!empty($server['HTTP_X_FORWARDED_FOR'])) {
            return $server['HTTP_X_FORWARDED_FOR'];
        }

        return $server['REMOTE_ADDR'] ?? null;
    }
}
