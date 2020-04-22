<?php

namespace Drupal\httpclientservice\GenerateContent;

use Drupal\node\Entity\Node;
use Drupal\httpclientservice\GenerateContent\ApiUser;
use Drupal\httpclientservice\GenerateContent\ClientService;

/**
 * Palvelupiste content type controller.
 */
class Palvelupiste {

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
   * Palvelupiste constructor.
   */
  public function __construct() {
    $this->languages = ['en', 'sv'];
    $this->type = 'customer_service';
    $this->client = new ClientService();
  }

  /**
   * Get Customer Services from API.
   *
   * @return mixed
   *   Data from API
   */
  public function httpclientserviceGetPalvelupisteet() {
    $data = $this->client->httpclientserviceGetService('api/v1/palvelupisteet');

    return $data;
  }

  /**
   * Save Customer Services to Drupal.
   */
  public function httpclientserviceSavePavelupisteet() {
    // Get custom service content type data from API.
    $palvelupisteet = $this->httpclientserviceGetPalvelupisteet();

    if (!$palvelupisteet) {
      return;
    }

    foreach ($palvelupisteet as $palvelupiste) {
      // Customer services code value aka id.
      $code = $palvelupiste['koodi'];

      // Check if Customer Services already exist.
      if (!$this->client->httpclientserviceCheckExist($code, $this->type)) {
        // Check that finnish version include title.
        // Node cannot be created without title.
        if (!isset($palvelupiste['nimi_kieliversiot']['fi'])) {
          // Logs a notice.
          \Drupal::logger('httpclientservice')->notice('@type: API save failed because empty title', ['@type' => 'Customer Service']);

          // Cannot save data if title is empty.
          continue;
        }

        // Create Customer Services.
        $this->httpclientserviceCreatePalvelupiste($palvelupiste);
      }
    }
  }

  /**
   * Create Customer Services from data.
   */
  public function httpclientserviceCreatePalvelupiste($data) {
    // Get APi user uid which create node.
    $uid = new ApiUser();
    $titles = $data['nimi_kieliversiot'];
    $descriptions = $data['kuvaus_kieliversiot'];
    $status = ($data['tila']['koodi'] == '1') ? 1 : 0;

    // Create node.
    $node = Node::create([
      // The node entity bundle in this case article.
      'type' => $this->type,
      'langcode' => 'fi',
      'created' => \Drupal::time()->getRequestTime(),
      'changed' => \Drupal::time()->getRequestTime(),
      'uid' => $uid->getApiuser(),
      'status' => $status,
      'title' => $titles['fi'],
      'field_description' => $descriptions['fi'],
      'field_code' => $data['koodi'],
      'field_updated_date' => date('Y-m-d', strtotime($data['muutospvm']))
    ]);

    // Saving original the node.
    $node->save();

    // Translate entity.
    $this->client->httpclientserviceTranslateEntity($node, $data, $this->type);
  }

}
