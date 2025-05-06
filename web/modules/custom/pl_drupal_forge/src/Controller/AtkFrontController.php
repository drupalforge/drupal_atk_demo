<?php
namespace Drupal\pl_drupal_forge\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Site front page controller.
 */
class AtkFrontController extends ControllerBase {

  /**
   * Returns an empty front page so that we don't have to define it as content in the database.
   *
   * @return array
   *   A simple renderable array.
   */
  public function front() {
    return [];
  }
}
