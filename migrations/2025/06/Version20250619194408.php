<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250619194408 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add isActive to User';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE user ADD is_active TINYINT(1) NOT NULL DEFAULT 0;
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE user DROP is_active
        SQL);
    }
}
