<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250804132633 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change workplace to jobrole';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE job_role (id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)', name VARCHAR(100) NOT NULL, description VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE employee_jobrole (employee_id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)', jobrole_id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)', INDEX IDX_5E9B560E8C03F15C (employee_id), INDEX IDX_5E9B560E7F34E8AE (jobrole_id), PRIMARY KEY(employee_id, jobrole_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE employee_jobrole ADD CONSTRAINT FK_5E9B560E8C03F15C FOREIGN KEY (employee_id) REFERENCES user (uuid)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE employee_jobrole ADD CONSTRAINT FK_5E9B560E7F34E8AE FOREIGN KEY (jobrole_id) REFERENCES job_role (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE employee_workplaces DROP FOREIGN KEY FK_C4BB4B328C03F15C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE employee_workplaces DROP FOREIGN KEY FK_C4BB4B32AC25FB46
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE workplace
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE employee_workplaces
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE workplace (id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)', name VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, description VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE employee_workplaces (employee_id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)', workplace_id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)', INDEX IDX_C4BB4B328C03F15C (employee_id), INDEX IDX_C4BB4B32AC25FB46 (workplace_id), PRIMARY KEY(employee_id, workplace_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE employee_workplaces ADD CONSTRAINT FK_C4BB4B328C03F15C FOREIGN KEY (employee_id) REFERENCES user (uuid) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE employee_workplaces ADD CONSTRAINT FK_C4BB4B32AC25FB46 FOREIGN KEY (workplace_id) REFERENCES workplace (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE employee_jobrole DROP FOREIGN KEY FK_5E9B560E8C03F15C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE employee_jobrole DROP FOREIGN KEY FK_5E9B560E7F34E8AE
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE job_role
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE employee_jobrole
        SQL);
    }
}
