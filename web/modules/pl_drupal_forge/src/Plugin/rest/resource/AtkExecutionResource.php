<?php

namespace Drupal\pl_drupal_forge\Plugin\rest\resource;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\pl_drupal_forge\Service\AtkClientService;
use Drupal\rest\Attribute\RestResource;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provide an endpoint to invoke ATK Lambda function.
 */
#[RestResource(
  id: 'pl_drupal_forge_invoke',
  label: new TranslatableMarkup('Invoke ATK Lambda Function'),
  uri_paths: [
    'create' => '/pl_drupal_forge/invoke'
  ],
)]
class AtkExecutionResource extends ResourceBase1 {
  protected AtkClientService $atkClientService;

  public function __construct(
    array $configuration, $plugin_id, $plugin_definition,
    array $serialization_formats,
    LoggerInterface $logger,
    AtkClientService $atkClientService,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serialization_formats, $logger);
    $this->atkClientService = $atkClientService;
  }

  /**
   * {@inheritdoc}
   * @param ContainerInterface $container
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('pl_drupal_forge'),
      $container->get('pl_drupal_forge.atk_client')
    );
  }

  /**
   * Invoke the function.
   *
   * @return ResourceResponse
   */
  public function post() {
    $payload = json_decode(\Drupal::request()->getContent(), true);
    $this->atkClientService->invokeFunction($payload);
    // Response object must be set, even empty, otherwise $.ajax() considers it an error.
    return self::response([]);
  }

}
