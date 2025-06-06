<?php

namespace Drupal\pl_drupal_forge\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Component\Utility\Random;

/**
 * Service for executing and managing background scripts.
 */
class LocalRunService implements RunService
{

  /**
   * The session.
   *
   * @var SessionInterface
   */
  protected $session;

  /**
   * The current user.
   *
   * @var AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The file system service.
   *
   * @var FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The config factory.
   *
   * @var ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The maximum time (in ms) a script is allowed to run before being killed.
   *
   * @var int
   */
  protected $scriptTimeout;

  /**
   * Constructs a new ScriptExecutorService object.
   *
   * @param SessionInterface $session
   * The session.
   * @param AccountProxyInterface $currentUser
   * The current user.
   * @param FileSystemInterface $fileSystem
   * The file system service.
   */
  public function __construct(SessionInterface $session, AccountProxyInterface $currentUser, FileSystemInterface $fileSystem, ConfigFactoryInterface $configFactory)
  {
    $this->session = $session;
    $this->currentUser = $currentUser;
    $this->fileSystem = $fileSystem;
    $this->configFactory = $configFactory;

    // Read scriptTimeout.
    $config = $this->configFactory->get('pl_drupal_forge.settings');
    $this->scriptTimeout = $config->get('scriptTimeout') ?? 0;
  }

  /**
   * Starts the execution of the defined script.
   *
   * The script to execute is scripts/run-playwright.sh.
   *
   * @return void
   * An associative array with 'status', 'message', and optionally 'pid'.
   */
  public function startExecution(array $payload): void
  {
    $scriptPath = DRUPAL_ROOT . '/../scripts/run-playwright.sh';
    $currentUserId = $this->currentUser->id();
    $scriptKey = 'pl_drupal_forge_script_execution_' . $currentUserId;

    // Check if a script is already running for this user.
    $scriptData = $this->session->get($scriptKey);
    if (!empty($scriptData) && $this->isScriptRunning($scriptData['pid'])) {
      throw new \RuntimeException('A script is already running for this user. Please wait for it to finish or check its status.');
    }

    // Ensure the script exists and is executable.
    if (!file_exists($scriptPath) || !is_executable($scriptPath)) {
      throw new \RuntimeException('The script ' . basename($scriptPath) . ' was not found or is not executable.');
    }

    // Generate unique temporary file names to avoid conflicts.
    $random = new Random();
    $uniqueId = $random->string(10, TRUE, TRUE, TRUE); // Alphanumeric
    $outputFile = $this->fileSystem->getTempDirectory() . '/my_awesome_script_output_' . $currentUserId . '_' . $uniqueId . '.log';

    // Command to run the script in the background and redirect output.
    // ' > ' redirects stdout, ' 2> ' redirects stderr.
    // ' & ' runs the command in the background.
    // 'echo $!' captures the PID of the last background process.
    $command = escapeshellcmd($scriptPath);
    $command = $command . " " . escapeshellarg($payload['url'] ?? 'http://localhost/');
    if (array_key_exists('grep', $payload)) {
      $command = $command . " " . escapeshellarg($payload['grep']);
    }
    $command = $command . " > " . escapeshellarg($outputFile) . " 2>&1 & echo $!";

    $process = proc_open($command, [
        0 => ['pipe', 'r'], // stdin
        1 => ['pipe', 'w'], // stdout (to capture the PID)
        2 => ['pipe', 'w']  // stderr
    ], $pipes);

    if (is_resource($process)) {
      $pid = (int)trim(stream_get_contents($pipes[1])); // Read the PID
      // Close pipes immediately after getting PID to prevent resource leaks.
      fclose($pipes[0]);
      fclose($pipes[1]);
      fclose($pipes[2]);

      $this->session->set($scriptKey, [
          'pid' => $pid,
          'output_file' => $outputFile,
          'error_file' => $errorFile,
          'started' => \Drupal::time()->getRequestTime(),
      ]);
    } else {
      throw new \RuntimeException('Failed to start the script.');
    }
  }

