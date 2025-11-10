<?php

declare(strict_types=1);

namespace ISER\Core\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use GuzzleHttp\Psr7\Response as Psr7Response;
use GuzzleHttp\Psr7\Utils;

/**
 * Response - Implementación PSR-7
 *
 * Wrapper para Response que implementa PSR-7 HTTP Message Interface
 *
 * @package ISER\Core\Http
 */
class Response implements ResponseInterface
{
    private ResponseInterface $response;

    /**
     * Constructor
     *
     * @param int $status Status code
     * @param array $headers Headers
     * @param string|StreamInterface|null $body Body
     */
    public function __construct(
        int $status = 200,
        array $headers = [],
        string|StreamInterface|null $body = null
    ) {
        $this->response = new Psr7Response($status, $headers, $body);
    }

    /**
     * {@inheritDoc}
     */
    public function getProtocolVersion(): string
    {
        return $this->response->getProtocolVersion();
    }

    /**
     * {@inheritDoc}
     */
    public function withProtocolVersion(string $version): static
    {
        $new = clone $this;
        $new->response = $this->response->withProtocolVersion($version);
        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function getHeaders(): array
    {
        return $this->response->getHeaders();
    }

    /**
     * {@inheritDoc}
     */
    public function hasHeader(string $name): bool
    {
        return $this->response->hasHeader($name);
    }

    /**
     * {@inheritDoc}
     */
    public function getHeader(string $name): array
    {
        return $this->response->getHeader($name);
    }

    /**
     * {@inheritDoc}
     */
    public function getHeaderLine(string $name): string
    {
        return $this->response->getHeaderLine($name);
    }

    /**
     * {@inheritDoc}
     */
    public function withHeader(string $name, $value): static
    {
        $new = clone $this;
        $new->response = $this->response->withHeader($name, $value);
        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function withAddedHeader(string $name, $value): static
    {
        $new = clone $this;
        $new->response = $this->response->withAddedHeader($name, $value);
        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function withoutHeader(string $name): static
    {
        $new = clone $this;
        $new->response = $this->response->withoutHeader($name);
        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function getBody(): StreamInterface
    {
        return $this->response->getBody();
    }

    /**
     * {@inheritDoc}
     */
    public function withBody(StreamInterface $body): static
    {
        $new = clone $this;
        $new->response = $this->response->withBody($body);
        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }

    /**
     * {@inheritDoc}
     */
    public function withStatus(int $code, string $reasonPhrase = ''): static
    {
        $new = clone $this;
        $new->response = $this->response->withStatus($code, $reasonPhrase);
        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function getReasonPhrase(): string
    {
        return $this->response->getReasonPhrase();
    }

    // ===== Métodos de conveniencia =====

    /**
     * Crear respuesta JSON
     *
     * @param mixed $data Datos a serializar
     * @param int $status Status code
     * @param int $options Opciones de json_encode
     * @return self
     */
    public static function json(
        $data,
        int $status = 200,
        int $options = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    ): self {
        $json = json_encode($data, $options);

        if ($json === false) {
            throw new \RuntimeException('Error encoding JSON: ' . json_last_error_msg());
        }

        return new self(
            $status,
            ['Content-Type' => 'application/json; charset=utf-8'],
            $json
        );
    }

    /**
     * Crear respuesta HTML
     *
     * @param string $html Contenido HTML
     * @param int $status Status code
     * @return self
     */
    public static function html(string $html, int $status = 200): self
    {
        return new self(
            $status,
            ['Content-Type' => 'text/html; charset=utf-8'],
            $html
        );
    }

    /**
     * Crear respuesta de redirección
     *
     * @param string $url URL de destino
     * @param int $status Status code (301, 302, etc.)
     * @return self
     */
    public static function redirect(string $url, int $status = 302): self
    {
        return new self($status, ['Location' => $url]);
    }

    /**
     * Crear respuesta vacía
     *
     * @param int $status Status code
     * @return self
     */
    public static function noContent(int $status = 204): self
    {
        return new self($status);
    }

    /**
     * Crear respuesta de archivo para descarga
     *
     * @param string $path Ruta al archivo
     * @param string|null $filename Nombre del archivo (opcional)
     * @return self
     */
    public static function download(string $path, ?string $filename = null): self
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("File not found: {$path}");
        }

        $filename = $filename ?? basename($path);
        $stream = Utils::streamFor(fopen($path, 'r'));

        return (new self(200, [], $stream))
            ->withHeader('Content-Type', mime_content_type($path) ?: 'application/octet-stream')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->withHeader('Content-Length', (string)filesize($path));
    }

    /**
     * Enviar la respuesta al cliente
     *
     * @return void
     */
    public function send(): void
    {
        // Enviar status code
        http_response_code($this->getStatusCode());

        // Enviar headers
        foreach ($this->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header(sprintf('%s: %s', $name, $value), false);
            }
        }

        // Enviar body
        echo (string)$this->getBody();
    }

    /**
     * Convertir a string
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->getBody();
    }
}
