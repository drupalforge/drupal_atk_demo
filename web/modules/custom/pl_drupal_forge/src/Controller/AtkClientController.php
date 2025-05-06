<?php

namespace Drupal\pl_drupal_forge\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use GuzzleHttp\Client;


class AtkClientController extends ControllerBase {
  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a StartPageController object.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   */
  public function __construct(FormBuilderInterface $form_builder) {
    $this->formBuilder = $form_builder;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder')
    );
  }

  /**
   * Test Runner page.
   * On button press, the Lambda function is triggered,
   * which runs ATK {@link https://www.drupal.org/project/automated_testing_kit/} tests
   * against this very site, and update the output to the page.
   *
   * @return array A simple renderable array.
   */
  public function start() {
    // Get config from settings form.
    $config = $this->config('pl_drupal_forge.settings');
    $show_url = $config->get('show_url');


    // Build the form render array.
    $form = $this->formBuilder->getForm('Drupal\pl_drupal_forge\Form\TestRunnerForm');

    return [
      '#theme' => 'start_page_template',
      '#show_url' => $show_url,
      '#start_test_form' => $form,
      '#base_url' => $this->getBaseUrl(),
      '#tags' => $this->config('pl_drupal_forge.settings')->get('tags'),
    ];
  }

  protected function getBaseUrl() {
    return \Drupal::request()->getSchemeAndHttpHost() . '/';
  }
}
