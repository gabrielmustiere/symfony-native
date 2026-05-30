<?php

declare(strict_types=1);

namespace App\Native;

use Symfony\UX\Native\Attribute\AsNativeConfiguration;
use Symfony\UX\Native\Attribute\AsNativeConfigurationProvider;
use Symfony\UX\Native\Configuration\Configuration;
use Symfony\UX\Native\Configuration\Rule;

/**
 * Path configuration de l'app Hotwire Native iOS.
 *
 * En dev (debug), la config est servie dynamiquement à l'URL du path.
 * En prod, exécuter `symfony console ux-native:dump` pour générer le JSON
 * dans public/ (ici : public/config/ios_v1.json).
 */
#[AsNativeConfigurationProvider]
final class AppNativeConfiguration
{
    #[AsNativeConfiguration('/config/ios_v1.json')]
    public function iosV1(): Configuration
    {
        return new Configuration(
            // Aucun réglage global pour l'instant — omis pour ne pas émettre
            // un `settings: []` (tableau) là où iOS attend un objet.
            rules: [
                // Les écrans d'authentification s'ouvrent en modale.
                new Rule(
                    patterns: ['/login$'],
                    properties: [
                        'context' => 'modal',
                        'pull_to_refresh_enabled' => false,
                    ],
                ),
                // Comportement par défaut pour tout le reste.
                new Rule(
                    patterns: ['.*'],
                    properties: [
                        'context' => 'default',
                        'pull_to_refresh_enabled' => true,
                    ],
                ),
            ],
        );
    }
}
