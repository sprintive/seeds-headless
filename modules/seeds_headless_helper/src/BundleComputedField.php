<?php

namespace Drupal\seeds_headless_helper;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * Bundle label computed field.
 */
class BundleComputedField extends FieldItemList {
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
    $entity = $this->prepareEntity($entity);
    // Return the bundle.
    $bundle = $entity->bundle();
    $this->list[0] = $this->createItem(0, $bundle);
  }

}
