<?php

namespace Drupal\pl_drupal_forge\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a custom form for the test runner.
 */
class TestRunnerForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'test_runner_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Add the JavaScript library for the form.
    $form['#attached']['library'][] = 'pl_drupal_forge/main';

    $form['#attributes'] = [
      'class' => ['pl-form'],
    ];

    $form['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#default_value' => \Drupal::request()->getSchemeAndHttpHost(),
      '#required' => TRUE,
      '#attributes' => [
        'class' => ['pl-form-item__input'],
      ],
      "#label_attributes" => [
        'class' => ['pl-form-item__label'],
      ],
      '#wrapper_attributes' => [
        'class' => ['pl-form-item'],
      ],
    ];

    $form['tags'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => $this->t('Tags'),
      '#options' => $this->getTags(),
      '#attributes' => [
        'class' => ['pl-form-item__select'],
      ],
      "#label_attributes" => [
        'class' => ['pl-form-item__label'],
      ],
      '#wrapper_attributes' => [
        'class' => ['pl-form-item'],
      ],
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Start Tests'),
      '#attributes' => [
        'id' => 'the-button',
        'class' => ['pl-button', 'pl-button--lg'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // This function is intentionally left empty.
    // The form submission is handled via JavaScript.
  }

  /**
   * Helper function to get tags.
    */
  private function getTags() {
    // Fetch tags from configuration.
    $config = $this->config('pl_drupal_forge.settings');
    $tags = $config->get('tags') ?? [];

    // Ensure the tags are in the correct format for the form.
    return array_combine($tags, $tags);
  }

}