  /**
   * Returns the output and status of the currently running script for the user.
   *
   * @param int|null $lastTimestamp
   * @return array
   * An associative array with 'timestamp', 'status', 'logs', 'message',
   * and 'resultUri'.
   */
  public function fetchLogs(int|null $lastTimestamp): array
  {
    $currentUserId = $this->currentUser->id();
    $scriptKey = 'pl_drupal_forge_script_execution_' . $currentUserId;

    if (!$this->session->has($scriptKey)) {
      return [
          'status' => 'idle',
          'logs' => [],
      ];
    }

    $scriptData = $this->session->get($scriptKey);
    $pid = $scriptData['pid'];
    $outputFile = $scriptData['output_file'];
    $errorFile = $scriptData['error_file'];
    // Get script start time
    $startedTimestamp = $scriptData['started'];
    $lastReadPositionOutput = !(bool)$lastTimestamp ? 0 : $scriptData['last_read_position_output'] ?? 0;

    $isRunning = $this->isScriptRunning($pid);
    $isTimedOut = false;
    $newLogs = [];

    // Check for timeout ONLY if the script is still considered running by the OS.
    if ($isRunning && $this->scriptTimeout > 0) {
      $currentTime = \Drupal::time()->getRequestTime();
      $elapsedTime = $currentTime - $startedTimestamp;

      if ($elapsedTime > $this->scriptTimeout) {
        // Time to kill the script!
        $this->killProcess($pid);
        $isRunning = false; // It's no longer running
        // Set status to timeout
        $isTimedOut = true;

        // Log the timeout for debugging/monitoring.
        \Drupal::logger('pl_drupal_forge')->warning('Script process @pid timed out after @timeout seconds.', [
            '@pid' => $pid,
            '@timeout' => $this->scriptTimeout,
        ]);
      }
    }

    // Get the current modification time of the log files.
    // This will serve as the timestamp for all messages read in this call.
    $outputFileMtime = file_exists($outputFile) ? filemtime($outputFile) * 1000 : 0; // Epoch millis

    // Read new output lines
    if (file_exists($outputFile)) {
      $fileHandle = fopen($outputFile, 'r');
      if ($fileHandle) {
        fseek($fileHandle, $lastReadPositionOutput);
        while (!feof($fileHandle)) {
          $line = fgets($fileHandle);
          if ($line === false) { // Reached end or error
            break;
          }
          $line = rtrim($line, "\r\n"); // Remove newline characters
          if (!empty($line)) {
            $newLogs[] = [
                'timestamp' => $outputFileMtime,
                'message' => $line,
            ];
          }
        }
        $newReadPositionOutput = ftell($fileHandle);
        fclose($fileHandle);
        $scriptData['last_read_position_output'] = $newReadPositionOutput;
      }
    }

    // Sort logs by timestamp if you want them strictly ordered, though
    // sequential reading usually keeps them ordered by appearance.
    // ksort($newLogs); // Not needed if appending correctly

    if (!$isRunning) {
      // Clean up session data and temporary files once the script finishes.
      $this->session->remove($scriptKey);
      if (file_exists($outputFile)) {
        $this->fileSystem->delete($outputFile);
      }
      if (file_exists($errorFile)) {
        $this->fileSystem->delete($errorFile);
      }
    } else {
      // Update session with new read positions only if still running
      $this->session->set($scriptKey, $scriptData);
    }

    $response = [
        'timestamp' => $outputFileMtime,
        'status' => $isTimedOut ? 'timeout' : ($isRunning ? 'running' : 'ended'),
        'logs' => $newLogs,
    ];
    if ($isTimedOut) {
      $response['message'] = "Script timeout: {$this->scriptTimeout}ms";
    } elseif (!$isRunning) {
      $response['message'] = 'Test execution finished.';
      $response['resultUri'] = '/sites/default/files/playwright-report/index.html?t=' . $outputFileMtime;
    }
    return $response;
  }

  /**
   * Checks if a process with the given PID is still running.
   *
   * @param int $pid
   * The process ID.
   *
   * @return bool
   * TRUE if the process is running, FALSE otherwise.
   */
  protected function isScriptRunning(int $pid): bool
  {
    // For Unix-like systems, check if the process exists.
    // 'ps -p <pid> -o comm=' will return the command name if the process exists.
    // On Windows, 'tasklist /FI "PID eq <pid>"' can be used.
    if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
      // Windows
      $command = 'tasklist /FI "PID eq ' . $pid . '"';
      exec($command, $output);
      // tasklist output has headers, usually 3 lines before any process data.
      return count($output) > 3;
    } else {
      // Unix-like
      $command = 'ps -p ' . $pid . ' -o comm=';
      exec($command, $output);
      return !empty($output);
    }
  }

  /**
   * Kills a process by its PID.
   *
   * @param int $pid
   * The process ID to kill.
   *
   * @return bool
   * TRUE if the kill command was executed, FALSE otherwise.
   */
  protected function killProcess(int $pid): bool {
    if ($pid <= 0) {
      return FALSE;
    }

    if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
      // Windows: taskkill /PID <pid> /F (Forcefully terminate)
      $command = 'taskkill /PID ' . (int) $pid . ' /F 2> NUL';
      exec($command, $output, $returnVar);
      return $returnVar === 0;
    } else {
      // Unix-like: kill -SIGTERM <pid> (Graceful termination signal)
      // For more forceful, use SIGKILL (9) after SIGTERM if SIGTERM fails.
      return posix_kill($pid, SIGTERM);
    }
  }

}
