<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250830161238 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change firstname and lastname to User from Customer';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE
              user
            CHANGE
              firstname firstname VARCHAR(255) NOT NULL,
            CHANGE
              lastname lastname VARCHAR(255) NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE
              user
            CHANGE
              firstname firstname VARCHAR(255) DEFAULT NULL,
            CHANGE
              lastname lastname VARCHAR(255) DEFAULT NULL
        SQL);
    }
}
