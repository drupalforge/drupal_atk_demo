<?php

namespace Drupal\pl_drupal_forge\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use GuzzleHttp\Client;


class AtkClientController extends ControllerBase {

  protected $httpClient;

  public function __construct(Client $http_client) {
    $this->httpClient = $http_client;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client')
    );
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
    ];
  }

  public function fetchLambdaOutput() {
    $url = 'https://your-api-gateway-url.amazonaws.com/prod/lambda-endpoint';

    try {
      $response = $this->httpClient->request('GET', $url);
      $data = json_decode($response->getBody()->getContents(), TRUE);
      return [
        '#theme' => 'lambda_output',
        '#output' => $data,
      ];
    } catch (\Exception $e) {
      \Drupal::logger('lambda_output')->error($e->getMessage());
      return ['#markup' => 'Error fetching Lambda output.'];
    }
  }

  protected function getBaseUrl() {
    return \Drupal::request()->getSchemeAndHttpHost() . '/';
  }
}
