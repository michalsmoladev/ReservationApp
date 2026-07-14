<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260714000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create company tables and assign services to companies';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE company (
                id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)',
                display_name VARCHAR(128) NOT NULL,
                legal_name VARCHAR(128) NOT NULL,
                tax_id VARCHAR(30) NOT NULL,
                currency VARCHAR(3) NOT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE company_address (
                id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)',
                company_id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)',
                street VARCHAR(150) NOT NULL,
                city VARCHAR(150) NOT NULL,
                country VARCHAR(150) NOT NULL,
                postal_code VARCHAR(6) NOT NULL,
                apartment_no INT NOT NULL,
                building_no INT NOT NULL,
                name VARCHAR(150) DEFAULT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                INDEX IDX_6B9B73B7979B1AD6 (company_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE tenant_company (
                tenant_id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)',
                company_id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)',
                INDEX IDX_3D6D0E3076AA899A (tenant_id),
                INDEX IDX_3D6D0E30979B1AD6 (company_id),
                PRIMARY KEY(tenant_id, company_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE service ADD company_id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE service ADD CONSTRAINT FK_E19D9AD2979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_E19D9AD2979B1AD6 ON service (company_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE company_address ADD CONSTRAINT FK_6B9B73B7979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tenant_company ADD CONSTRAINT FK_3D6D0E3076AA899A FOREIGN KEY (tenant_id) REFERENCES user (uuid)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tenant_company ADD CONSTRAINT FK_3D6D0E30979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE service DROP FOREIGN KEY FK_E19D9AD2979B1AD6
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE company_address DROP FOREIGN KEY FK_6B9B73B7979B1AD6
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tenant_company DROP FOREIGN KEY FK_3D6D0E3076AA899A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tenant_company DROP FOREIGN KEY FK_3D6D0E30979B1AD6
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_E19D9AD2979B1AD6 ON service
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE service DROP company_id
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE tenant_company
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE company_address
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE company
        SQL);
    }
}
