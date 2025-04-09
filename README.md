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
AWS shares the credentials from ~/.aws (via [docker-compose.aws.yml](.ddev/docker-compose.aws.yml)).
If you don't have AWS configured on the host machine, do the following steps:
 - copy .aws directory to home: `cp -r ./.aws ~`
 - replace `[XXX]` with actual keys

You should have the `credentials` and `config` files in your ~/.aws directory.
Restart DDEV.

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

## Exposing SSH to the Internet
The command above will expose http port only. To expose SSH (which
is needed for Drush commands), do the following.

Authorize your SSH keys to DDEV. Restart.
Authorize ATK Lambda Function's SSH keys (the following command implying that they are
in the same directory).
```shell
ddev auth ssh -d ~/.ssh/
ddev restart
```

Check out SSH port mapping.
```shell
ddev describe
```

You should see a line like this:
```text
- web:22 -> 127.0.0.1:32832
```

Check you can connect to the container. Replace the port and your user.
```shell
ssh -o StrictHostKeyChecking=no -p 32832 -o SetEnv=IS_DDEV_PROJECT=true ilya@localhost
```

Now it's time to connect ngrok.

Create `ngrok.yml` with the following content. Replace authorization token, http and ssh port.
```yaml
version: 2
authtoken: '[xxx]'
tunnels:
  web:
    proto: http
    addr: http://127.0.0.1:32804
  ssh:
    proto: tcp
    addr: 32803
```

Run:
```shell
ngrok start --config ngrok.yml --all
```

You should see two ports forwarding:
```text
Forwarding                    tcp://0.tcp.ngrok.io:10186 -> localhost:32832
Forwarding                    https://17368dc6c817.ngrok.app -> http://127.0.0.1:32833
```

Check that you can connect to the container:
```shell
ssh -o StrictHostKeyChecking=no -p 10186 -o SetEnv=IS_DDEV_PROJECT=true ilya@0.tcp.ngrok.io
```

Now, to run SSH commands from the tests, module settings should be updated.

Check module settings:
```shell
ddev drush cget pl_drupal_forge.settings targetSite
```

If necessary, update host, port, and username. For example
```shell
ddev drush cset pl_drupal_forge.settings targetSite.port 10186
```

Double-check module settings. Now you can go to the Test me! page and run the tests.
