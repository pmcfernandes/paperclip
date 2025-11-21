<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251118AddSubmitId extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add submit_id column to form_data for correlating submissions';
    }

    public function up(Schema $schema): void
    {
        // add nullable submit_id column to store external submission identifier
        $this->addSql("ALTER TABLE `form_data` ADD COLUMN `submit_id` INT NOT NULL DEFAULT 0 AFTER `form_id`");
    }

    public function down(Schema $schema): void
    {
        // remove the submit_id column
        $this->addSql('ALTER TABLE `form_data` DROP COLUMN `submit_id`');
    }
}
