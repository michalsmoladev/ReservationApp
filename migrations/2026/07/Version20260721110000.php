<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260721110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow guest reservations with contact details';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation
                MODIFY customer_id BINARY(16) DEFAULT NULL COMMENT '(DC2Type:uuid)',
                ADD guest_firstname VARCHAR(255) DEFAULT NULL,
                ADD guest_lastname VARCHAR(255) DEFAULT NULL,
                ADD guest_email VARCHAR(255) DEFAULT NULL,
                ADD guest_phone VARCHAR(255) DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation
                DROP guest_firstname,
                DROP guest_lastname,
                DROP guest_email,
                DROP guest_phone,
                MODIFY customer_id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)'
        SQL);
    }
}
