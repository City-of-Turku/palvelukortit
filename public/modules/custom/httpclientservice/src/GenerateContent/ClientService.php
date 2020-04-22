<?php

namespace Drupal\httpclientservice\GenerateContent;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\httpclientservice\GenerateContent\ApiUser;

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
  public function httpclientserviceCheckExist($code, $type) {
    $query = \Drupal::entityQuery('node')
      ->condition('type', $type)
      ->condition('field_code', $code);

    if ($result = $query->execute()) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Create node translations.
   *
   * {@inheritdoc}
   */
  public function httpclientserviceTranslateEntity($node, $data, $†ype) {
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
      $node_tr = $node->addTranslation($language);
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

}
