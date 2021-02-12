<?php declare(strict_types=1);

namespace Emakers\TransmissionPlugin\Entity;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void              add(TransmissionLogEntity $entity)
 * @method void              set(string $key, TransmissionLogEntity $entity)
 * @method TransmissionLogEntity[]    getIterator()
 * @method TransmissionLogEntity[]    getElements()
 * @method TransmissionLogEntity|null get(string $key)
 * @method TransmissionLogEntity|null first()
 * @method TransmissionLogEntity|null last()
 */
class TransmissionLogEntityCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return TransmissionLogEntity::class;
    }
}
