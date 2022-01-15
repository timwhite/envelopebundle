<?php

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Setup tables for external connection with an API
 */
class Version20210724051046 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE external_connector (id INT NOT NULL, account_id INT DEFAULT NULL, system_id VARCHAR(512) NOT NULL, system_type VARCHAR(64) NOT NULL, system_credential VARCHAR(2048) DEFAULT NULL, INDEX IDX_30D7EA7A9B6B5FBA (account_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE external_connector ADD CONSTRAINT FK_30D7EA7A9B6B5FBA FOREIGN KEY (account_id) REFERENCES account (id)');
        $this->addSql('ALTER TABLE transaction ADD external_id VARCHAR(512) DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE external_connector');
        $this->addSql('ALTER TABLE transaction DROP external_id');
    }
}
