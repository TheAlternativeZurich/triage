<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210224190029 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_E7927C74A45BB98C');
        $this->addSql('CREATE TEMPORARY TABLE __temp__email AS SELECT id, sent_by_id, identifier, type, link, sent_date_time, read_at FROM email');
        $this->addSql('DROP TABLE email');
        $this->addSql('CREATE TABLE email (id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:guid)
        , sent_by_id CHAR(36) DEFAULT NULL COLLATE BINARY --(DC2Type:guid)
        , identifier CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:guid)
        , type INTEGER NOT NULL, link CLOB DEFAULT NULL COLLATE BINARY, sent_date_time DATETIME NOT NULL, read_at DATETIME DEFAULT NULL, body CLOB DEFAULT NULL, PRIMARY KEY(id), CONSTRAINT FK_E7927C74A45BB98C FOREIGN KEY (sent_by_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO email (id, sent_by_id, identifier, type, link, sent_date_time, read_at) SELECT id, sent_by_id, identifier, type, link, sent_date_time, read_at FROM __temp__email');
        $this->addSql('DROP TABLE __temp__email');
        $this->addSql('CREATE INDEX IDX_E7927C74A45BB98C ON email (sent_by_id)');
        $this->addSql('DROP INDEX IDX_3BAE0AA7BA2D8762');
        $this->addSql('DROP INDEX UNIQ_3BAE0AA7772E836A');
        $this->addSql('CREATE TEMPORARY TABLE __temp__event AS SELECT id, lecturer_id, identifier, experience, min_registrations, public, created_at, last_changed_at, title, description, start_date, parts, public_notification_sent, sufficient_registrations_notification_sent, author FROM event');
        $this->addSql('DROP TABLE event');
        $this->addSql('CREATE TABLE event (id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:guid)
        , lecturer_id CHAR(36) DEFAULT NULL COLLATE BINARY --(DC2Type:guid)
        , identifier VARCHAR(255) NOT NULL COLLATE BINARY, experience CLOB NOT NULL COLLATE BINARY, min_registrations INTEGER NOT NULL, public BOOLEAN NOT NULL, created_at DATETIME NOT NULL, last_changed_at DATETIME NOT NULL, title CLOB NOT NULL COLLATE BINARY, description CLOB NOT NULL COLLATE BINARY, start_date DATETIME NOT NULL, parts INTEGER DEFAULT NULL, public_notification_sent DATETIME DEFAULT NULL, sufficient_registrations_notification_sent DATETIME DEFAULT NULL, author CLOB DEFAULT NULL COLLATE BINARY, PRIMARY KEY(id), CONSTRAINT FK_3BAE0AA7BA2D8762 FOREIGN KEY (lecturer_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO event (id, lecturer_id, identifier, experience, min_registrations, public, created_at, last_changed_at, title, description, start_date, parts, public_notification_sent, sufficient_registrations_notification_sent, author) SELECT id, lecturer_id, identifier, experience, min_registrations, public, created_at, last_changed_at, title, description, start_date, parts, public_notification_sent, sufficient_registrations_notification_sent, author FROM __temp__event');
        $this->addSql('DROP TABLE __temp__event');
        $this->addSql('CREATE INDEX IDX_3BAE0AA7BA2D8762 ON event (lecturer_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3BAE0AA7772E836A ON event (identifier)');
        $this->addSql('DROP INDEX IDX_62A8A7A7A76ED395');
        $this->addSql('DROP INDEX IDX_62A8A7A771F7E88B');
        $this->addSql('CREATE TEMPORARY TABLE __temp__registration AS SELECT id, event_id, user_id, created_at, last_changed_at FROM registration');
        $this->addSql('DROP TABLE registration');
        $this->addSql('CREATE TABLE registration (id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:guid)
        , event_id CHAR(36) DEFAULT NULL COLLATE BINARY --(DC2Type:guid)
        , user_id CHAR(36) DEFAULT NULL COLLATE BINARY --(DC2Type:guid)
        , created_at DATETIME NOT NULL, last_changed_at DATETIME NOT NULL, PRIMARY KEY(id), CONSTRAINT FK_62A8A7A771F7E88B FOREIGN KEY (event_id) REFERENCES event (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_62A8A7A7A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO registration (id, event_id, user_id, created_at, last_changed_at) SELECT id, event_id, user_id, created_at, last_changed_at FROM __temp__registration');
        $this->addSql('DROP TABLE __temp__registration');
        $this->addSql('CREATE INDEX IDX_62A8A7A7A76ED395 ON registration (user_id)');
        $this->addSql('CREATE INDEX IDX_62A8A7A771F7E88B ON registration (event_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_E7927C74A45BB98C');
        $this->addSql('CREATE TEMPORARY TABLE __temp__email AS SELECT id, sent_by_id, identifier, type, link, sent_date_time, read_at FROM email');
        $this->addSql('DROP TABLE email');
        $this->addSql('CREATE TABLE email (id CHAR(36) NOT NULL --(DC2Type:guid)
        , sent_by_id CHAR(36) DEFAULT NULL --(DC2Type:guid)
        , identifier CHAR(36) NOT NULL --(DC2Type:guid)
        , type INTEGER NOT NULL, link CLOB DEFAULT NULL, sent_date_time DATETIME NOT NULL, read_at DATETIME DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO email (id, sent_by_id, identifier, type, link, sent_date_time, read_at) SELECT id, sent_by_id, identifier, type, link, sent_date_time, read_at FROM __temp__email');
        $this->addSql('DROP TABLE __temp__email');
        $this->addSql('CREATE INDEX IDX_E7927C74A45BB98C ON email (sent_by_id)');
        $this->addSql('DROP INDEX UNIQ_3BAE0AA7772E836A');
        $this->addSql('DROP INDEX IDX_3BAE0AA7BA2D8762');
        $this->addSql('CREATE TEMPORARY TABLE __temp__event AS SELECT id, lecturer_id, identifier, experience, min_registrations, public, public_notification_sent, sufficient_registrations_notification_sent, created_at, last_changed_at, title, description, start_date, parts, author FROM event');
        $this->addSql('DROP TABLE event');
        $this->addSql('CREATE TABLE event (id CHAR(36) NOT NULL --(DC2Type:guid)
        , lecturer_id CHAR(36) DEFAULT NULL --(DC2Type:guid)
        , identifier VARCHAR(255) NOT NULL, experience CLOB NOT NULL, min_registrations INTEGER NOT NULL, public BOOLEAN NOT NULL, public_notification_sent DATETIME DEFAULT NULL, sufficient_registrations_notification_sent DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, last_changed_at DATETIME NOT NULL, title CLOB NOT NULL, description CLOB NOT NULL, start_date DATETIME NOT NULL, parts INTEGER DEFAULT NULL, author CLOB DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO event (id, lecturer_id, identifier, experience, min_registrations, public, public_notification_sent, sufficient_registrations_notification_sent, created_at, last_changed_at, title, description, start_date, parts, author) SELECT id, lecturer_id, identifier, experience, min_registrations, public, public_notification_sent, sufficient_registrations_notification_sent, created_at, last_changed_at, title, description, start_date, parts, author FROM __temp__event');
        $this->addSql('DROP TABLE __temp__event');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3BAE0AA7772E836A ON event (identifier)');
        $this->addSql('CREATE INDEX IDX_3BAE0AA7BA2D8762 ON event (lecturer_id)');
        $this->addSql('DROP INDEX IDX_62A8A7A771F7E88B');
        $this->addSql('DROP INDEX IDX_62A8A7A7A76ED395');
        $this->addSql('CREATE TEMPORARY TABLE __temp__registration AS SELECT id, event_id, user_id, created_at, last_changed_at FROM registration');
        $this->addSql('DROP TABLE registration');
        $this->addSql('CREATE TABLE registration (id CHAR(36) NOT NULL --(DC2Type:guid)
        , event_id CHAR(36) DEFAULT NULL --(DC2Type:guid)
        , user_id CHAR(36) DEFAULT NULL --(DC2Type:guid)
        , created_at DATETIME NOT NULL, last_changed_at DATETIME NOT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO registration (id, event_id, user_id, created_at, last_changed_at) SELECT id, event_id, user_id, created_at, last_changed_at FROM __temp__registration');
        $this->addSql('DROP TABLE __temp__registration');
        $this->addSql('CREATE INDEX IDX_62A8A7A771F7E88B ON registration (event_id)');
        $this->addSql('CREATE INDEX IDX_62A8A7A7A76ED395 ON registration (user_id)');
    }
}
