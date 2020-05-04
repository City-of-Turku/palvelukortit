<?php

namespace Drupal\pako_content\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\node\Entity\Node;

/**
 * Provides a 'ServiceCardNavigationBlock' block.
 *
 * @Block(
 *  id = "service_card_navigation_block",
 *  admin_label = @Translation("Service card - Navigation Block"),
 * )
 */
class ServiceCardNavigationBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    if ($node = \Drupal::routeMatch()->getParameter('node')) {
      if ($node instanceof Node && $node->getType() == 'service_card') {
        $config = \Drupal::service('config.factory')
          ->get('pako_content.service_card_navigation')
          ->get('pako_fields');
        $fields = reset($config);

        foreach ($fields as $icon => $field) {
          if ($node->hasField($field) && !$node->get($field)->isEmpty()) {
            $build['#items'][] = [
              '#label' => $node->get($field)->getFieldDefinition()->getLabel(),
              '#label_icon' => $icon,
              '#theme' => 'pako_content_service_card_navigation_field',
            ];
          }
        }
        $build['#theme'] = 'pako_content_service_card_navigation';
      }
    }
    return $build;
  }

}
