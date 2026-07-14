<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260714020000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Assign employees to companies, locations, and services';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE user ADD company_id BINARY(16) DEFAULT NULL COMMENT '(DC2Type:uuid)', ADD company_address_id BINARY(16) DEFAULT NULL COMMENT '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_8D93D64979B1AD6 ON user (company_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_8D93D64199BEB7B ON user (company_address_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user ADD CONSTRAINT FK_8D93D64979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user ADD CONSTRAINT FK_8D93D64199BEB7B FOREIGN KEY (company_address_id) REFERENCES company_address (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE service_employee (
                service_id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)',
                employee_id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)',
                INDEX IDX_5BB4C5DDED5CA9E6 (service_id),
                INDEX IDX_5BB4C5DD8C03F15C (employee_id),
                PRIMARY KEY(service_id, employee_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE service_employee ADD CONSTRAINT FK_5BB4C5DDED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE service_employee ADD CONSTRAINT FK_5BB4C5DD8C03F15C FOREIGN KEY (employee_id) REFERENCES user (uuid)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE user DROP FOREIGN KEY FK_8D93D64979B1AD6
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user DROP FOREIGN KEY FK_8D93D64199BEB7B
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE service_employee DROP FOREIGN KEY FK_5BB4C5DDED5CA9E6
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE service_employee DROP FOREIGN KEY FK_5BB4C5DD8C03F15C
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE service_employee
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_8D93D64979B1AD6 ON user
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_8D93D64199BEB7B ON user
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user DROP company_id, DROP company_address_id
        SQL);
    }
}
