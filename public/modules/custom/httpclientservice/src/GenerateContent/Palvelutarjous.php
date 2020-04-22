<?php

namespace Drupal\httpclientservice\GenerateContent;

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
   * Create Services Offer from data.
   */
  public function httpclientserviceCreatePalvelutarjous($data) {
    // Get APi user uid which create node.
    $uid = new ApiUser();
    $titles = $data['nimi_kieliversiot'];

    // Create node.
    $node = Node::create([
      // The node entity bundle in this case article.
      'type' => $this->type,
      'langcode' => 'fi',
      'created' => \Drupal::time()->getRequestTime(),
      'changed' => \Drupal::time()->getRequestTime(),
      'uid' => $uid->getApiuser(),
      'title' => $titles['fi'],
      'field_code' => $data['koodi'],
      'field_updated_date' => date('Y-m-d', strtotime($data['muutospvm']))
    ]);

    // Saving original the node.
    $node->save();

    $this->client->httpclientserviceTranslateEntity($node, $data, $this->type);
  }

}
