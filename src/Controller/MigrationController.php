<?php

namespace Appflix\DewaMigrationTool\Controller;

use Appflix\DewaMigrationTool\Service\MigrationService;
use Appflix\DewaShop\Core\Defaults;
use Appflix\DewaShop\Core\Service\DataService;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class SettingsController
 * @package Appflix\DewaShop\Administration\Controller
 * @RouteScope(scopes={"api"})
 */
class MigrationController
{
    private MigrationService $migrationService;

    public function __construct(MigrationService $migrationService)
    {
        $this->migrationService = $migrationService;
    }

    /**
     * @Route("/api/dewa/settings/migration/install/{name}/{salesChannelId}", name="api.dewa.settings.demo-data.install", methods={"POST"})
     */
    public function migrationInstall(Request $request): JsonResponse
    {
        if ($salesChannelId && !in_array($salesChannelId, ['undefined','null'])) {
            $this->migrationService->setSalesChannelId($salesChannelId);
        }

        $this->migrationService->remove(Defaults::NAME, 'demo');
        $this->migrationService->install(Defaults::NAME, 'demo', $name);

        return new JsonResponse([]);
    }

    /**
     * @Route("/api/dewa/settings/migration/remove/{salesChannelId}", name="api.dewa.settings.demo-data.remove", methods={"GET"})
     */
    public function migrationRemove(?string $salesChannelId = null): JsonResponse
    {
        if ($salesChannelId && !in_array($salesChannelId, ['undefined','null'])) {
            $this->migrationService->setSalesChannelId($salesChannelId);
        }

        $this->migrationService->remove(Defaults::NAME, 'demo');

        return new JsonResponse([]);
    }
}
