<?php

namespace Drupal\pl_drupal_forge\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AtkReportController extends ControllerBase {
  public static function create(ContainerInterface $container) {
    return new static();
  }

  /**
   * @return array A simple renderable array.
   */
  public function report() {
    /**
     * URI of the report in AWS S3.
     */
    $reportUri = \Drupal::request()->query->get('report-uri');
    if (!$reportUri) {
      throw new \InvalidArgumentException('"report-uri" query parameter is missing.');
    }

    // URI must start with the configured prefix (to prevent reading local files etc.).
    $prefix = \Drupal::config('pl_drupal_forge.settings')->get('s3.prefix');
    if (!str_starts_with($reportUri, $prefix)) {
      throw new \InvalidArgumentException("report-uri=$reportUri is not a valid reportURL");
    }

    // Read file contents.
    $jsonReport = \file_get_contents($reportUri);
    if (!$jsonReport) {
      throw new \InvalidArgumentException("Report by report-uri=$reportUri is not readable.");
    }

    $report = json_decode($jsonReport, true);
    if (!$report) {
      throw new \InvalidArgumentException("Report by report-uri=$reportUri cannot be JSON-decoded.");
    }

    $this->preprocess($report, $reportUri);

    return [
      '#theme' => 'report_page_template',
      '#report' => $report,
    ];
  }

  protected function preprocess(array &$report, string $reportUri) {
    // Flatten suites, to be one-level.
    if (array_key_exists('suites', $report)) {
      do {
        $moved = 0;
        $suites = [];
        foreach ($report['suites'] as $suite) {
          if (array_key_exists('specs', $suite) && count($suite['specs']) > 0) {
            $suites[] = $suite;
          } elseif (array_key_exists('suites', $suite) && count($suite['suites']) > 0) {
            foreach ($suite['suites'] as $subsuite) {
              $suites[] = $subsuite;
              $moved++;
            }
          }
        }
        $report['suites'] = $suites;
      } while($moved > 0);
    }

    // Add summary and status to each suite.
    if (array_key_exists('suites', $report)) {
      foreach ($report['suites'] as &$suite) {
        // Status -> count map on a suite level.
        $suiteStat = ['unexpected' => 0, 'expected' => 0];
        if (array_key_exists('specs', $suite)) {
          foreach ($suite['specs'] as &$spec) {
            if (array_key_exists('tests', $spec)) {
              foreach ($spec['tests'] as &$test) {
                if (array_key_exists('status', $test) && array_key_exists($test['status'], $suiteStat)) {
                  $suiteStat[$test['status']]++;
                }
              }
            }
          }
        }
        // Status to class.
        $statusToClass = ['unexpected' => 'failed', 'expected' => 'passed'];
        // Set status.
        $suite['status'] = $suiteStat['unexpected'] > 0 ? 'unexpected' : 'expected';
        $suite['class'] = $statusToClass[$suite['status']];
        // Set summary.
        $arr = array_filter($suiteStat, fn($val) => $val > 0);
        $arrr = [];
        foreach ($arr as $key => $value) {
          $arrr[] = "$value {$statusToClass[$key]}";
        }
        $suite['summary'] = implode(', ', $arrr);
      }
    }

    // Change attachments URL relative to index.
    if (array_key_exists('suites', $report)) {
      foreach ($report['suites'] as &$suite) {
        if (array_key_exists('specs', $suite)) {
          foreach ($suite['specs'] as &$spec) {
            if (array_key_exists('tests', $spec)) {
              foreach ($spec['tests'] as &$test) {
                if (array_key_exists('results', $test)) {
                  foreach ($test['results'] as &$result) {
                    if (array_key_exists('attachments', $result)) {
                      foreach ($result['attachments'] as &$attachment) {
                        if (array_key_exists('path', $attachment)) {
                          $path = $attachment['path'];
                          // Remove local root directory from the path.
                          $path = str_replace('/tmp/test-results/', '', $path);
                          // Generate a unique ID for the pop-up.
                          $id = str_replace('/', '-', $path);
                          // Add remote root from the report URI.
                          $path = str_replace('index.json', '', $reportUri) . $path;
                          // Set these properties back to attachment.
                          $attachment['id'] = $id;
                          $attachment['path'] = $path;
                        }
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    }

    // etc...
  }

}
