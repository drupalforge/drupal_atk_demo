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
Add the AWS credentials to .aws/credentials:

```
[performantlabs]
aws_access_key_id = [XXX]
aws_secret_access_key = [xxx]
```

See [pl_drupal_forge.settings.yml](web/modules/pl_drupal_forge/config/install/pl_drupal_forge.settings.yml).
AWS shares the credentials above (via [docker-compose.aws.yml](.ddev/docker-compose.aws.yml)).

## Usage
Open "Test Me!" page and follow the instructions.

## Recipe

In order tests to pass, Drupal installation must have
`drupal/automated_testing_kit_demo_recipe` recipe applied.

```shell
ddev composer config allow-plugins.ewcomposer/unpack true
ddev composer config repo.recipe-unpack vcs https://github.com/woredeyonas/Drupal-Recipe-Unpack.git
ddev composer require ewcomposer/unpack:dev-master
ddev composer require drupal/automated_testing_kit_demo_recipe
ddev drush cr
ddev composer unpack drupal/automated_testing_kit_demo_recipe
ddev drush recipe ../recipes/automated_testing_kit_demo_recipe -v
```

## Exposing local installation to the Internet

To run tests against the local Drupal installation, set up
[ngrok](https://ngrok.com/docs/getting-started/?os=linux).
Then run:
```shell
ddev share
```
