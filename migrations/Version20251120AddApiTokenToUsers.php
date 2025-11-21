<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251120AddApiTokenToUsers extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add api_token column to users table to support simple token auth';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `users` ADD COLUMN `api_token` VARCHAR(255) DEFAULT NULL AFTER `password`");
        $this->addSql("ALTER TABLE `users` ADD UNIQUE INDEX `UNIQ_users_api_token` (`api_token`)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `users` DROP INDEX `UNIQ_users_api_token`');
        $this->addSql('ALTER TABLE `users` DROP COLUMN `api_token`');
    }
}
