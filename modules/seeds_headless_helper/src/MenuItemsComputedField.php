<?php

namespace Drupal\seeds_headless_helper;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\node\NodeInterface;
use Drupal\seeds_headless_helper\JSONComputedFieldInterface;
use Drupal\seeds_headless_helper\SeedsComputedFieldTrait;

/**
 * The menu items computed fields.
 */
class MenuItemsComputedField extends FieldItemList implements JSONComputedFieldInterface {
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
    $cache_metadata = new CacheableMetadata();

    // Check if the entity has layout.
    if ($entity->isNew() || !($entity instanceof NodeInterface) || !($entity instanceof EntityWithMenuItemsInterface)) {
      return;
    }

    /** @var EntityWithMenuItemsInterface&NodeInterface $entity */
    $entity = $this->prepareEntity($entity);

    $serialized = [];

    // If the menu is created, get the menu items of the created menu.
    if ($entity->withCreatedMenu()) {
      $serialized = $this->loadMenuItemsOfEntityWithMenu($entity, $cache_metadata);
    }

    // If the menu is shown, get the children menu items, otherwise get the children of the parent item.
    elseif ($entity->isMenuShown()) {
      $menu_item = $this->loadMenuItemOfNode($entity->id());
      if ($menu_item) {
        $cache_metadata->addCacheableDependency($menu_item);
        // Load the children.
        $serialized = $this->getChildMenuItems($menu_item, $cache_metadata);
        // Add the entity link as well at the first item.
        array_unshift($serialized, $this->serializeMenuItem($menu_item, $cache_metadata));

      }
    }
    else {
      $parent_node = $this->loadParentNode($entity->id());
      $cache_metadata->addCacheableDependency($parent_node);
      if ($parent_node instanceof EntityWithMenuItemsInterface) {
        if ($parent_node->withCreatedMenu()) {
          $serialized = $this->loadMenuItemsOfEntityWithMenu($parent_node, $cache_metadata);
        }
        elseif ($parent_node->isMenuShown()) {
          $parent_menu_item = $this->loadParentMenuItemOfNode($entity->id());
          if ($parent_menu_item) {
            
            $cache_metadata->addCacheableDependency($parent_menu_item);
            $menu_name = $parent_menu_item->getMenuName();
            // Add menu name as cache tag.
            $cache_metadata->addCacheTags(['config:system.menu.' . $menu_name]);
            $serialized = $this->getChildMenuItems($parent_menu_item, $cache_metadata);
            // Add the entity link as well.
            array_unshift($serialized, $this->serializeMenuItem($parent_menu_item, $cache_metadata));
          }
        }
      }
    }
    $cache_metadata->addCacheTags(['route_match']);

    /** @var \Drupal\seeds_headless_helper\Plugin\Field\FieldType\CacheableStringItem $item */
    $item = $this->createItem(0, json_encode([
      'items' => $serialized,
      'cache_tags' => $cache_metadata->getCacheTags(),
    ]));
    /** @var \Drupal\Core\Cache\RefinableCacheableDependencyInterface $property */
    $property = $item->get('value');
    $property->addCacheableDependency($cache_metadata);
    $this->list[0] = $item;
  }

}
