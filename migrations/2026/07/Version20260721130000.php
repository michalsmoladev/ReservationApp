<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260721130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add availability calendar tables for company and employee';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE company_opening_hour (
                id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)',
                company_id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)',
                company_address_id BINARY(16) DEFAULT NULL COMMENT '(DC2Type:uuid)',
                day_of_week SMALLINT NOT NULL,
                opens_at TIME DEFAULT NULL COMMENT '(DC2Type:time_immutable)',
                closes_at TIME DEFAULT NULL COMMENT '(DC2Type:time_immutable)',
                is_closed TINYINT(1) NOT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                INDEX IDX_D7A53B66979B1AD6 (company_id),
                INDEX IDX_D7A53B66199BEB7B (company_address_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE employee_working_hour (
                id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)',
                employee_id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)',
                day_of_week SMALLINT NOT NULL,
                starts_at TIME NOT NULL COMMENT '(DC2Type:time_immutable)',
                ends_at TIME NOT NULL COMMENT '(DC2Type:time_immutable)',
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                INDEX IDX_96A9CB428C03F15C (employee_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE employee_absence (
                id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)',
                employee_id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)',
                starts_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                ends_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                reason VARCHAR(255) NOT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                INDEX IDX_575996F18C03F15C (employee_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE company_opening_hour ADD CONSTRAINT FK_D7A53B66979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE company_opening_hour ADD CONSTRAINT FK_D7A53B66199BEB7B FOREIGN KEY (company_address_id) REFERENCES company_address (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE employee_working_hour ADD CONSTRAINT FK_96A9CB428C03F15C FOREIGN KEY (employee_id) REFERENCES user (uuid)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE employee_absence ADD CONSTRAINT FK_575996F18C03F15C FOREIGN KEY (employee_id) REFERENCES user (uuid)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE company_opening_hour DROP FOREIGN KEY FK_D7A53B66979B1AD6
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE company_opening_hour DROP FOREIGN KEY FK_D7A53B66199BEB7B
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE employee_working_hour DROP FOREIGN KEY FK_96A9CB428C03F15C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE employee_absence DROP FOREIGN KEY FK_575996F18C03F15C
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE employee_absence
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE employee_working_hour
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE company_opening_hour
        SQL);
    }
}
