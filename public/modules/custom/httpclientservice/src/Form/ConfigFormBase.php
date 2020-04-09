<?php

namespace Drupal\httpclientservice\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase as DrupalConfigFormBase;

/**
 * Class ConfigFormBase.
 *
 * @package Drupal\httpclientservice\Form
 */
abstract class ConfigFormBase extends DrupalConfigFormBase {

  const CONFIG_NAME = 'httpclientservice.settings';

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [self::CONFIG_NAME];
  }

  /**
   * Returns this modules configuration object.
   */
  protected function getConfig() {
    return $this->config(self::CONFIG_NAME);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->getConfig();
    $values = $form_state->getValues();
    foreach ($values as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();
  }

}
