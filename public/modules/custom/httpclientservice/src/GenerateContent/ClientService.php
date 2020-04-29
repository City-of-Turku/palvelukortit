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
  public function httpclientserviceCheckExist($code, $type, $language = 'fi') {
    $query = \Drupal::entityQuery('node')
      ->condition('type', $type)
      ->condition('langcode', $language)
      ->condition('field_code', $code);

    if ($result = $query->execute()) {
      return reset($result);
    }

    return FALSE;
  }

  /**
   * Create node translations.
   *
   * {@inheritdoc}
   */
  public static function httpclientserviceTranslateEntity($node, $data, $†ype) {
    // Get API user uid.
    $uid = new ApiUser();
    $languages = ['en', 'sv'];
    $titles = $data['nimi_kieliversiot'];
    // Convert change date value from APi to Drupal date time.
    $dateTime = new DrupalDateTime($data['muutospvm'], 'UTC');
    $date = $dateTime->getTimestamp();

    foreach ($languages as $language) {
      // Check that data include translation title.
      // Cannot save node without title.
      if (!isset($titles[$language]) || empty($titles[$language])) {
        \Drupal::logger('httpclientservice')->notice('@type: API %title missing %language translation',
          [
            '@type' => $†ype,
            '%title' => $titles['fi'],
            '%language' => $language
          ]
        );

        continue;
      }

      // Translation base field.
      if ($node->hasTranslation($language)) {
        $node_tr = $node->getTranslation($language);
      }
      else {
        $node_tr = $node->addTranslation($language);
      }

      $node_tr->uid = $uid->getApiUser();
      $node_tr->title = $titles[$language];
      $node_tr->field_code = $data['koodi'];
      $node_tr->field_updated_date = $date;

      // Content type's which has description field.
      if ($type = 'customer_service' || $type = 'service_card') {
        $descriptions = $data['kuvaus_kieliversiot'];
        $node_tr->field_description = (isset($descriptions[$language])) ? strip_tags($descriptions[$language]) : '';
        $node_tr->status = ($data['tila']['koodi'] == '1') ? 1 : 0;
      }

      // Customer serivice content type fields.
      if ($type = 'customer_service') {
        $palvelupiste = new Palvelupiste();

        // Convert more information value into Drupal fields.
        $node_tr->field_more_information_link = $palvelupiste->httpclientserviceConvertMoreInformationValue($data, $language);

        // Convert telephone data into Drupal field.
        $node_tr->field_telephone‎ = $palvelupiste->httpclientserviceConvertPhoneValue($data['puhelinnumerot']);

        // Address information.
        $node_tr->field_address = $palvelupiste->httpclientserviceGetAddressValue($data, $language);
      }

      // Service offer type fields.
      if ($type = 'service_offer') {
        $palvelutarjous = new Palvelutarjous();

        $node_tr->field_terms = $data['palvelunsaanninEhdot_kieliversiot'][$language];

        // Create and get opening hours paragraph data.
        $opening_hours = $palvelutarjous->httpclientserviceTranslateOpenHourParagraph($node_tr->field_opening_hours_reference, $data, $language);

        // Create and get pricing paragraph data.
        $pricing = $palvelutarjous->httpclientserviceTranslatePricingParagraph($node_tr->field_pricing_reference, $data, $language);

        $node_tr->field_opening_hours_reference = $opening_hours;
        $node_tr->field_pricing_reference = $pricing;
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
   * Convert change date value from APi to Drupal date time.
   */
  public function httpclientserviceConvertTimeStamp($date) {
    $dateTime = new DrupalDateTime($date, 'UTC');
    return $dateTime->getTimestamp();
  }

  /**
   * Delete multiple paragraphs.
   *
   * {@inheritdoc}
   */
  public function httpclientserviceDeleteParagraph($paragraphs) {
    $paragraphs = $paragraphs->referencedEntities();

    foreach ($paragraphs as $paragraph) {
      $paragraph->delete();
    }
  }

}
