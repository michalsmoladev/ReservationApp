<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250425152254 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create User';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE user (
                uuid BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)', 
                email VARCHAR(255) NOT NULL, 
                password VARCHAR(255) NOT NULL, 
                roles JSON NOT NULL, 
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', 
                updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', 
                PRIMARY KEY(uuid)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            DROP TABLE user
        SQL);
    }
}
