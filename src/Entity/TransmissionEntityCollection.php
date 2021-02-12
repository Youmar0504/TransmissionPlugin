<?php declare(strict_types=1);

namespace Emakers\TransmissionPlugin\Entity;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void              add(TransmissionEntity $entity)
 * @method void              set(string $key, TransmissionEntity $entity)
 * @method TransmissionEntity[]    getIterator()
 * @method TransmissionEntity[]    getElements()
 * @method TransmissionEntity|null get(string $key)
 * @method TransmissionEntity|null first()
 * @method TransmissionEntity|null last()
 */
class TransmissionEntityCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return TransmissionEntity::class;
    }
}
