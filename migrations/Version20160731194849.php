<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160731194849 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE template ADD accessgroup_id INT NOT NULL');
        $this->addSql('UPDATE template SET accessgroup_id=1'); // Just assign to first user for upgrade
        $this->addSql('ALTER TABLE template ADD CONSTRAINT FK_97601F834239E22F FOREIGN KEY (accessgroup_id) REFERENCES access_group (id)');
        $this->addSql('CREATE INDEX IDX_97601F834239E22F ON template (accessgroup_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE template DROP FOREIGN KEY FK_97601F834239E22F');
        $this->addSql('DROP INDEX IDX_97601F834239E22F ON template');
        $this->addSql('ALTER TABLE template DROP accessgroup_id');
    }
}
