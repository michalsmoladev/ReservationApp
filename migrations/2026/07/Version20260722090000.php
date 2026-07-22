<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260722090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_active flag to company for soft deactivation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE company ADD is_active TINYINT(1) NOT NULL DEFAULT 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE company DROP is_active');
    }
}
