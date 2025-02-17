<?php

namespace Drupal\pl_drupal_forge\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use GuzzleHttp\Client;


class LambdaOutputController extends ControllerBase {

  protected $httpClient;

  public function __construct(Client $http_client) {
    $this->httpClient = $http_client;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client')
    );
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
}
