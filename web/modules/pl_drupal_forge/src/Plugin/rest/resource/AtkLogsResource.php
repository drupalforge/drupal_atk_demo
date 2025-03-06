<?php

namespace Drupal\pl_drupal_forge\Plugin\rest\resource;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\pl_drupal_forge\Service\AtkClientService;
use Drupal\rest\Attribute\RestResource;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

#[RestResource(
  id: 'pl_drupal_forge_logs',
  label: new TranslatableMarkup('Fetch the Lambda Function Logs'),
  uri_paths: [
    'canonical' => '/pl_drupal_forge/logs',
  ],
)]
class AtkLogsResource extends ResourceBase1 {
  protected AtkClientService $atkClientService;

  public function __construct(
    array $configuration, $plugin_id, $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AtkClientService $atkClientService,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->atkClientService = $atkClientService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('pl_drupal_forge'),
      $container->get('pl_drupal_forge.atk_client'),
    );
  }

  /**
   * Return invocation status and the newest logs, in format:
   * <pre>
   *   {
   *     "status": "<idle|running|ended|timeout>",
   *     "timestamp": <the last log event timestamp, in epoch ms, use this as an input for the next call to get new records>
   *     "logs": [
   *       {
   *         "timestamp": <number>,
   *         "message": "<log message>",
   *       },
   *       ...
   *     ],
   *     // TODO function result if it has ended
   *   }
   * </pre>
   *
   * @return ResourceResponse
   */
  public function get() {
    $timestamp = \Drupal::request()->query->get('timestamp');
    $data = $this->atkClientService->fetchLogs($timestamp);

    return self::response($data);
  }
}
