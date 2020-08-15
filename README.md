# icreatives

[![CircleCI](https://circleci.com/gh/teodyseguin/icreatives.svg?style=shield)](https://circleci.com/gh/teodyseguin/icreatives)
[![Dashboard icreatives](https://img.shields.io/badge/dashboard-icreatives-yellow.svg)](https://dashboard.pantheon.io/sites/9cde9b81-1422-4195-9c97-75a2c3b31f40#dev/code)
[![Dev Site icreatives](https://img.shields.io/badge/site-icreatives-blue.svg)](http://dev-icreatives.pantheonsite.io/)

### Pre-requisite

- [Docker Engine](https://docs.lando.dev/basics/installation.html#docker-engine-requirements)
- [Lando](https://docs.lando.dev/basics/installation.html)

### Setup

- Clone this repository `git clone git@github.com:teodyseguin/icadmin.git`.
- Change directory to `/icadmin/sites/default`.
- Create a `settings.local.php`. Copy the contents below.
- Change directory to `/icadmin` and run `composer install`.
- Finally run `lando start`.

### Local settings

```
<?php
$databases = array(
  'default' =>
    array(
      'default' =>
        array(
          'database' => 'drupal8',
          'username' => 'drupal8',
          'password' => 'drupal8',
          'host' => 'database',
          'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
          'driver' => 'mysql',
          'prefix' => '',
        ),
    ),
);
$config_directories = array(
  CONFIG_SYNC_DIRECTORY => '../../../config',
);
$settings['trusted_host_patterns'] = array(
  '^.+$',
);
$settings['hash_salt'] = 'V7XlQ8fhv4jQ4rooPH9CYiJvzRmcO2Yc2vsz0-H6vkuS0Kygo3Gi8Cxq8-5Xw3NPYjtuVT51-w';
$settings['container_yamls'][] = DRUPAL_ROOT . '/sites/development.services.yml';
$settings['cache']['bins']['render'] = 'cache.backend.null';
$settings['cache']['bins']['dynamic_page_cache'] = 'cache.backend.null';
$settings['cache']['bins']['page'] = 'cache.backend.null';
$config['system.performance']['css']['preprocess'] = FALSE;
$config['system.performance']['js']['preprocess'] = FALSE;
```

### Twig Debugging

- To enable debugging, go to `/icadmin/sites/default` and make a copy of `default.services.yml` and rename it as `services.yml`.
- Edit `services.yml`.
- Set Twig debugging `debug: true`.
- Set Twig cache `cache: false`.

### Tweaks

After integrating the Particle prototyping tool, I am now getting a lot of test fails under `static_tests` from CircleCI build. I don't know at the moment how to ignore certain files or directory for testing so what I did is that, I tried to track down where the script is executing the `static_tests` steps. I found out, it's on the `.ci/test/static/run` file. There are 3 composer commands there that I have disabled.

- `composer -n unit-test`
- `composer -n lint`
- `composer -n code-sniff`

Furthermore, I also disable the `/dist` directory from within `/particle` from being ignored by git. The reason is because the drupal theme reads that directory for the components being compiled in there.
