<?php declare(strict_types=1);

namespace Appflix\DewaMigrationTool\Core\Migration;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class MigrationEntity extends Entity
{
    use EntityIdTrait;

    protected string $provider;

    /**
     * @return string
     */
    public function getProvider(): string
    {
        return $this->provider;
    }

    /**
     * @param string $provider
     */
    public function setProvider(string $provider): void
    {
        $this->provider = $provider;
    }
}
