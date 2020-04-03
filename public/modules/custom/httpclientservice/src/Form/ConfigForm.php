<?php

namespace Drupal\httpclientservice\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Class for implementing custom system configuration form.
 */
class ConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'httpclientservice_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->getConfig();

    $form['httpclientservice_caller'] = [
      '#type' => 'key_select',
      '#title' => $this->t('API Caller'),
      '#default_value' => $config->get('httpclientservice_caller'),
      '#description' => $this->t('Your Caller value.'),
    ];

    $form['httpclientservice_public_key'] = [
      '#type' => 'key_select',
      '#title' => $this->t('API Public Key'),
      '#default_value' => $config->get('httpclientservice_public_key'),
      '#description' => $this->t('Your API Key.'),
    ];

    $form['httpclientservice_base_uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Base URL'),
      '#default_value' => $config->get('httpclientservice_base_uri'),
      '#description' => $this->t('Api base url value'),
    ];

    return parent::buildForm($form, $form_state);
  }

}
