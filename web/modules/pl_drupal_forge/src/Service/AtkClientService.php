<?php

namespace Drupal\pl_drupal_forge\Service;

use Aws\Lambda\LambdaClient;

class AtkClientService {
  protected LambdaClient $client;

  public function __construct(string $region) {
    $this->client = LambdaClient::factory([
      'profile' => 'performantlabs',
      'region' => $region,
    ]);
  }

  public function invokeTest() {

  }
}
