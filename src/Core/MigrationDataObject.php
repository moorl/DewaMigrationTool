<?php declare(strict_types=1);

namespace Appflix\DewaMigrationTool\Core;

use Appflix\DewaMigrationTool\AppflixDewaMigrationTool;
use Appflix\DewaShop\Core\System\DataExtension;
use Appflix\DewaShop\Core\System\DataInterface;

class MigrationDataObject extends DataExtension implements DataInterface
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

    public function getInstallConfig(): array {
        return [
            "AppflixDewaShop.config.registrationFormDewa" => true,
            "AppflixDewaShop.config.checkoutTimepicker" => 'dropdownMinutes',
            "AppflixDewaShop.config.checkoutDropdownSteps" => '30,45,60,90,120'
        ];
    }
}
