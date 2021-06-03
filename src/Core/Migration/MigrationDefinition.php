<?php declare(strict_types=1);

namespace Appflix\DewaShop\Core\Content\Migration;

use Appflix\DewaShop\Core\Content\Ingredient\Aggregate\IngredientMigration\IngredientMigrationDefinition;
use Appflix\DewaShop\Core\Content\Ingredient\IngredientDefinition;
use Appflix\DewaShop\Core\Content\Option\OptionDefinition;
use Appflix\DewaShop\Core\Content\Migration\Aggregate\MigrationTranslation\MigrationTranslationDefinition;
use Appflix\DewaShop\Core\Framework\DataAbstractionLayer\Field\Flags\EditField;
use Appflix\DewaShop\Core\Framework\DataAbstractionLayer\Field\Flags\LabelProperty;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Inherited;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\SearchRanking;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class MigrationDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'dewa_migration';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return MigrationCollection::class;
    }

    public function getEntityClass(): string
    {
        return MigrationEntity::class;
    }

    public function getDefaults(): array
    {
        return [
            'purchaseUnit' => 1
        ];
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
        ]);
    }
}
