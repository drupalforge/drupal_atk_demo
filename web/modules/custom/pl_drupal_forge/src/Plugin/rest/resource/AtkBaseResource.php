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

    $container = \Drupal::getContainer();
    // Initialize RunService regarding to the given configuration settings.
    // Sometimes null comes, so Drupal will re-initialize it during request.
    $config = $container->get('config.factory')->get('pl_drupal_forge.settings');
    $runType = $config->get('runType');
    if ((bool)$runType) {
      $this->runService = $container->get("pl_drupal_forge.$runType");
    }

    if (!(bool)$this->runService) {
      $this->logger->error("Initialization of 'pl_drupal_forge.$runType' miserably failed");
    } else {
      $this->logger->notice("Initialization of 'pl_drupal_forge.$runType' successful");
    }
  }

}
