<?php declare(strict_types=1);

namespace Emakers\TransmissionPlugin\Entity;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class TransmissionLogEntityDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'transmission_log';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return TransmissionLogEntityCollection::class;
    }

    public function getEntityClass(): string
    {
        return TransmissionLogEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
             new IntField('orderNumber', 'orderNumber'),
	     new StringField('status', 'status'),
	     new StringField('requestType', 'requestType'),
	     new StringField('targetUrl', 'targetUrl'),	
	     new LongTextField('request', 'request'),
	     new LongTextField('response', 'response'),
	     new DatetimeField('created_at', 'created_at'),
        ]);
    }
}
