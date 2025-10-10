<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\HttpClient;

use Http\Discovery\Psr17Factory;
use N1ebieski\KSEFClient\Contracts\HttpClient\ClientInterface;
use N1ebieski\KSEFClient\Contracts\HttpClient\HttpClientInterface;
use N1ebieski\KSEFClient\Contracts\HttpClient\ResponseInterface;
use N1ebieski\KSEFClient\DTOs\Config;
use N1ebieski\KSEFClient\DTOs\HttpClient\Request;
use N1ebieski\KSEFClient\Exceptions\ExceptionHandler;
use N1ebieski\KSEFClient\Exceptions\HttpClient\AsyncClientNotSupportedException;
use N1ebieski\KSEFClient\ValueObjects\AccessToken;
use N1ebieski\KSEFClient\ValueObjects\Requests\Sessions\EncryptedKey;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface as BaseResponseInterface;
use Psr\Log\LoggerInterface;

final class HttpClient implements HttpClientInterface
{
    public function __construct(
        private readonly ClientInterface $client,
        private readonly Config $config,
        private readonly ?LoggerInterface $logger = null
    ) {
    }

    public function withEncryptedKey(EncryptedKey $encryptedKey): self
    {
        return new self(
            client: $this->client,
            config: $this->config->withEncryptedKey($encryptedKey),
            logger: $this->logger
        );
    }

    public function withAccessToken(AccessToken $accessToken): self
    {
        return new self(
            client: $this->client,
            config: $this->config->withAccessToken($accessToken),
            logger: $this->logger
        );
    }

    public function withoutAccessToken(): self
    {
        return new self(
            client: $this->client,
            config: $this->config->withoutAccessToken(),
            logger: $this->logger
        );
    }

    private function createClientRequest(Request $request): RequestInterface
    {
        $psr17Factory = new Psr17Factory();

        $request = $request->withUri($request->uri->withBaseUrl($this->config->baseUri)->withoutSlashAtEnd());

        if ($this->config->accessToken instanceof AccessToken) {
            $request = $request->withHeader('Authorization', "Bearer {$this->config->accessToken->token}");
        }

        $clientRequest = $psr17Factory->createRequest(
            method: $request->method->value,
            uri: $request->uri->withParameters($request->getParametersAsString())->value
        );

        foreach ($request->headers as $name => $value) {
            $clientRequest = $clientRequest->withHeader($name, $value);
        }

        if ($request->method->hasBody()) {
            $body = $request->getBodyAsString();

            $clientRequest = $clientRequest->withBody(
                $psr17Factory->createStream($body)
            );
        }

        return $clientRequest;
    }

    public function sendRequest(Request $request): ResponseInterface
    {
        $clientRequest = $this->createClientRequest($request);

        if ($this->logger instanceof LoggerInterface) {
            $this->logger->debug('Sending request to KSEF', $request->toArray());
        }

        $response = new Response(
            baseResponse: $this->client->sendRequest($clientRequest),
            exceptionHandler: new ExceptionHandler($this->logger)
        );

        if ($this->logger instanceof LoggerInterface) {
            $this->logger->debug('Received response from KSEF', $response->toArray());
        }

        return $response;
    }

    public function sendAsyncRequest(array $requests): array
    {
        try {
            if ($this->logger instanceof LoggerInterface) {
                $this->logger->debug(
                    'Try async sending requests to KSEF',
                    array_map(fn (Request $request): array => $request->toArray(), $requests)
                );
            }

            $clientRequests = array_map([$this, 'createClientRequest'], $requests);

            $clientResponses = $this->client->sendAsyncRequest($clientRequests, $this->config->asyncMaxConcurrency);

            $responses = array_map(function (?BaseResponseInterface $response): ?ResponseInterface {
                if ( ! $response instanceof \Psr\Http\Message\ResponseInterface) {
                    return $response;
                }

                return new Response(
                    baseResponse: $response,
                    exceptionHandler: new ExceptionHandler($this->logger)
                );
            }, $clientResponses);

            if ($this->logger instanceof LoggerInterface) {
                $this->logger->debug(
                    'Received responses from KSEF',
                    array_map(fn (?Response $response): ?array => $response?->toArray(), $responses)
                );
            }

            return $responses;
        } catch (AsyncClientNotSupportedException) {
            if ($this->logger instanceof LoggerInterface) {
                $this->logger->debug(
                    'Async sending requests to KSEF is not supported. Sending requests one by one.',
                );
            }
        }

        $responses = [];

        foreach ($requests as $index => $request) {
            $responses[$index] = $this->sendRequest($request);
        }

        return $responses;
    }
}
