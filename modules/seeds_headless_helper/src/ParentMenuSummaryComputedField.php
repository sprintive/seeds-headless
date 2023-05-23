<?php

namespace Drupal\seeds_headless_helper;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\node\NodeInterface;
use Drupal\seeds_headless_helper\SeedsComputedFieldTrait;

/**
 * The menu items computed fields.
 */
class ParentMenuSummaryComputedField extends FieldItemList {
  use ComputedItemListTrait;
  use EntityWithMenuItemsTrait;
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

    // Check if the entity has layout.
    if ($entity->isNew() || !($entity instanceof NodeInterface) || !($entity instanceof EntityWithMenuItemsInterface)) {
      return;
    }

    /** @var EntityWithMenuItemsInterface&NodeInterface $entity */
    $entity = $this->prepareEntity($entity);

    $parent_menu_summary = NULL;
    if ($entity->isMenuShown() || $entity->withCreatedMenu()) {
      $parent_menu_summary = $entity->getSummary();
    }
    else {
      $parent_node = $this->loadParentNode($entity->id());
      if ($parent_node instanceof EntityWithMenuItemsInterface && ($parent_node->isMenuShown() || $parent_node->withCreatedMenu())) {
        $parent_menu_summary = $parent_node->getSummary();
      }
    }

    $this->list[0] = $this->createItem(0, $parent_menu_summary);
  }

}
