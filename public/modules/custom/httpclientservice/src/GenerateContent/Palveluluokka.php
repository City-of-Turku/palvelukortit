<?php

namespace Drupal\httpclientservice\GenerateContent;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\node\Entity\Node;
use Drupal\httpclientservice\GenerateContent\ClientService;
use Drupal\httpclientservice\GenerateContent\ApiUser;

/**
 * Palveluluokka content type controller.
 */
class Palveluluokka {

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
   * Palveluluokka constructor.
   */
  public function __construct() {
    $this->languages = ['en', 'sv'];
    $this->type = 'service_class';
    $this->client = new ClientService();
  }

  /**
   * Get Service Classes from API.
   *
   * @return mixed
   *   Data from API
   */
  public function httpclientserviceGetPalveluluokat() {
    $data = $this->client->httpclientserviceGetService('api/v1/palveluluokat');

    return $data;
  }

  /**
   * Save service Classes to Drupal.
   */
  public function httpclientserviceSavePalveluluokat() {
    // Get service class content type data from API.
    $palveluluokat = $this->httpclientserviceGetPalveluluokat();

    if (!$palveluluokat) {
      return;
    }

    foreach ($palveluluokat as $palveluluokka) {
      // Service class code value aka id.
      $code = $palveluluokka['koodi'];

      // Check if service class already exist.
      if (!$this->client->httpclientserviceCheckExist($code, $this->type, 'fi')) {
        // Check that finnish version include title.
        // Node cannot be created without title.
        if (!isset($palveluluokka['nimi_kieliversiot']['fi'])) {
          // Logs a notice.
          \Drupal::logger('httpclientservice')->notice('@type: API save failed because empty title', ['@type' => 'Customer Service']);

          // Cannot save data if title is empty.
          continue;
        }

        // Create service class node.
        $this->httpclientserviceCreatePalveluluokka($palveluluokka);
      }
    }
  }

  /**
   * Create service class node from data.
   */
  public function httpclientserviceCreatePalveluluokka($data) {
    // Get APi user uid which create node.
    $uid = new ApiUser();
    $titles = $data['nimi_kieliversiot'];

    // Convert change date value from APi to Drupal date time.
    $dateTime = new DrupalDateTime($data['muutospvm'], 'UTC');
    $date = $dateTime->getTimestamp();

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
      'field_updated_date' => $dateTime
    ]);

    // Saving original the node.
    $node->save();

    $this->client->httpclientserviceTranslateEntity($node, $data, $this->type);
  }

}
