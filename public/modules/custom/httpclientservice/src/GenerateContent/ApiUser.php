<?php

namespace Drupal\httpclientservice\GenerateContent;

use Drupal\user\Entity\User;

/**
 * API User Class.
 */
class ApiUser {

  /**
   * User uid.
   *
   * @var string
   */
  protected $apiUser;

  /**
   * ApiUser constructor.
   */
  public function __construct() {
    $this->apiUser = $this->httpclientserviceCheckApiUser();
  }

  /**
   * API User.
   */
  public function getApiUser() {
    return (int) $this->apiUser;
  }

  /**
   * Get API User uid.
   */
  public function httpclientserviceCheckApiUser() {
    if (!$user = $this->httpclientserviceCheckApiUserExist()) {
      $user = $this->httpclientserviceCreateApiUser();
    }

    return $user;
  }

  /**
   * Check if APi User exiest.
   */
  public function httpclientserviceCheckApiUserExist() {
    $id = \Drupal::entityQuery('user')
      ->condition('name', 'API')
      ->range(0, 1)
      ->execute();

    if (empty($id)) {
      return FALSE;
    }

    return reset($id);
  }

  /**
   * Create a new API User.
   */
  public function httpclientserviceCreateApiUser() {
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();

    $user = User::create();
    // Mandatory settings.
    $user->setPassword('api');
    $user->enforceIsNew();
    $user->setEmail('tuomas.valimaa@druid.fi');
    $user->setUsername('API');
    // Optional settings.
    $user->activate();
    // Adding default user roles.
    $user->addRole('content_editor');
    // Save user.
    $user->save();

    return $user->id();
  }

}
