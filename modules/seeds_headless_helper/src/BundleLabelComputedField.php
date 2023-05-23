<?php

namespace Drupal\seeds_headless_helper;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * Bundle label computed field.
 */
class BundleLabelComputedField extends FieldItemList {
  use ComputedItemListTrait;
  use SeedsComputedFieldTrait;

  /**
   * {@inheritDoc}
   */
  public function access($operation = 'view', ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    $entity = $this->getEntity();

    // If the entity is new, return.
    if ($entity->isNew()) {
      return;
    }

    $entity = $this->prepareEntity($entity);
    $bundle = $entity->bundle();
    // Load node bundle definition.
    $bundle_definition = \Drupal::entityTypeManager()->getStorage('node_type')->load($bundle);
    // If it exists, load label.
    if ($bundle_definition) {
      $bundle_label = $bundle_definition->label();
      return $this->list[0] = $this->createItem(0, $bundle_label);
    }
    else {
      // NULL.
      return $this->list[0] = $this->createItem(0, NULL);
    }
  }

}
