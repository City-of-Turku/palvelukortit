<?php

namespace Drupal\httpclientservice\GenerateContent;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\time_field\Time;
use Drupal\httpclientservice\GenerateContent\ClientService;
use Drupal\httpclientservice\GenerateContent\ApiUser;

/**
 * Service offer type controller.
 */
class Palvelutarjous {

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

      // Check if Service offer already exist.
      if (!$id = $this->client->httpclientserviceCheckExist($code, $this->type, $this->client->getDefaultLanguage())) {

        // Check if default language version has title. If not, search for other
        // languages and create the node with an existing language.
        $langcode = $this->client->retrieveOriginalLanguageTitle($palvelutarjous, $code, $this->type);

        // Create Service offer node.
        if ($langcode) {
          $this->httpclientserviceCreatePalvelutarjous($palvelutarjous, $langcode);
        }
      }
      else {
        $this->httpclientserviceUpdatePalvelutarjous($id, $palvelutarjous, $this->client->getDefaultLanguage());
      }
    }
  }

  /**
   * Save One Service offers.
   */
  public function httpclientserviceSavePalvelutarjousDev($id = 205) {
    $palvelutarjoukset = $this->httpclientserviceGetPalvelutarjoukset();
    $palvelutarjous = $palvelutarjoukset[$id];
    $code = $palvelutarjous['koodi'];
    $id = $this->client->httpclientserviceCheckExist($code, $this->type, 'fi');
    // Create Customer Services.
    $this->httpclientserviceCreatePalvelutarjous($palvelutarjous, 'fi');
    // Update customer service.
    /* $this->httpclientserviceUpdatePalvelutarjous($id,$palvelutarjous); */
  }

  /**
   * Create Services Offer from data.
   */
  public function httpclientserviceCreatePalvelutarjous($data, $langcode) {
    // Get APi user uid which create node.
    $uid = new ApiUser();
    $titles = $data['nimi_kieliversiot'];

    // Convert change date value from APi to Drupal date time.
    $date = $this->client->httpclientserviceConvertTimeStamp($data['muutospvm']);

    // Convert palvelupiste data into Drupal field.
    if (isset($data['palvelupiste'])) {
      $service_reference = $this->httpclientserviceCreateCustomerServiceReferences($data['palvelupiste']['koodi'], $langcode);
    }

    // Create and get opening hours paragraph data.
    $opening_hours = $this->httpclientserviceCreateOpenHourParagraph($data);

    // Create and get pricing paragraph data.
    $pricing = $this->httpclientserviceCreatePricingParagraph($data);

    // Create node.
    $node = Node::create([
      // The node entity bundle in this case article.
      'type' => $this->type,
      'langcode' => $langcode,
      'created' => \Drupal::time()->getRequestTime(),
      'changed' => \Drupal::time()->getRequestTime(),
      'uid' => $uid->getApiUser(),
      'title' => $titles[$langcode],
      'field_code' => $data['koodi'],
      'field_updated_date' => $date,
      'field_service_terms' => isset($data['palvelunsaanninEhdot_kieliversiot'][$langcode]) ? $data['palvelunsaanninEhdot_kieliversiot'][$langcode] : '',
      'field_customer_service_reference' => $service_reference,
      'field_opening_hours_reference' => $opening_hours,
      'field_pricing_reference' => $pricing,
    ]);

    // Set service offers.
    if (!empty($service_reference)) {
      $node->set('field_customer_service_reference', $service_reference);
    }

    // Saving original the node.
    $node->save();

    $this->client->httpclientserviceTranslateEntity($node, $data, $this->type, $langcode);
  }

  /**
   * Update Services Offer from data.
   */
  public function httpclientserviceUpdatePalvelutarjous($nid, $data, $langcode = 'fi') {
    $node = Node::load($nid);
    // $node->field_opening_hours_reference
    $this->client->httpclientserviceDeleteParagraph($node->field_opening_hours_reference);
    // $node->field_pricing_reference
    $this->client->httpclientserviceDeleteParagraph($node->field_pricing_reference);
    // Convert change date value from APi to Drupal date time.
    $date = $this->client->httpclientserviceConvertTimeStamp($data['muutospvm']);
    // Convert palvelupiste data into Drupal field.
    $service_reference = $this->httpclientserviceCreateCustomerServiceReferences($data['palvelupiste']['koodi']);
    // Create and get opening hours paragraph data.
    $opening_hours = $this->httpclientserviceCreateOpenHourParagraph($data);
    // Create and get pricing paragraph data.
    $pricing = $this->httpclientserviceCreatePricingParagraph($data);

    $node->set('created', \Drupal::time()->getRequestTime());
    $node->set('changed', \Drupal::time()->getRequestTime());
    $node->set('title', $data['nimi_kieliversiot'][$langcode]);
    $node->set('field_updated_date', $date);
    $node->set('field_terms', $data['palvelunsaanninEhdot_kieliversiot'][$langcode]);
    $node->set('field_customer_service_reference', $service_reference);
    $node->set('field_opening_hours_reference', $opening_hours);
    $node->set('field_pricing_reference', $pricing);

    // Saving original the node.
    $node->save();

    $this->client->httpclientserviceTranslateEntity($node, $data, $this->type, $langcode);
  }

  /**
   * Create Opening Hours paragraph.
   */
  public function httpclientserviceCreateOpenHourParagraph($data, $langcode = 'fi') {
    $openinghours = [];

    foreach ($data['aukioloajat'] as $open) {
      // Convert opening hours into Drupal time field.
      $opening_start = (!empty($open['avaamisaika'])) ? $this->client->httpclientserviceConvertTimeStamp($open['avaamisaika']) : NULL;
      $opening_end = (!empty($open['sulkemisaika'])) ? $this->client->httpclientserviceConvertTimeStamp($open['sulkemisaika']) : NULL;

      $date_opening_start = DrupalDateTime::createFromTimestamp($opening_start, 'UTC');
      $date_opening_start = new Time($date_opening_start->format('H'), $date_opening_start->format('i'));
      $date_opening_end = DrupalDateTime::createFromTimestamp($opening_end, 'UTC');
      $date_opening_end = new Time($date_opening_end->format('H'), $date_opening_end->format('i'));

      $opening = [
        'from' => $date_opening_start->getTimestamp(),
        'to' => $date_opening_end->getTimestamp()
      ];

      // Convert validity into Drupal date field.
      $validity_start = (!empty($open['voimassaoloAlkamishetki'])) ? $this->client->httpclientserviceConvertTimeStamp($open['voimassaoloAlkamishetki']) : NULL;
      $validity_end = (!empty($open['voimassaoloPaattymishetki'])) ? $this->client->httpclientserviceConvertTimeStamp($open['voimassaoloPaattymishetki']) : NULL;

      $validity = [
                    'value' => $validity_start,
                    'end_value' => $validity_end
                  ];

      $paragraph = Paragraph::create([
        'type' => 'opening_hours',
        'field_opening_type' => strtolower($open['aukiolotyyppi']),
        'field_opening_description' => $open['kuvaus_kieliversiot'][$langcode],
        'field_opening_hours' => $opening,
        'field_validity' => $validity,
        'field_week_day' => $open['viikonpaiva']
      ]);

      $paragraph->isNew();
      $paragraph->save();

      $openinghours[] = $paragraph;
    }

    return $openinghours;
  }

  /**
   * Translate Opening Hours paragraph.
   */
  public function httpclientserviceTranslateOpenHourParagraph($paragraphs, $data, $langcode = 'fi') {
    $openinghours = [];
    $paragraphdata = $paragraphs->referencedEntities();

    foreach ($paragraphdata as $key => $paragraph) {
      $translation = [
        'field_opening_type' => strtolower($data['aukioloajat'][$key]['aukiolotyyppi']),
        'field_opening_description' => strtolower($data['aukioloajat'][$key]['kuvaus_kieliversiot'][$langcode])
      ];

      $paragraph->addTranslation($langcode, $translation);
      $paragraph->save();

      $openinghours[] = $paragraph;
    }

    return $openinghours;
  }

  /**
   * Create Pricing paragraph.
   */
  public function httpclientserviceCreatePricingParagraph($data, $langcode = 'fi') {
    $pricing = [];

    foreach ($data['hinnat'] as $price) {
      $paragraph = Paragraph::create([
        'type' => 'pricing',
        'field_pricing_type' => strtolower($price['hinnoittelutyyppi']),
        'field_pricing_description' => $price['kuvaus_kieliversiot'][$langcode],
        'field_pricing' => $price['hinta_kieliversiot'][$langcode]
      ]);

      $paragraph->isNew();
      $paragraph->save();

      $pricing[] = $paragraph;
    }

    return $pricing;
  }

  /**
   * Translate Pricing paragraph.
   */
  public function httpclientserviceTranslatePricingParagraph($paragraphs, $data, $langcode = 'fi') {
    $pricing = [];
    $paragraphdata = $paragraphs->referencedEntities();

    foreach ($paragraphdata as $key => $paragraph) {
      $pricing_default = $data['hinnat'][$key]['hinta_kieliversiot']['fi'];
      $pricing_text = (isset($data['hinnat'][$key]['hinta_kieliversiot'][$langcode])) ? $data['hinnat'][$key]['hinta_kieliversiot'][$langcode] : $pricing_default;

      $desc_default = $data['hinnat'][$key]['kuvaus_kieliversiot']['fi'];
      $desc = (isset($data['hinnat'][$key]['kuvaus_kieliversiot'][$langcode])) ? $data['hinnat'][$key]['kuvaus_kieliversiot'][$langcode] : $desc_default;

      $translation = [
        'field_pricing_type' => strtolower($data['hinnat'][$key]['hinnoittelutyyppi']),
        'field_pricing_description' => $pricing_text,
        'field_pricing' => $desc
      ];

      $paragraph->addTranslation($langcode, $translation);
      $paragraph->save();

      $pricing[] = $paragraph;
    }

    return $pricing;
  }

  /**
   * Convert service offer data to Drupal Service offers reference value.
   */
  public function httpclientserviceCreateCustomerServiceReferences($id, $langcode) {
    $service_references = [];

    $query = \Drupal::entityQuery('node')
      ->condition('type', 'customer_service')
      ->condition('langcode', $langcode)
      ->condition('field_code', $id);

    if ($result = $query->execute()) {
      $service_references = reset($result);
    }

    return $service_references;
  }

}
