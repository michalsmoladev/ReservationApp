<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260713000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create reservation service table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE service (
                id CHAR(36) NOT NULL COMMENT '(DC2Type:uuid)',
                name VARCHAR(120) NOT NULL,
                description VARCHAR(255) DEFAULT NULL,
                duration DOUBLE PRECISION NOT NULL,
                price DOUBLE PRECISION NOT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                UNIQUE INDEX UNIQ_E19D9AD2BF396750 (id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            DROP TABLE service
        SQL);
    }
}
