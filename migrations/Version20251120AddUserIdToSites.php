<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251120AddUserIdToSites extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add nullable user_id column to sites and create FK to users(id)';
    }

    public function up(Schema $schema): void
    {
        // Add nullable user_id column
        $this->addSql("ALTER TABLE `sites` ADD COLUMN `user_id` INT DEFAULT NULL AFTER `webhook_token`");
    }

    public function down(Schema $schema): void
    {
        // Drop FK then index then column
        $this->addSql("ALTER TABLE `sites` DROP COLUMN `user_id`");
    }
}
