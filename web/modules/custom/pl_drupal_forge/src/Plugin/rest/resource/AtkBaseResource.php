<?php

namespace Drupal\pl_drupal_forge\Plugin\rest\resource;

use Drupal\pl_drupal_forge\Service\RunService;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AtkBaseResource extends ResourceBase
{
  protected RunService|null $runService;

  public function __construct(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    // Drypal don't care about data consistency, but cares about variable
    //  initialization.
    $this->runService = null;

    // Drupal plugin's boilerplate.
    $serializer_formats = $container->getParameter('serializer.formats');
    $logger = $container->get('logger.factory')->get('pl_drupal_forge');
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->init();
  }

  static function response(mixed $data): ResourceResponse
  {
    $shutdownCache = ['#cache' => ['max-age' => 0]];
    return (new ResourceResponse($data))->addCacheableDependency($shutdownCache);
  }

  /**
   * Laze initialize service.
   *
   * @return void
   */
  protected function init(): void
  {
    if ((bool)$this->runService) {
      return;
    }

    try {
      $container = \Drupal::getContainer();
      // Initialize RunService regarding to the given configuration settings.
      $config = $container->get('config.factory')->get('pl_drupal_forge.settings');
      $runType = $config->get('runType');
      
      if ((bool)$runType && $container->has("pl_drupal_forge.$runType")) {
        $this->runService = $container->get("pl_drupal_forge.$runType");
        $this->logger->notice("Initialization of 'pl_drupal_forge.$runType' successful");
      } else {
        // If the service doesn't exist or runType is not set, use a fallback approach
        $this->logger->notice("Service 'pl_drupal_forge.$runType' not available, using fallback");
        // You could set a fallback service here if needed
      }
    } catch (\Exception $e) {
      $this->logger->error("Error initializing service: @message", ['@message' => $e->getMessage()]);
    }
  }

}
