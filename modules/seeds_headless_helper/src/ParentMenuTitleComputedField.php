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
class ParentMenuTitleComputedField extends FieldItemList {
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
    if ($entity->isNew() || !($entity instanceof NodeInterface) ) {
      return;
    }

    /** @var EntityWithMenuItemsInterface&NodeInterface $entity */
    $entity = $this->prepareEntity($entity);

    $parent_node = $this->loadParentNode($entity->id());

    $parent_menu_title = NULL;
    if ($parent_node) {
      $parent_menu_title = $parent_node->getTitle();
    }
    
    $this->list[0] = $this->createItem(0, $parent_menu_title);
  }

}
