<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260721120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add guest cancellation token to reservations and backfill existing guest reservations';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reservation ADD guest_cancellation_token VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_RESERVATION_GUEST_CANCELLATION_TOKEN ON reservation (guest_cancellation_token)');

        $rows = $this->connection->fetchFirstColumn(
            'SELECT id FROM reservation WHERE customer_id IS NULL AND guest_email IS NOT NULL AND guest_cancellation_token IS NULL'
        );

        foreach ($rows as $reservationId) {
            $this->addSql(
                'UPDATE reservation SET guest_cancellation_token = :token WHERE id = :id',
                [
                    'token' => bin2hex(random_bytes(16)),
                    'id' => $reservationId,
                ],
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_RESERVATION_GUEST_CANCELLATION_TOKEN');
        $this->addSql('ALTER TABLE reservation DROP guest_cancellation_token');
    }
}
