# Operations
useful information for operations.

## Installation

The server must fulfil requirements of `composer.json`.

A sample production config `.env.local` could look like this:

```dotenv
APP_ENV=prod
APP_SECRET=NEW_SECRET
MAILER_URL=smtp://info@example.com:NOT_REAL@smtp.mail.com:465/?encryption=ssl&auth_mode=login
```

### Using agnes

Use `famoser/agnes` to ensure folders are setup correctly and migrations are run. It also manages configuration files (see `data.files` section).

You need access to the config repository specified in `agnes.yml`. Then:
- create a new release (here `v1.0`) of `main` branch with `./vendor/bin/agnes release v1.0 main`
- deploy release to `prod` environment with `./vendor/bin/agnes deploy *:*:prod v1.0`

You can also directly deploy from `main` branch with `./vendor/bin/agnes deploy *:*:prod main`.

### Manual installation

To be able to reliably install it you need basic symfony knowledge.

Steps:
- unzip the release in the target directory
- create a .env.local in the target directory to overwrite configuration values of the .env file
- migrate the database (run `composer install` then `php bin/console doctrine:migrations:migrate -n`)
- point the server inside the `public` directory

## Backup

You only need to backup the files in the `/var/persistent` folder (includes the sqlite database too). 

Using rsync, do something like this: 
```
rsync -azP ~/www/triage.thealternaitve.ch/production/shared/var/persistent/ user@IP:~/backup/
```

### Setup ssh

`ssh-copy-id -i ~/.ssh/id_rsa.pub username@domain` to add ssh key  
`cat ~/.ssh/id_rsa.pub` to query the active ssh key  
`ssh-keygen -t rsa -b 4096 -C "username@domain" && eval $(ssh-agent -s) && ssh-add ~/.ssh/id_rsa` generate a new key & add it to ssh  
