<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260721000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create reservation table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE reservation (
                id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)',
                service_id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)',
                customer_id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)',
                employee_id BINARY(16) DEFAULT NULL COMMENT '(DC2Type:uuid)',
                reservation_date DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                status VARCHAR(255) NOT NULL,
                service_price DOUBLE PRECISION NOT NULL,
                service_duration DOUBLE PRECISION NOT NULL,
                note VARCHAR(255) DEFAULT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                INDEX IDX_42C84955ED5CA9E6 (service_id),
                INDEX IDX_42C849559395C3F3 (customer_id),
                INDEX IDX_42C849558C03F15C (employee_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation ADD CONSTRAINT FK_42C84955ED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation ADD CONSTRAINT FK_42C849559395C3F3 FOREIGN KEY (customer_id) REFERENCES user (uuid)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation ADD CONSTRAINT FK_42C849558C03F15C FOREIGN KEY (employee_id) REFERENCES user (uuid)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955ED5CA9E6
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation DROP FOREIGN KEY FK_42C849559395C3F3
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation DROP FOREIGN KEY FK_42C849558C03F15C
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE reservation
        SQL);
    }
}
