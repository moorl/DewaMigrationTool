<?php

namespace Appflix\DewaMigrationTool\Controller;

use Appflix\DewaMigrationTool\Service\MigrationService;
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
     * @Route("/api/dewa/settings/migration/install/{name}/{salesChannelId}", name="api.dewa.settings.migration.install", methods={"POST"})
     */
    public function migrationInstall(Request $request): JsonResponse
    {
        if ($request->get('salesChannelId') && !in_array($request->get('salesChannelId'), ['undefined','null'])) {
            $this->migrationService->setSalesChannelId($request->get('salesChannelId'));
        }

        $this->migrationService->remove();
        $this->migrationService->install($request->get('provider'));

        return new JsonResponse([]);
    }

    /**
     * @Route("/api/dewa/settings/migration/remove/{salesChannelId}", name="api.dewa.settings.migration.remove", methods={"POST"})
     */
    public function migrationRemove(Request $request): JsonResponse
    {
        if ($request->get('salesChannelId') && !in_array($request->get('salesChannelId'), ['undefined','null'])) {
            $this->migrationService->setSalesChannelId($request->get('salesChannelId'));
        }

        $this->migrationService->remove();

        return new JsonResponse([]);
    }
}
