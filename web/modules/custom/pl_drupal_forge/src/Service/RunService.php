<?php

namespace Drupal\pl_drupal_forge\Service;

interface RunService
{
  public function startExecution(array $payload): void;

  public function fetchLogs(int|null $lastTimestamp): array;
}