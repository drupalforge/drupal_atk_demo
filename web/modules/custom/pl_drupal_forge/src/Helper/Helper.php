<?php

namespace Drupal\pl_drupal_forge\Helper;

/**
 * Some common methods that are missing in PHP.
 */
class Helper {
  public static function timestamp(): int {
    return (int)round(microtime(true) * 1000);
  }

}
