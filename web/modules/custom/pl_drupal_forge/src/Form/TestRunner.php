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
    $form['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#default_value' => \Drupal::request()->getSchemeAndHttpHost(),
      '#required' => TRUE,
    ];

    $form['tags'] = [
      '#type' => 'select',
      '#title' => $this->t('Tags'),
      '#options' => $this->getTags(),
      '#multiple' => TRUE,
      '#attributes' => [
        'class' => ['js-multiselect'],
        'style' => 'min-width: 400px',
      ],
    ];

    $form['actions']['submit'] = [
      '#type' => 'button',
      '#value' => $this->t('Start Tests'),
      '#attributes' => [
        'id' => 'the-button',
        'class' => ['pl-button', 'pl-button--lg'],
        'onclick' => 'invoke(); return false;', // Prevent default form submission.
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // This form does not handle submissions directly.
  }

  /**
   * Helper function to get tags.
   */
  private function getTags() {
    // Replace this with your dynamic tag generation logic.
    return [
      'tag1' => $this->t('Tag 1'),
      'tag2' => $this->t('Tag 2'),
      'tag3' => $this->t('Tag 3'),
    ];
  }

}
