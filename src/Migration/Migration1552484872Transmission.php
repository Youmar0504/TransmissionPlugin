<?php

namespace Emakers\TransmissionPlugin\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1552484872Transmission extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1552484872;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `transmission` (
    `id` BINARY(16) NOT NULL,
    `orderNumber` BIGINT COLLATE utf8mb4_unicode_ci,
    `productNumber` VARCHAR(255) COLLATE utf8mb4_unicode_ci,
    `customerId` VARCHAR(255) COLLATE utf8mb4_unicode_ci,
    `status` VARCHAR(255) COLLATE utf8mb4_unicode_ci,
    `origine` VARCHAR(255) COLLATE utf8mb4_unicode_ci,
    `destination` VARCHAR(255) COLLATE utf8mb4_unicode_ci,
    `requestType` VARCHAR(255) COLLATE utf8mb4_unicode_ci,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3),
    PRIMARY KEY (`id`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;
SQL;
        $connection->executeUpdate($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
