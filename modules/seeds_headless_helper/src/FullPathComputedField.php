<?php

namespace Drupal\seeds_headless_helper;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * Full path computed field.
 */
class FullPathComputedField extends FieldItemList {
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
    // Get the absolute url.
    if ($entity->hasLinkTemplate('canonical')) {
      $url = $entity->toUrl('canonical')->toString();
      $item = $this->createItem(0, $url);
      $this->list[0] = $item;
    }
  }

}
