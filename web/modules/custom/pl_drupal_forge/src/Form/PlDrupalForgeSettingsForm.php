<?php

namespace Drupal\pl_drupal_forge\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure settings for the PL Drupal Forge module.
 */
class PlDrupalForgeSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['pl_drupal_forge.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pl_drupal_forge_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('pl_drupal_forge.settings');

    $form['show_url'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show URL'),
      '#default_value' => $config->get('show_url'),
      '#description' => $this->t('Check this box to show the URL field on the test runner page. It will be hidden by default.'),
    ];

    $form['output_placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Output placeholder text'),
      '#default_value' => $config->get('output_placeholder'),
      '#description' => $this->t('Placeholder text for the test results output.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('pl_drupal_forge.settings')
      ->set('show_url', $form_state->getValue('show_url'))
      ->set('output_placeholder', $form_state->getValue('output_placeholder'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
