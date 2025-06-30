<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250619202147 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add UserMetadata';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE user_metadata (
                id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)', 
                activation_token VARCHAR(255) NOT NULL, 
                activation_expires_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', 
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user ADD metadata_id BINARY(16) DEFAULT NULL COMMENT '(DC2Type:uuid)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user ADD CONSTRAINT FK_8D93D649DC9EE959 FOREIGN KEY (metadata_id) REFERENCES user_metadata (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_8D93D649DC9EE959 ON user (metadata_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE user DROP FOREIGN KEY FK_8D93D649DC9EE959
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user_metadata
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_8D93D649DC9EE959 ON user
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user DROP metadata_id
        SQL);
    }
}
