<?php

namespace Drupal\seeds_headless_helper;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\TranslatableInterface;

/**
 * Seeds computed field trait.
 */
trait SeedsComputedFieldTrait {

  /**
   * Prepares the current langcode entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The current langcode entity.
   */
  public function prepareEntity($entity = NULL) {
    $current_langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    // If the entity has the current langcode, load it.
    if ($entity instanceof TranslatableInterface && $entity->hasTranslation($current_langcode)) {
      $entity = $entity->getTranslation($current_langcode);
    }

    return $entity;
  }

}
