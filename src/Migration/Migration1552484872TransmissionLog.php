<?php 

namespace Emakers\TransmissionPlugin\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1552484872TransmissionLog extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1552484872;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `transmission_log` (
    `id` BINARY(16) NOT NULL,
    `orderNumber` BIGINT COLLATE utf8mb4_unicode_ci,
    `targetUrl` VARCHAR(255) COLLATE utf8mb4_unicode_ci,
    `status` VARCHAR(255) COLLATE utf8mb4_unicode_ci,
    `request` LONGTEXT COLLATE utf8mb4_unicode_ci,
    `response` LONGTEXT COLLATE utf8mb4_unicode_ci,
    `requestType` VARCHAR(255) COLLATE utf8mb4_unicode_ci,
    `created_at` DATETIME(3) NOT NULL,
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
