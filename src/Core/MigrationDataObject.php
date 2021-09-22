<?php declare(strict_types=1);

namespace Appflix\DewaMigrationTool\Core;

use Appflix\DewaMigrationTool\AppflixDewaMigrationTool;
use Appflix\DewaShop\Core\System\DewaShopDataExtension;
use Appflix\Foundation\Core\System\DataInterface;

class MigrationDataObject extends DewaShopDataExtension implements DataInterface
{
    public function getName(): string
    {
        return 'migration';
    }

    public function getType(): string
    {
        return 'migration';
    }

    public function getPath(): string
    {
        return __DIR__;
    }

    public function getPluginName(): string
    {
        return AppflixDewaMigrationTool::NAME;
    }

    public function getCreatedAt(): string
    {
        return AppflixDewaMigrationTool::DATA_CREATED_AT;
    }
}
