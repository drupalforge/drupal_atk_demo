<?php

namespace Drupal\pl_drupal_forge\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use GuzzleHttp\Client;


class AtkClientController extends ControllerBase {

  public static function create(ContainerInterface $container) {
    return new static();
  }

  /**
   * Start "Test Me" page. Contains the "Press the button".
   * On button press, the Lamnda function is triggered,
   * which runs ATK {@link https://www.drupal.org/project/automated_testing_kit/} tests
   * against this very site, and update the output to the page.
   *
   * @return array A simple renderable array.
   */
  public function start() {
    return [
      '#theme' => 'start_page_template',
      '#base_url' => $this->getBaseUrl(),
      '#tags' => $this->config('pl_drupal_forge.settings')->get('tags'),
    ];
  }

  protected function getBaseUrl() {
    return \Drupal::request()->getSchemeAndHttpHost() . '/';
  }
}
