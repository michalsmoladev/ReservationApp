<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250720101557 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add relation table between employee and workplace';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE employee_workplaces (employee_id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)', workplace_id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)', INDEX IDX_C4BB4B328C03F15C (employee_id), INDEX IDX_C4BB4B32AC25FB46 (workplace_id), PRIMARY KEY(employee_id, workplace_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE employee_workplaces ADD CONSTRAINT FK_C4BB4B328C03F15C FOREIGN KEY (employee_id) REFERENCES user (uuid)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE employee_workplaces ADD CONSTRAINT FK_C4BB4B32AC25FB46 FOREIGN KEY (workplace_id) REFERENCES workplace (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user DROP workplaces
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE employee_workplaces DROP FOREIGN KEY FK_C4BB4B328C03F15C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE employee_workplaces DROP FOREIGN KEY FK_C4BB4B32AC25FB46
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE employee_workplaces
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user ADD workplaces VARCHAR(255) DEFAULT NULL
        SQL);
    }
}
