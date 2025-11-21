<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251119AddAutoIncrementToIds extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make forms.id and sites.id AUTO_INCREMENT';
    }

    public function up(Schema $schema): void
    {
        // Ensure id columns are AUTO_INCREMENT so inserts generate ids
        $this->addSql("ALTER TABLE `forms` MODIFY `id` INT NOT NULL AUTO_INCREMENT");
        $this->addSql("ALTER TABLE `sites` MODIFY `id` INT NOT NULL AUTO_INCREMENT");
    }

    public function down(Schema $schema): void
    {
        // Revert AUTO_INCREMENT attribute
        $this->addSql("ALTER TABLE `forms` MODIFY `id` INT NOT NULL");
        $this->addSql("ALTER TABLE `sites` MODIFY `id` INT NOT NULL");
    }
}
