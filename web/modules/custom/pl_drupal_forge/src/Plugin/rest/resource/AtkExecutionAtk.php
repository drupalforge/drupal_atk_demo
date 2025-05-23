<?php

namespace Drupal\pl_drupal_forge\Plugin\rest\resource;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\rest\Attribute\RestResource;
use Drupal\rest\ResourceResponse;
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
class AtkExecutionAtk extends AtkBaseResource
{
  /**
   * {@inheritdoc}
   * @param ContainerInterface $container
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    return new static(
        $container,
        $configuration,
        $plugin_id,
        $plugin_definition,
    );
  }

  /**
   * Invoke the function.
   *
   * @return ResourceResponse
   */
  public function post()
  {
    $payload = json_decode(\Drupal::request()->getContent(), true);
    $this->runService->startExecution($payload);
    // Response object must be set, even empty, otherwise $.ajax() considers it an error.
    return self::response(['message' => 'OK']);
  }

}
