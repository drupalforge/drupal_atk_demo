<?php

namespace Drupal\pl_drupal_forge\Service;

use Aws\CloudWatchLogs\CloudWatchLogsClient;
use Aws\Lambda\LambdaClient;
use Drupal\pl_drupal_forge\Helper\Helper;
use Psr\Log\LoggerInterface;

class AtkClientService {
  protected \Drupal\Core\Config\ImmutableConfig $config;
  protected LambdaClient $lambdaClient;
  protected CloudWatchLogsClient $logsClient;
  protected LoggerInterface $logger;

  public function __construct() {
    // There can  be two situations here.
    // 1. We just installed the module, in this case $config is not
    //    available and the service will be initialized in hook_install().
    // 2. We started the service when the module is installed, in this
    //    case initialize it here.
    $this->logger = \Drupal::logger('pl_drupal_forge');
    $config = \Drupal::config('pl_drupal_forge.settings');
    if ($config->get()) {
      $this->init();
    }
    else {
      $this->logger->notice('Postpone initialization because config is not ready yet.');
    }
  }

  /**
   * @return void
   */
  public function init(): void {
    $this->config = \Drupal::config('pl_drupal_forge.settings');
    $options = $this->config->get('credentials') ? [
      'credentials' => $this->config->get('credentials'),
      'region' => $this->config->get('region'),
    ] : [
      'profile' => $this->config->get('profile'),
      'region' => $this->config->get('region'),
    ];

    $this->lambdaClient = LambdaClient::factory($options);
    $this->logsClient = CloudWatchLogsClient::factory($options);
  }

  public function invokeFunction(array $payload): void {
    $executionId = \Drupal::service('uuid')->generate();
    $payload['uuid'] = $executionId;
    $payload['drushCmd'] = $this->config->get('drushCmd');
    $payload['targetSite'] = $this->config->get('targetSite');
    $payload['workers'] = $this->config->get('workers');
    $function = $this->config->get('function');
    $this->logger->info('Invoke @function with payload: @payload', ['@function' => $function, '@payload' => $payload]);
    $result = $this->lambdaClient->invoke([
      'FunctionName' => $function,
      'InvocationType' => 'Event',
      'Payload' => json_encode($payload),
    ]);
    // Debug, debug, debug...
    $this->logger->debug('Lambda response: @result', ['@result' => $result]);

    // Store execution ID in session.
    $session = \Drupal::request()->getSession();
    $session->set('executionId', $executionId);
    $session->set('startTime', Helper::timestamp());
    $this->logger->info('Saved executionId: @executionId', ['@executionId' => $executionId]);
  }

  public function fetchLogs(int|null $lastTimestamp):array {
    // Get execution ID from the session.
    $session = \Drupal::request()->getSession();
    $executionId = $session->get('executionId');
    $startTime = $session->get('startTime');
    $this->logger->info('Pulled executionId: @executionId', ['@executionId' => $executionId]);

    // Get the previous log events' ID
    // as an associative array with key equal ID.
    $eventIdSet = $session->get('eventIdSet', []);

    if (!isset($executionId)) {
      return [
        'timestamp' => $lastTimestamp,
        'logs' => [],
        'status' => 'idle',
      ];
    }
    $logGroupName = $this->config->get('cloudWatch.group');
    $timeout = $this->config->get('cloudWatch.timeout');
    try {
      $result = $this->logsClient->getLogEvents([
        'logGroupName' => $logGroupName,
        'logStreamName' => $executionId,
        'startTime' => $lastTimestamp ? $lastTimestamp - 500 : 0,
      ]);
    } catch (\Aws\CloudWatchLogs\Exception\CloudWatchLogsException $exception) {
      $timestamp = Helper::timestamp();
      $diff = $timestamp - $startTime;
      $this->logger->warning("Logs are not ready within {$diff}ms, exception is {$exception->getMessage()}");
      // If timeout isn't exceeded yet, return "running", else "timeout".
      if ($diff < $timeout) {
        return [
          'timestamp' => $lastTimestamp,
          'logs' => [],
          'status' => 'running',
        ];
      }
      return [
        'timestamp' => $lastTimestamp,
        'logs' => [[
          'timestamp' => $timestamp,
          'message' => "Logs are not ready within {$diff}ms",
        ]],
        'status' => 'timeout',
      ];
    }
    // Debug, debug, debug...
    $this->logger->debug('Lambda response: @result', ['@result' => $result]);

    // Get the events from the response.
    $events = $result['events'];

    // Event doesn't have a unique ID, so to check duplicates, let use hash of timestamp and message.
    $id = fn($event) => md5((string)$event['timestamp'] . $event['message']);

    // Filter out duplicates.
    if ($lastTimestamp) {
      $events = array_filter($events, fn($event) => !array_key_exists($id($event), $eventIdSet));
    }

    // Update eventIdSet.
    foreach ($events as $event) {
      $eventIdSet[$id($event)] = true;
    }
    $session->set('eventIdSet', $eventIdSet);

    // Sort events by timestamp.
    // usort($events, fn($event1, $event2) => $event1['timestamp'] <=> $event2['timestamp']);

    // The last timestamp is now the last in the array.
    if (count($events) > 0) {
      $lastTimestamp = end($events)['timestamp'];
    }

    // Check if execution is ended by log entry
    // since there is no way to get response of the asynchronous call.
    $executionEnded = false;
    foreach ($events as $event) {
      if (str_contains($event['message'], "END Execution:")) {
        $executionEnded = true;
      }
      // The rest of the log line must be a JSON-serialized function response.
      // Parse it and add to our response.
      $functionResponse = json_decode(str_replace('END Execution:', '', $event['message']), true);
    }
    if ($executionEnded) {
      $session->remove('executionId');
      $session->remove('startTime');
      $session->remove('eventIdSet');
    }

    $logs = array_values(array_map(fn($event) => ['timestamp' => $event['timestamp'], 'message' => $event['message']], $events));
    $response = [
      'timestamp' => $lastTimestamp,
      'logs' => $logs,
      'status' => $executionEnded ? 'ended' : 'running',
    ];
    if (isset($functionResponse) && is_array($functionResponse)) {
      $response = array_merge($functionResponse, $response);
    }
    return $response;
  }

}
