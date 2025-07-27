<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250720100708 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add wokrplace, add employee and customer, add address';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE address (id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)', created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', street VARCHAR(255) NOT NULL, city VARCHAR(100) NOT NULL, apartment_no INT NOT NULL, building_no INT NOT NULL, post_code VARCHAR(6) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE workplace (id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)', name VARCHAR(100) NOT NULL, description VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user DROP INDEX UNIQ_8D93D649DC9EE959, ADD INDEX IDX_8D93D649DC9EE959 (metadata_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user ADD type VARCHAR(255) NOT NULL, ADD workplaces VARCHAR(255) DEFAULT NULL, CHANGE is_active is_active TINYINT(1) NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            DROP TABLE address
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE workplace
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user DROP INDEX IDX_8D93D649DC9EE959, ADD UNIQUE INDEX UNIQ_8D93D649DC9EE959 (metadata_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user DROP type, DROP workplaces, CHANGE is_active is_active TINYINT(1) DEFAULT 0 NOT NULL
        SQL);
    }
}
