<?php

namespace Drupal\pl_drupal_forge\Service;

use Aws\CloudWatchLogs\CloudWatchLogsClient;
use Aws\Lambda\LambdaClient;

class AtkClientService {
  protected \Drupal\Core\Config\ImmutableConfig $config;
  protected LambdaClient $lambdaClient;
  protected CloudWatchLogsClient $logsClient;

  /**
   * @return void
   */
  public function init(): void {
    $this->config = \Drupal::config('pl_drupal_forge.settings');
    $options = [
      'profile' => $this->config->get('profile'),
      'region' => $this->config->get('region'),
    ];

    $this->lambdaClient = LambdaClient::factory($options);
    $this->logsClient = CloudWatchLogsClient::factory($options);
  }

  public function invokeFunction(array $payload): void {
    $function = $this->config->get('function');
    $result = $this->lambdaClient->invoke([
      'FunctionName' => $function,
      'InvocationType' => 'Event',
      'InvokeArgs' => json_encode($payload),
    ]);
    // Capture the unique execution ID.
    $executionId = $result['ResponseMetadata']['RequestId'];

    // Store this ID in session.
    $session = \Drupal::request()->getSession();
    $session->set('executionId', $executionId);
  }

  public function fetchLogs(int|null $lastTimestamp):array {
    // Get execution ID from the session.
    $session = \Drupal::request()->getSession();
    $executionId = $session->get('executionId');

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
    $function = $this->config->get('function');
    $logGroupName = "/aws/lambda/$function";
    $result = $this->logsClient->filterLogEvents([
      'logGroupName' => $logGroupName,
      'startTime' => $lastTimestamp ? $lastTimestamp - 500 : 0,
      'filterPattern' => "\"$executionId\"",
    ]);

    // Get the events from the response.
    $events = $result['events'];

    // Filter out duplicates.
    if ($lastTimestamp) {
      $events = array_filter($events, fn($event) => !array_key_exists($event['eventId'], $eventIdSet));
    }

    // Update eventIdSet.
    foreach ($events as $event) {
      $eventIdSet[$event['eventId']] = true;
    }
    $session->set('eventIdSet', $eventIdSet);

    // Sort events by timestamp.
    usort($events, fn($event1, $event2) => $event1['timestamp'] <=> $event2['timestamp']);

    // The last timestamp is now the last in the array.
    if (count($events) > 0) {
      $lastTimestamp = end($events)['timestamp'];
    }

    // Check if execution is ended by log entry
    // since there is no way to get response of the asynchronous call.
    $executionEnded = false;
    foreach ($events as $event) {
      if (str_contains($event['message'], "END RequestId: $executionId")) {
        $executionEnded = true;
      }
    }
    if ($executionEnded) {
      $session->remove('executionId');
      $session->remove('eventIdSet');
    }

    // TODO similar way grab execution response, put to session and add to return value.

    $logs = array_map(fn($event) => ['timestamp' => $event['timestamp'], 'message' => $event['message']], $events);
    return [
      'timestamp' => $lastTimestamp,
      'logs' => $logs,
      'status' => $executionEnded ? 'ended' : 'running',
    ];
  }

}
