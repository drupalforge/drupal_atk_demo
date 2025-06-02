<?php

namespace Drupal\pl_drupal_forge\Plugin\rest\resource;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\rest\Attribute\RestResource;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

#[RestResource(
    id: 'pl_drupal_forge_logs_atk',
    label: new TranslatableMarkup('Fetch the Lambda Function Logs'),
    uri_paths: [
        'canonical' => '/pl_drupal_forge/logs',
    ],
)]
class AtkLogsAtk extends AtkBaseResource
{
  /**
   * {@inheritdoc}
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
  public function get()
  {
    $timestamp = \Drupal::request()->query->get('timestamp');
    $data = $this->runService->fetchLogs($timestamp);

    return self::response($data);
  }
}
