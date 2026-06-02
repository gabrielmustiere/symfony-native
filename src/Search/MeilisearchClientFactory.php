<?php

declare(strict_types=1);

namespace App\Search;

use Meilisearch\Client;
use Symfony\Component\HttpClient\Psr18Client;

/**
 * Construit le client Meilisearch en s'appuyant sur le PSR-18 de symfony/http-client
 * (déjà présent) — évite toute auto-discovery hasardeuse.
 */
final readonly class MeilisearchClientFactory
{
    public function __construct(
        private string $url,
        private string $masterKey,
    ) {
    }

    public function create(): Client
    {
        $psr18 = new Psr18Client();

        return new Client($this->url, $this->masterKey, $psr18, $psr18, [], $psr18);
    }
}
