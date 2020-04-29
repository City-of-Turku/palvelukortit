<?php

namespace Drupal\httpclientservice\GenerateContent;

use Drupal\Core\Datetime\DrupalDateTime;
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
   * Save One Customer Services.
   */
  public function httpclientserviceSavePavelupisteDev($id = 1453) {
    $palvelupisteet = $this->httpclientserviceGetPalvelupisteet();
    $palvelupiste = $palvelupisteet[$id];
    // Create Customer Services.
    $this->httpclientserviceCreatePalvelupiste($palvelupiste);
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

    // Convert change date value from APi to Drupal date time.
    $dateTime = new DrupalDateTime($data['muutospvm'], 'UTC');
    $date = $dateTime->getTimestamp();

    // Convert more information value into Drupal fields.
    $links = $this->httpclientserviceConvertMoreInformationValue($data, 'fi');

    // Convert telephone data into Drupal field.
    $telephones = $this->httpclientserviceConvertPhoneValue($data);

    // Adress information.
    $address = $this->httpclientserviceGetAddressValue($data, 'fi');

    // Create node.
    $node = Node::create([
      // The node entity bundle in this case article.
      'type' => $this->type,
      'langcode' => 'fi',
      'created' => \Drupal::time()->getRequestTime(),
      'changed' => \Drupal::time()->getRequestTime(),
      'uid' => $uid->getApiUser(),
      'status' => $status,
      'title' => $titles['fi'],
      'field_description' => $descriptions['fi'],
      'field_code' => $data['koodi'],
      'field_updated_date' => $date,
      'field_email' => $data['sahkoposti'],
      'field_more_information_link' => $links,
      'field_telephone' => $telephones,
      'field_address' => $address
    ]);

    // Saving original the node.
    $node->save();

    // Translate entity.
    $this->client->httpclientserviceTranslateEntity($node, $data, $this->type);
  }

  /**
   * Convert API phone number data into phone number value.
   */
  public function httpclientserviceConvertPhoneValue($data) {
    $telephones = [];

    if (isset($data['puhelinnumerot'])) {
      foreach ($data['puhelinnumerot'] as $key => $phone) {
        // Merge country code + phone number into one value.
        $telephones[$key]['value'] = '+' . $phone['maakoodi'] . $phone['numero'];
      }
    }

    return $telephones;
  }

  /**
   * Convert API address data into address field.
   */
  public function httpclientserviceGetAddressValue($data, $langcode) {
    $address = (isset($data['fyysinenPaikka']['osoitteet'][0])) ? $data['fyysinenPaikka']['osoitteet'][0] : NULL;

    if (!$address) {
      return [];
    }

    return [
      'country_code' => 'FI',
      'address_line1' => $address['katuosoite_' . $langcode],
      'locality' => $address['postitoimipaikka_' . $langcode],
      'postal_code' => $address['postinumero']
    ];
  }

  /**
   * Convert API more information data into link field value.
   */
  public function httpclientserviceConvertMoreInformationValue($data, $langcode) {
    $links = [];

    if (isset($data['lisatiedot'])) {
      foreach ($data['lisatiedot'] as $key => $link) {
        // Check that description field include value.
        if (empty($link['kuvaus_kieliversiot'][$langcode])) {
          continue;
        }

        $title = (!empty($link['nimi_kieliversiot'][$langcode])) ? $link['nimi_kieliversiot'][$langcode] : $link['kuvaus_kieliversiot'][$langcode];

        $links[$key] = [
          'uri' => $link['kuvaus_kieliversiot'][$langcode],
          'title' => $title,
          'options' => [
            'attributes' => [
              'target' => '_blank',
            ]
          ]
        ];
      }
    }

    return $links;
  }

}
