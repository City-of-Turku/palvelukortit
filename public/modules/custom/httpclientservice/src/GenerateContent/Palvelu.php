<?php

namespace Drupal\httpclientservice\GenerateContent;

use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\httpclientservice\GenerateContent\ApiUser;
use Drupal\httpclientservice\GenerateContent\ClientService;

/**
 * An example controller.
 */
class Palvelu {

  /**
   * API Base URL.
   *
   * @var string
   */
  protected $languages;

  /**
   * This Class Content Type.
   *
   * @var string
   */
  protected $type;

  /**
   * ClientService.
   *
   * @var object
   */
  protected $client;

  /**
   * Palvelu class constructor.
   */
  public function __construct() {
    $this->languages = ['en', 'sv'];
    $this->type = 'service_card';
    $this->client = new ClientService();
  }

  /**
   * Get services from API.
   *
   * @return mixed
   *   Data from API
   */
  public function httpclientserviceGetPalvelut() {
    $data = $this->client->httpclientserviceGetService('api/v1/palvelut');

    return $data;
  }

  /**
   * Save services to Drupal.
   */
  public function httpclientserviceSavePalvelu() {
    // Get service content type data from API.
    $palvelut = $this->httpclientserviceGetPalvelut();

    if (!$palvelut) {
      return;
    }

    foreach ($palvelut as $palvelu) {
      // Service's code value aka id.
      $code = $palvelu['koodi'];

      // Check if service already exist.
      if (!$this->client->httpclientserviceCheckExist($code, $this->type)) {
        // Check that finnish version include title.
        // Node cannot be created without title.
        if (!isset($palvelu['nimi_kieliversiot']['fi'])) {
          // Logs a notice.
          \Drupal::logger('httpclientservice')->notice('@type: API save failed because empty title', ['@type' => 'Customer Service']);

          // Cannot save data if title is empty.
          continue;
        }

        // Create service node.
        $this->httpclientserviceCreatePalvelu($palvelu);
      }
    }
  }

  /**
   * Save services to Drupal.
   */
  public function httpclientserviceSavePalveluDev($id = 18) {
    // Get service content type data from API.
    $palvelut = $this->httpclientserviceGetPalvelut();
    $palvelu = $palvelut[$id];
    // Create service node.
    $this->httpclientserviceCreatePalvelu($palvelu);
  }

  /**
   * Create service node from data.
   */
  public function httpclientserviceCreatePalvelu($data) {
    // Get APi user uid which create node.
    $uid = new ApiUser();
    $descriptions = $data['kuvaus_kieliversiot'];
    $status = ($data['tila']['koodi'] == '1') ? 1 : 0;

    // Convert change date value from APi to Drupal date time.
    $dateTime = new DrupalDateTime($data['muutospvm'], 'UTC');
    $date = $dateTime->getTimestamp();

    // Convert Service types taxonomy data to Drupal taxonomies.
    $service_types = $this->httpclientserviceCreateServicetypeTaxonomy($data['palvelutyypit']);

    // Convert Service Offer's to Drupal reference's.
    $service_offers = $this->httpclientserviceCreateServiceOfferReferences($data['palvelutarjoukset']);

    // Create node.
    $node = Node::create([
      // The node entity bundle in this case article.
      'type' => $this->type,
      'langcode' => 'fi',
      'created' => \Drupal::time()->getRequestTime(),
      'changed' => \Drupal::time()->getRequestTime(),
      'uid' => $uid->getApiUser(),
      'status' => $status,
      'title' => $data['nimi_kieliversiot']['fi'],
      'field_description' => strip_tags($descriptions['fi']),
      'field_code' => $data['koodi'],
      'field_updated_date' => $date,
      'field_service_types' => $service_types,
      'field_service_offers' => $service_offers
    ]);

    // Saving original the node.
    $node->save();

    // Translate entity.
    $this->client->httpclientserviceTranslateEntity($node, $data, $this->type);
  }

  /**
   * Convert service types data to Drupal taxonomies.
   */
  public function httpclientserviceCreateServicetypeTaxonomy($taxonomies) {
    $service_types = [];

    foreach ($taxonomies as $key => $taxonomy) {
      $languages = ['en', 'sv'];

      // Check that data include finnish title.
      // Cannot save taxonomy term without title.
      if (!isset($taxonomy['nimi_kieliversiot']['fi']) || empty($taxonomy['nimi_kieliversiot']['fi'])) {
        continue;
      }

      // Check if taxonomy term already exist.
      if ($term = $this->httpclientserviceCheckTaxonomyExist($taxonomy['koodi'])) {
        // Use exist taxonomy term id.
        $service_types[$key] = $term;
      }
      else {
        // If taxonomy term not exist. Create a new taxonomy term.
        $newterm = Term::create([
          'parent' => [],
          'name' => $taxonomy['nimi_kieliversiot']['fi'],
          'vid' => 'service_types',
          'field_code' => $taxonomy['koodi']
        ]);

        // Save the taxonomy term.
        $newterm->save();

        foreach ($languages as $language) {
          // Check that data include translation title.
          // Cannot save taxonomy term without title.
          if (!isset($taxonomy['nimi_kieliversiot'][$language]) || empty($taxonomy['nimi_kieliversiot'][$language])) {
            continue;
          }

          // Create taxonomy term translation's.
          $newterm->addTranslation($language, [
            'name' => $taxonomy['nimi_kieliversiot'][$language],
          ])->save();
        }

        // Return created taxonomy term id.
        $service_types[$key] = $newterm->id();
      }
    }

    return $service_types;
  }

  /**
   * Check if taxonomy term exist.
   */
  public function httpclientserviceCheckTaxonomyExist($code) {
    $query = \Drupal::entityQuery('taxonomy_term');
    $query->condition('vid', 'service_types');
    $query->condition('field_code', $code);

    if ($result = $query->execute()) {
      return reset($result);
    }

    return NULL;
  }

  /**
   * Convert service offer data to Drupal Service offers reference value.
   */
  public function httpclientserviceCreateServiceOfferReferences($offers) {
    $offer_references = [];

    foreach ($offers as $key => $offer) {
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'service_offer')
        ->condition('langcode', 'fi')
        ->condition('field_code', $offer['koodi']);

      if ($result = $query->execute()) {
        $offer_references[$key] = reset($result);
      }
    }

    return $offer_references;
  }

}
