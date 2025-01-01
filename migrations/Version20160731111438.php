<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160731111438 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' != $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D19B6B5FBA');
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D1B6A263D9');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D19B6B5FBA FOREIGN KEY (account_id) REFERENCES account (id)');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D1B6A263D9 FOREIGN KEY (import_id) REFERENCES import (id)');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D6494239E22F');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6494239E22F FOREIGN KEY (accessgroup_id) REFERENCES access_group (id)');
        $this->addSql('ALTER TABLE account DROP FOREIGN KEY FK_7D3656A44239E22F');
        $this->addSql('ALTER TABLE account ADD CONSTRAINT FK_7D3656A44239E22F FOREIGN KEY (accessgroup_id) REFERENCES access_group (id)');
        $this->addSql('ALTER TABLE budget_group DROP FOREIGN KEY FK_6D1D1334239E22F');
        $this->addSql('ALTER TABLE budget_group ADD CONSTRAINT FK_6D1D1334239E22F FOREIGN KEY (accessgroup_id) REFERENCES access_group (id)');
        $this->addSql('ALTER TABLE budget_account DROP FOREIGN KEY FK_BFB51C456D1D133');
        $this->addSql('ALTER TABLE budget_account ADD CONSTRAINT FK_BFB51C456D1D133 FOREIGN KEY (budget_group) REFERENCES budget_group (id)');
        $this->addSql('ALTER TABLE template_transaction DROP FOREIGN KEY FK_79CE11B54D6C90FA');
        $this->addSql('ALTER TABLE template_transaction DROP FOREIGN KEY FK_79CE11B55DA0FB8');
        $this->addSql('ALTER TABLE template_transaction ADD CONSTRAINT FK_79CE11B54D6C90FA FOREIGN KEY (budget_account_id) REFERENCES budget_account (id)');
        $this->addSql('ALTER TABLE template_transaction ADD CONSTRAINT FK_79CE11B55DA0FB8 FOREIGN KEY (template_id) REFERENCES template (id)');
        $this->addSql('ALTER TABLE template ADD Archived TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE budget_transaction DROP FOREIGN KEY FK_43D438E2FC0CB0F');
        $this->addSql('ALTER TABLE budget_transaction DROP FOREIGN KEY FK_43D438E4D6C90FA');
        $this->addSql('ALTER TABLE budget_transaction ADD CONSTRAINT FK_43D438E2FC0CB0F FOREIGN KEY (transaction_id) REFERENCES transaction (id)');
        $this->addSql('ALTER TABLE budget_transaction ADD CONSTRAINT FK_43D438E4D6C90FA FOREIGN KEY (budget_account_id) REFERENCES budget_account (id)');
        $this->addSql('ALTER TABLE auto_code_search DROP FOREIGN KEY FK_7A17F7C54D6C90FA');
        $this->addSql('ALTER TABLE auto_code_search ADD CONSTRAINT FK_7A17F7C54D6C90FA FOREIGN KEY (budget_account_id) REFERENCES budget_account (id)');
        $this->addSql('UPDATE template SET Archived=0');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' != $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE account DROP FOREIGN KEY FK_7D3656A44239E22F');
        $this->addSql('ALTER TABLE account ADD CONSTRAINT FK_7D3656A44239E22F FOREIGN KEY (accessgroup_id) REFERENCES access_group (id) ON UPDATE CASCADE');
        $this->addSql('ALTER TABLE auto_code_search DROP FOREIGN KEY FK_7A17F7C54D6C90FA');
        $this->addSql('ALTER TABLE auto_code_search ADD CONSTRAINT FK_7A17F7C54D6C90FA FOREIGN KEY (budget_account_id) REFERENCES budget_account (id) ON UPDATE CASCADE');
        $this->addSql('ALTER TABLE budget_account DROP FOREIGN KEY FK_BFB51C456D1D133');
        $this->addSql('ALTER TABLE budget_account ADD CONSTRAINT FK_BFB51C456D1D133 FOREIGN KEY (budget_group) REFERENCES budget_group (id) ON UPDATE CASCADE');
        $this->addSql('ALTER TABLE budget_group DROP FOREIGN KEY FK_6D1D1334239E22F');
        $this->addSql('ALTER TABLE budget_group ADD CONSTRAINT FK_6D1D1334239E22F FOREIGN KEY (accessgroup_id) REFERENCES access_group (id) ON UPDATE CASCADE');
        $this->addSql('ALTER TABLE budget_transaction DROP FOREIGN KEY FK_43D438E2FC0CB0F');
        $this->addSql('ALTER TABLE budget_transaction DROP FOREIGN KEY FK_43D438E4D6C90FA');
        $this->addSql('ALTER TABLE budget_transaction ADD CONSTRAINT FK_43D438E2FC0CB0F FOREIGN KEY (transaction_id) REFERENCES transaction (id) ON UPDATE CASCADE');
        $this->addSql('ALTER TABLE budget_transaction ADD CONSTRAINT FK_43D438E4D6C90FA FOREIGN KEY (budget_account_id) REFERENCES budget_account (id) ON UPDATE CASCADE');
        $this->addSql('ALTER TABLE template DROP Archived');
        $this->addSql('ALTER TABLE template_transaction DROP FOREIGN KEY FK_79CE11B54D6C90FA');
        $this->addSql('ALTER TABLE template_transaction DROP FOREIGN KEY FK_79CE11B55DA0FB8');
        $this->addSql('ALTER TABLE template_transaction ADD CONSTRAINT FK_79CE11B54D6C90FA FOREIGN KEY (budget_account_id) REFERENCES budget_account (id) ON UPDATE CASCADE');
        $this->addSql('ALTER TABLE template_transaction ADD CONSTRAINT FK_79CE11B55DA0FB8 FOREIGN KEY (template_id) REFERENCES template (id) ON UPDATE CASCADE');
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D19B6B5FBA');
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D1B6A263D9');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D19B6B5FBA FOREIGN KEY (account_id) REFERENCES account (id) ON UPDATE CASCADE');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D1B6A263D9 FOREIGN KEY (import_id) REFERENCES import (id) ON UPDATE CASCADE');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D6494239E22F');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6494239E22F FOREIGN KEY (accessgroup_id) REFERENCES access_group (id) ON UPDATE CASCADE');
    }
}
