<?php declare(strict_types=1);

namespace Appflix\DewaMigrationTool;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;

class AppflixDewaMigrationTool extends Plugin
{
    public const NAME = 'AppflixDewaMigrationTool';
    public const DATA_CREATED_AT = '2001-02-03 01:02:04.000';
    public const PLUGIN_TABLES = ['dewa_migration'];

    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);

        if ($uninstallContext->keepUserData()) {
            return;
        }

        $this->uninstallTrait();
    }

    private function uninstallTrait(): void
    {
        $connection = $this->container->get(Connection::class);

        foreach (self::PLUGIN_TABLES as $table) {
            $sql = sprintf('SET FOREIGN_KEY_CHECKS=0; DROP TABLE IF EXISTS `%s`;', $table);
            $connection->executeStatement($sql);
        }
    }
}
