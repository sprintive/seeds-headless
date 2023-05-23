<?php

namespace Drupal\seeds_headless_helper\Plugin\jsonapi\FieldEnhancer;

use Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerBase;
use Drupal\options\Plugin\Field\FieldType\ListItemBase;
use Drupal\options\Plugin\Field\FieldType\ListStringItem;
use Shaper\Util\Context;

/**
 * Perform additional manipulations to the enhanced value.
 *
 * @ResourceFieldEnhancer(
 *   id = "string_list_label",
 *   label = @Translation("String List Label Field"),
 *   description = @Translation("Render the label of string_list field value")
 * )
 */
class StringListLabel extends ResourceFieldEnhancerBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function doUndoTransform($data, Context $context) {
    $field_item = $context['field_item_object'] ?? NULL;
    if($field_item instanceof ListItemBase) {
      $allowed_values = $field_item->getFieldDefinition()->getSetting('allowed_values');
      $data = isset($allowed_values[$data])?$allowed_values[$data]:$data;
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  protected function doTransform($data, Context $context) {
    $field_item = $context['field_item_object'] ?? NULL;
    if($field_item instanceof ListItemBase) {
      $allowed_values = $field_item->getFieldDefinition()->getSetting('allowed_values');
      // Find the key which matches the value.
      $search = array_search($data, $allowed_values) !== FALSE;
      if($search !== FALSE) {
        $data = array_search($data, $allowed_values);
      }
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getOutputJsonSchema() {
    return [
      'oneOf' => [
        ['type' => 'string'],
        ['type' => 'null'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $resource_field_info) {
    return [];
  }

}
