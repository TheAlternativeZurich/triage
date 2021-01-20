<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210120150045 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE email (id CHAR(36) NOT NULL --(DC2Type:guid)
        , sent_by_id CHAR(36) DEFAULT NULL --(DC2Type:guid)
        , identifier CHAR(36) NOT NULL --(DC2Type:guid)
        , type INTEGER NOT NULL, link CLOB DEFAULT NULL, sent_date_time DATETIME NOT NULL, read_at DATETIME DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_E7927C74A45BB98C ON email (sent_by_id)');
        $this->addSql('CREATE TABLE event (id CHAR(36) NOT NULL --(DC2Type:guid)
        , lecturer_id CHAR(36) DEFAULT NULL --(DC2Type:guid)
        , identifier VARCHAR(255) NOT NULL, experience CLOB NOT NULL, min_registrations INTEGER NOT NULL, public BOOLEAN NOT NULL, created_at DATETIME NOT NULL, last_changed_at DATETIME NOT NULL, title CLOB NOT NULL, description CLOB NOT NULL, start_date DATETIME NOT NULL, parts INTEGER DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3BAE0AA7772E836A ON event (identifier)');
        $this->addSql('CREATE INDEX IDX_3BAE0AA7BA2D8762 ON event (lecturer_id)');
        $this->addSql('CREATE TABLE registration (id CHAR(36) NOT NULL --(DC2Type:guid)
        , event_id CHAR(36) DEFAULT NULL --(DC2Type:guid)
        , user_id CHAR(36) DEFAULT NULL --(DC2Type:guid)
        , created_at DATETIME NOT NULL, last_changed_at DATETIME NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_62A8A7A771F7E88B ON registration (event_id)');
        $this->addSql('CREATE INDEX IDX_62A8A7A7A76ED395 ON registration (user_id)');
        $this->addSql('CREATE TABLE user (id CHAR(36) NOT NULL --(DC2Type:guid)
        , is_admin BOOLEAN NOT NULL, created_at DATETIME NOT NULL, last_changed_at DATETIME NOT NULL, email VARCHAR(255) NOT NULL, password CLOB DEFAULT NULL, authentication_hash CLOB DEFAULT NULL, is_enabled BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON user (email)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE email');
        $this->addSql('DROP TABLE event');
        $this->addSql('DROP TABLE registration');
        $this->addSql('DROP TABLE user');
    }
}
