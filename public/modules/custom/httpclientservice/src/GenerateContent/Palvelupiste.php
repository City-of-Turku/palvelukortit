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
    $changeday = $this->client->httpclientserviceGetChangedate();
    $data = $this->client->httpclientserviceGetService('api/v1/palvelupisteet', $query = [], $changeday);

    return $data;
  }

  /**
   * Save Customer Services to Drupal.
   */
  public function httpclientserviceSavePalvelupisteet() {
    // Get custom service content type data from API.
    $palvelupisteet = $this->httpclientserviceGetPalvelupisteet();

    if (!$palvelupisteet) {
      return;
    }

    foreach ($palvelupisteet as $palvelupiste) {
      // Customer services code value aka id.
      $code = $palvelupiste['koodi'];

      // Check if Customer service node already exist.
      if (!$id = $this->client->httpclientserviceCheckExist($code, $this->type, $this->client->getDefaultLanguage())) {

        // Check if default language version has title. If not, search for other
        // languages and create the node with an existing language.
        $langcode = $this->client->retrieveOriginalLanguageTitle($palvelupiste, $code, $this->type);

        // Create Customer service node.
        if ($langcode) {
          $this->httpclientserviceCreatePalvelupiste($palvelupiste, $langcode);
        }
      }
      else {
        // Check if default language version has title. If not, search for other
        // languages and create the node with an existing language.
        $langcode = $this->client->retrieveOriginalLanguageTitle($palvelupiste, $code, $this->type);

        // Create Customer service node.
        if ($langcode) {
          $this->httpclientserviceUpdatePalvelupiste($id, $palvelupiste, $langcode);
        }
      }
    }
  }

  /**
   * Save One Customer Services.
   */
  public function httpclientserviceSavePalvelupisteDev($id = 1453) {
    $palvelupisteet = $this->httpclientserviceGetPalvelupisteet();
    $palvelupiste = $palvelupisteet[$id];
    // Create Customer Services.
    $this->httpclientserviceCreatePalvelupiste($palvelupiste, 'fi');
  }

  /**
   * Create Customer Services from data.
   */
  public function httpclientserviceCreatePalvelupiste($data, $langcode) {
    // Get APi user uid which create node.
    $uid = new ApiUser();
    $titles = $data['nimi_kieliversiot'];
    $descriptions = isset($data['kuvaus_kieliversiot']) ? $data['kuvaus_kieliversiot'] : [];
    $status = ($data['tila']['koodi'] == '1') ? 1 : 0;

    // Convert change date value from APi to Drupal date time.
    $dateTime = new DrupalDateTime($data['muutospvm'], 'UTC');
    $date = $dateTime->getTimestamp();

    // Convert more information value into Drupal fields.
    $links = $this->httpclientserviceConvertMoreInformationValue($data, $langcode);

    // Convert telephone data into Drupal field.
    $telephones = $this->httpclientserviceConvertPhoneValue($data);

    // Address information.
    $address = $this->httpclientserviceGetAddressValue($data, $langcode);

    // Create node.
    $node = Node::create([
      // The node entity bundle in this case article.
      'type' => $this->type,
      'langcode' => $langcode,
      'created' => \Drupal::time()->getRequestTime(),
      'changed' => \Drupal::time()->getRequestTime(),
      'uid' => $uid->getApiUser(),
      'status' => $status,
      'title' => $titles[$langcode],
      'field_code' => $data['koodi'],
      'field_updated_date' => $date,
      'field_telephone' => $telephones,
      'field_address' => $address
    ]);

    // Set description.
    if (isset($descriptions[$langcode])) {
      $node->set('field_description', $descriptions[$langcode]);
    }

    // Set more information link.
    if (!empty($links)) {
      $node->set('field_more_information_link', $links);
    }

    // Set email.
    if (isset($data['sahkoposti'])) {
      $node->set('field_email', $data['sahkoposti']);
    }

    // Saving original the node.
    $node->save();

    // Translate entity.
    $this->client->httpclientserviceTranslateEntity($node, $data, $this->type, $langcode);
  }

  /**
   * Update Customer Services from data.
   */
  public function httpclientserviceUpdatePalvelupiste($nid, $data, $langcode) {
    $node = Node::load($nid);
    $titles = $data['nimi_kieliversiot'];
    $descriptions = isset($data['kuvaus_kieliversiot']) ? $data['kuvaus_kieliversiot'] : [];
    $status = ($data['tila']['koodi'] == '1') ? 1 : 0;

    // Convert change date value from APi to Drupal date time.
    $date = $this->client->httpclientserviceConvertTimeStamp($data['muutospvm']);

    // Convert more information value into Drupal fields.
    $links = $this->httpclientserviceConvertMoreInformationValue($data, $langcode);

    // Convert telephone data into Drupal field.
    $telephones = $this->httpclientserviceConvertPhoneValue($data);

    // Address information.
    $address = $this->httpclientserviceGetAddressValue($data, $langcode);

    $node->set('changed', \Drupal::time()->getRequestTime());
    $node->set('title', $data['nimi_kieliversiot'][$langcode]);
    $node->set('status', $status);
    $node->set('field_updated_date', $date);
    $node->set('field_telephone', $telephones);
    $node->set('field_address', $address);

    // Set description.
    if (isset($descriptions[$langcode])) {
      $node->set('field_description', $descriptions[$langcode]);
    }

    // Set more information link.
    if (!empty($links)) {
      $node->set('field_more_information_link', $links);
    }

    // Set email.
    if (isset($data['sahkoposti'])) {
      $node->set('field_email', $data['sahkoposti']);
    }

    // Saving original the node.
    $node->save();

    // Translate entity.
    $this->client->httpclientserviceTranslateEntity($node, $data, $this->type, $langcode);
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

        // Check that description field type is suitable for links (code 6).
        if (
          !array_key_exists('koodi', $link['lisatietotyyppi']) ||
          $link['lisatietotyyppi']['koodi'] != 6
        ) {
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
