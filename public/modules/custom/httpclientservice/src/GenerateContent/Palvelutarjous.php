<?php

namespace Drupal\httpclientservice\GenerateContent;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\node\Entity\Node;
use Drupal\httpclientservice\GenerateContent\ClientService;
use Drupal\httpclientservice\GenerateContent\ApiUser;

/**
 * Service offer type controller.
 */
class Palvelutarjous {

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
   * Palvelutarjous constructor.
   */
  public function __construct() {
    $this->languages = ['en', 'sv'];
    $this->type = 'service_offer';
    $this->client = new ClientService();
  }

  /**
   * Get Service offer's from API.
   *
   * @return mixed
   *   Data from API
   */
  public function httpclientserviceGetPalvelutarjoukset() {
    $data = $this->client->httpclientserviceGetService('api/v1/palvelutarjoukset');

    return $data;
  }

  /**
   * Save Service offers to Drupal.
   */
  public function httpclientserviceSavePalvelutarjoukset() {
    // Get service offer content type data from API.
    $palvelutarjoukset = $this->httpclientserviceGetPalvelutarjoukset();

    if (!$palvelutarjoukset) {
      return;
    }

    foreach ($palvelutarjoukset as $palvelutarjous) {
      // Service offer code value aka id.
      $code = $palvelutarjous['koodi'];

      // Check if service offer already exist.
      if (!$this->client->httpclientserviceCheckExist($code, $this->type)) {
        // Check that finnish version include title.
        // Node cannot be created without title.
        if (!isset($palvelutarjous['nimi_kieliversiot']['fi'])) {
          // Logs a notice.
          \Drupal::logger('httpclientservice')->notice('@type: API save failed because empty title', ['@type' => 'Customer Service']);

          // Cannot save data if title is empty.
          continue;
        }

        // Create service offer node.
        $this->httpclientserviceCreatePalvelutarjous($palvelutarjous);
      }
    }
  }

  /**
   * Save One Service offers.
   */
  public function httpclientserviceSavePalvelutarjousDev($id = 156) {
    $palvelutarjoukset = $this->httpclientserviceGetPalvelutarjoukset();
    $palvelutarjous = $palvelutarjoukset[$id];
    // Create Customer Services.
    $this->httpclientserviceCreatePalvelutarjous($palvelutarjous);
  }

  /**
   * Create Services Offer from data.
   */
  public function httpclientserviceCreatePalvelutarjous($data) {
    // Get APi user uid which create node.
    $uid = new ApiUser();
    $titles = $data['nimi_kieliversiot'];

    // Convert change date value from APi to Drupal date time.
    $dateTime = new DrupalDateTime($data['muutospvm'], 'UTC');
    $date = $dateTime->getTimestamp();

    // Convert palvelupiste data into Drupal field.
    $service_reference = $this->httpclientserviceCreateCustomerServiceReferences($data['palvelupiste']['koodi']);

    // Create node.
    $node = Node::create([
      // The node entity bundle in this case article.
      'type' => $this->type,
      'langcode' => 'fi',
      'created' => \Drupal::time()->getRequestTime(),
      'changed' => \Drupal::time()->getRequestTime(),
      'uid' => $uid->getApiUser(),
      'title' => $titles['fi'],
      'field_code' => $data['koodi'],
      'field_updated_date' => $date,
      'field_terms' => $data['palvelunsaanninEhdot_kieliversiot']['fi'],
      'field_customer_service_reference' => $service_reference
    ]);

    // Saving original the node.
    $node->save();

    $this->client->httpclientserviceTranslateEntity($node, $data, $this->type);
  }

  /**
   * Convert service offer data to Drupal Service offers reference value.
   */
  public function httpclientserviceCreateCustomerServiceReferences($id) {
    $service_references = [];

    $query = \Drupal::entityQuery('node')
      ->condition('type', 'customer_service')
      ->condition('langcode', 'fi')
      ->condition('field_code', $id);

    if ($result = $query->execute()) {
      $service_references = reset($result);
    }

    return $service_references;
  }

}
