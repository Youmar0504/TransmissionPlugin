<?php declare(strict_types=1);

namespace Emakers\TransmissionPlugin\Entity;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Defaults;

class TransmissionEntityDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'transmission';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return TransmissionEntityCollection::class;
    }

    public function getEntityClass(): string
    {
        return TransmissionEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
             new IntField('orderNumber', 'orderNumber'),
	     new StringField('productNumber', 'productNumber'),
	     new StringField('customerId', 'customerId'),
	     new StringField('status', 'status'),
	     new StringField('origine', 'origine'),
	     new StringField('destination', 'destination'),	
	     new StringField('requestType', 'requestType'),
	     new CreatedAtField('createdAt', 'createdAt'),
	     new UpdatedAtField('updatedAt', 'updatedAt'),
        ]);
    }
}
