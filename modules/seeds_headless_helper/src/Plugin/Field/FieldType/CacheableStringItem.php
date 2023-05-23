<?php

namespace Drupal\seeds_headless_helper\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\StringLongItem;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'string' entity field type with cacheability metadata.
 *
 * @FieldType(
 *   id = "string_cacheable",
 *   label = @Translation("Cacheable Text (plain)"),
 *   description = @Translation("A test field containing a plain string value and cacheability metadata."),
 *   category = @Translation("Text"),
 *   no_ui = TRUE,
 *   default_widget = "string_textfield",
 *   default_formatter = "string"
 * )
 */
class CacheableStringItem extends StringLongItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('cacheable_string')
      ->setLabel(new TranslatableMarkup('Text value'))
      ->setSetting('case_sensitive', $field_definition->getSetting('case_sensitive'))
      ->setRequired(TRUE);

    return $properties;
  }

}
