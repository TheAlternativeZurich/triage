# Developers

## About the setup

This project uses the symfony framework for its php backend, and minimal JavaScript dependencies for the frontend.

backend:
- `symfony/sceleton`, `orm`, `translations`, `logging`, `dotenv`, `mailer`, `form`, `requirements-checker` and `templates`.
- dev dependencies include `encore`, `orm-fixtures`, `cs-fixer`, `security-checker`, `debug`, `simple-phpunit`, `brainmaestro/composer-git-hooks`.
- for deployment, `famoser/agnes` is used.

frontend:
- `bootrap` as UI framework
- `fontawesome` for the icons (you will need a license key)

## Developing

setup:
- `composer install` to install backend dependencies  
- `yarn install` to install frontend dependencies  

developing:
- `symfony start` to start the symfony server  
- `yarn run dev-server` to start the frontend server
- visit `localhost:8000` and login with `f@thealternative.ch`, `asdf`  
- test error templates inside TwigBundle/views by accessing `/_error/404` and `/_error/500`

clean up:
- `vendor/bin/php-cs-fixer fix` to autoformat code
- `bin/phpunit` to execute tests

database commands:
- `doctrine:migrations:diff` to generate a new migration class  
- `doctrine:migrations:migrate` to execute all migrations  
- `doctrine:fixtures:load` to load fixtures

deployment:
- `vendor/bin/agnes release` to create a new release
- `vendor/bin/agnes deploy` to deploy a release or commitish  

## Troubleshooting

### fortawesome

to be able to execute `yarn install`, you need to download the fortawesome pro fonts. 
For this, you need to setup its registry. You can do so by executing  
`npm config set "@fortawesome:registry" https://npm.fontawesome.com/`  
`npm config set "//npm.fontawesome.com/:_authToken" AUTH_TOKEN`  
You can find the auth token on the [fontawesome account page](https://fontawesome.com/account)

You need to add this auth token to all CI services too.  
[**scrutinizer**](https://scrutinizer-ci.com/g/mangelio/app/settings/build-config)
```yaml
build:
    dependencies:
        before:
            - npm config set "@fortawesome:registry" https://npm.fontawesome.com/
            - npm config set "//npm.fontawesome.com/:_authToken" FONTAWESOME_AUTH_TOKEN
```
