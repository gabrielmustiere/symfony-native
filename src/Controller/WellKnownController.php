<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class WellKnownController extends AbstractController
{
    /**
     * Apple App Site Association : autorise l'app poc-mobile à utiliser les
     * passkeys WebAuthn (service `webcredentials`) depuis son WKWebView.
     *
     * Servi sans extension, en application/json, comme l'exige iOS.
     */
    #[Route(
        path: '/.well-known/apple-app-site-association',
        name: 'app_well_known_aasa',
        methods: ['GET']
    )]
    public function appleAppSiteAssociation(): JsonResponse
    {
        return new JsonResponse([
            'webcredentials' => [
                'apps' => ['2S4J753898.net.technao.poc-mobile'],
            ],
        ]);
    }

    /**
     * Digital Asset Links : équivalent Android de l'AASA. Autorise l'app
     * poc-mobile à partager les passkeys WebAuthn via le Credential Manager
     * (relation `get_login_creds`) depuis sa WebView Hotwire Native.
     *
     * L'empreinte SHA-256 est celle du certificat de signature de l'APK.
     * Pour le keystore debug : `cd android && ./gradlew signingReport`
     * (variant `debug`, ligne `SHA-256`), puis recopier la valeur ci-dessous.
     */
    #[Route(
        path: '/.well-known/assetlinks.json',
        name: 'app_well_known_assetlinks',
        methods: ['GET']
    )]
    public function assetLinks(): JsonResponse
    {
        return new JsonResponse([
            [
                'relation' => ['delegate_permission/common.get_login_creds'],
                'target' => [
                    'namespace' => 'android_app',
                    'package_name' => 'net.technao.poc_mobile',
                    'sha256_cert_fingerprints' => [
                        // Empreinte du keystore debug (`./gradlew signingReport`).
                        'FE:73:17:05:F2:E6:3C:6C:66:81:2C:BC:B0:28:9F:BF:7B:14:36:AE:0E:AD:12:5F:AF:AA:00:15:B3:E7:29:6A',
                    ],
                ],
            ],
        ]);
    }
}
