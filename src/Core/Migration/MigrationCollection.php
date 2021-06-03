<?php declare(strict_types=1);

namespace Appflix\DewaShop\Core\Content\Migration;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                add(MigrationEntity $entity)
 * @method void                set(string $key, MigrationEntity $entity)
 * @method MigrationEntity[]    getIterator()
 * @method MigrationEntity[]    getElements()
 * @method MigrationEntity|null get(string $key)
 * @method MigrationEntity|null first()
 * @method MigrationEntity|null last()
 */
class MigrationCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'dewa_migration_collection';
    }

    protected function getExpectedClass(): string
    {
        return MigrationEntity::class;
    }
}
