<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260714010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Assign services to company addresses';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE service ADD company_address_id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE service ADD CONSTRAINT FK_E19D9AD2199BEB7B FOREIGN KEY (company_address_id) REFERENCES company_address (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_E19D9AD2199BEB7B ON service (company_address_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE service DROP FOREIGN KEY FK_E19D9AD2199BEB7B
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_E19D9AD2199BEB7B ON service
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE service DROP company_address_id
        SQL);
    }
}
