<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Allow storing TNT Classifier data
 */
final class Version20250101043824 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow storing TNT Classifier data';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE access_group 
            ADD classifier_serialized LONGTEXT DEFAULT NULL, 
            ADD last_classified DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE access_group 
            DROP classifier_serialized, 
            DROP last_classified');
    }
}
