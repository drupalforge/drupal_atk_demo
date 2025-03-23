<?php

namespace Drupal\pl_drupal_forge\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

class ResourceBase1 extends ResourceBase {
  static function response(mixed $data): ResourceResponse {
    $shutdownCache = ['#cache' => ['max-age' => 0]];
    return (new ResourceResponse($data))->addCacheableDependency($shutdownCache);
  }
}
