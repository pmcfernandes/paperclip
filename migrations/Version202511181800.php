<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251118 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial schema migration created from migrations/20251118_initial.sql';
    }

    public function up(Schema $schema): void
    {
        // Load SQL file and execute each statement separated by semicolons
        $file = __DIR__ . '/20251118_initial.sql';
        if (!is_file($file)) {
            $this->abortIf(true, 'Migration SQL file not found: ' . $file);
            return;
        }

        $sql = file_get_contents($file);
        if ($sql === false) {
            $this->abortIf(true, 'Failed to read migration SQL file: ' . $file);
            return;
        }

        // Normalize line endings and split on semicolon followed by newline (simple splitter)
        $sql = str_replace(["\r\n", "\r"], "\n", $sql);
        $parts = preg_split('/;\s*\n/', $sql);
        foreach ($parts as $part) {
            $stmt = trim($part);
            if ($stmt === '' ) {
                continue;
            }
            // addSql executes the SQL fragment
            $this->addSql($stmt);
        }
    }

    public function down(Schema $schema): void
    {
        // Drop tables in reverse order
        $this->addSql('DROP TABLE IF EXISTS `form_data`');
        $this->addSql('DROP TABLE IF EXISTS `forms`');
        $this->addSql('DROP TABLE IF EXISTS `sites`');
    }
}
