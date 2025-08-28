<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250819191035 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add firstname, lastname and phone in Customer';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE
              user
            ADD
              firstname VARCHAR(255) DEFAULT NULL,
            ADD
              lastname VARCHAR(255) DEFAULT NULL,
            ADD
              phone VARCHAR(255) DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE user DROP firstname, DROP lastname, DROP phone
        SQL);
    }
}
