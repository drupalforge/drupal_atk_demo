# drupal-forge

## Installation

```shell
ddev start
ddev composer install
ddev drush site:install --account-name=admin --account-pass=password -y
ddev drush en pl_drupal_forge -y
ddev launch
```

## Configuration
See [pl_drupal_forge.settings.yml](web/modules/pl_drupal_forge/config/install/pl_drupal_forge.settings.yml).
AWS shares host configuration (by [docker-compose.aws.yml](.ddev/docker-compose.aws.yml)).

## Usage
Open "Test Me!" page and follow the instructions.
