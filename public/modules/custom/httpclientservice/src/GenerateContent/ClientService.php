<?php

namespace Drupal\httpclientservice\GenerateContent;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\httpclientservice\GenerateContent\ApiUser;
use Drupal\httpclientservice\GenerateContent\Palvelupiste;

/**
 * An example controller.
 */
class ClientService {

  /**
   * Active languages.
   *
   * @var array
   */
  protected $languages;

  /**
   * Default site language.
   *
   * @var string
   */
  protected $defaultLanguage;

  /**
   * Palvelupiste constructor.
   */
  public function __construct() {
    $languages = \Drupal::languageManager()->getLanguages();
    $this->languages = array_keys($languages);
    $this->defaultLanguage = 'fi';
  }

  /**
   * Get data from API using url & query parameters.
   *
   * {@inheritdoc}
   */
  public function httpclientserviceGetService($url, $query = [], $code = NULL) {
    $client = \Drupal::service('httpclientservice.client');
    $query = ['verify' => FALSE, 'debug' => TRUE];
    $url = ($code) ? $url . '/' . $code : $url;
    $request = $client->connect('GET', $url, $query);

    return Json::decode($request);
  }

  /**
   * Check if entity already created.
   *
   * {@inheritdoc}
   */
  public function httpclientserviceCheckExist($code, $type, $language) {
    $language = (empty($language)) ? $this->defaultLanguage : $language;
    $query = \Drupal::entityQuery('node')
      ->condition('type', $type)
      ->condition('langcode', $language)
      ->condition('field_code', $code);

    $result = (int) $query->count()->execute();
    if ($result > 0) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Create node translations.
   *
   * {@inheritdoc}
   */
  public function httpclientserviceTranslateEntity($node, $data, $type, $original_language) {
    // Get API user uid.
    $uid = new ApiUser();
    $languages = array_combine($this->languages, $this->languages);
    unset($languages[$original_language]);

    // Skip translation if the title doesn't exist.
    if (!isset($data['nimi_kieliversiot'])) {
      return;
    }

    $title_array = $data['nimi_kieliversiot'];

    // Convert change date value from APi to Drupal date time.
    $dateTime = new DrupalDateTime($data['muutospvm'], 'UTC');
    $date = $dateTime->getTimestamp();

    foreach ($languages as $language) {
      // Check that data include translation title.
      // Cannot save node without title.
      if (!isset($title_array[$language]) || empty($title_array[$language])) {
        continue;
      }

      // Translation base field.
      $node_tr = $node->addTranslation($language);
      $node_tr->uid = $uid->getApiUser();
      $node_tr->title = $title_array[$language];
      $node_tr->field_code = $data['koodi'];
      $node_tr->field_updated_date = $date;

      // Content types's which has description field.
      if ($type = 'customer_service' || $type = 'service_card') {
        if (isset($data['kuvaus_kieliversiot'])) {
          $descriptions = $data['kuvaus_kieliversiot'];
          if (isset($descriptions[$language])) {
            $node_tr->field_description = strip_tags($descriptions[$language]);
          }
        }
        $node_tr->status = ($data['tila']['koodi'] == '1') ? 1 : 0;
      }

      // Customer service content type fields.
      if ($type = 'customer_service') {
        $palvelupiste = new Palvelupiste();

        // Convert more information value into Drupal fields.
        $node_tr->field_more_information_link = $palvelupiste->httpclientserviceConvertMoreInformationValue($data, $language);

        // Convert telephone data into Drupal field.
        if (isset($data['puhelinnumerot'])) {
          $node_tr->field_telephone‎ = $palvelupiste->httpclientserviceConvertPhoneValue($data['puhelinnumerot']);
        }

        // Address information.
        $node_tr->field_address = $palvelupiste->httpclientserviceGetAddressValue($data, $language);
      }

      // Service offer type fields.
      if ($type = 'service_offer' && isset($data['palvelunsaanninEhdot_kieliversiot'][$language])) {
        $node_tr->field_service_terms = $data['palvelunsaanninEhdot_kieliversiot'][$language];
      }

      // Saving translation.
      $node_tr->save();
    }
  }

  /**
   * Delete one or multiple entitys.
   *
   * {@inheritdoc}
   */
  public function httpclientserviceDeleteEntity($type, $nid = NULL) {
    if ($nid) {
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);

      if ($node) {
        $node->delete();
      }
    }
    else {
      $nodes = \Drupal::entityQuery("node")
        ->condition('type', $type)
        ->execute();

      $storage_handler = \Drupal::entityTypeManager()->getStorage("node");

      if (!empty($nodes)) {
        foreach ($nodes as $key => $value) {
          $node = $storage_handler->load($value);
          $node->delete($node);
        }
      }
    }
  }

  /**
   * Delete all vocabulary taxonomy terms.
   *
   * {@inheritdoc}
   */
  public function httpclientserviceDeleteTaxonomies($vocabulary) {
    $tids = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', $vocabulary)
      ->execute();

    $controller = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $entities = $controller->loadMultiple($tids);
    $controller->delete($entities);
  }

  /**
   * Search for original language from dataset.
   *
   * @param array $data
   *   Dataset from API.
   * @param string $code
   *   Code which connects node and API dataset.
   * @param string $type
   *   Node type.
   *
   * @return string|false
   *   Return original language code or FALSE.
   */
  public function retrieveOriginalLanguageTitle(array $data, $code, $type) {
    if (
      isset($data['nimi_kieliversiot']) &&
      !array_key_exists($this->defaultLanguage, $data['nimi_kieliversiot'])
    ) {
      $translation_language = $this->defaultLanguage;

      foreach ($this->languages as $langcode) {
        if (
          $langcode !== $this->defaultLanguage &&
          array_key_exists($langcode, $data['nimi_kieliversiot']) &&
          !$this->httpclientserviceCheckExist($code, $type, $langcode)
        ) {
          $translation_language = $langcode;
          break;
        }
      }

      if ($translation_language != $this->getDefaultLanguage()) {
        return $translation_language;
      }

      // Logs a notice.
      \Drupal::logger('httpclientservice')
        ->notice('@type: API save failed because empty title', ['@type' => $type]);
      return FALSE;
    }

    if (isset($data['nimi_kieliversiot'][$this->defaultLanguage])) {
      return $this->defaultLanguage;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Getter for languages.
   *
   * @return array|string
   *   Returns an array of language codes.
   */
  public function getLanguages() {
    return $this->languages;
  }

  /**
   * Getter for default language.
   *
   * @return array|string
   *   Returns default language code.
   */
  public function getDefaultLanguage() {
    return $this->defaultLanguage;
  }

}
