<?php

namespace Drupal\seeds_headless_helper;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\menu_link_content\MenuLinkContentInterface;

/**
 * Trait of an entity with menu items.
 */
trait EntityWithMenuItemsTrait {

  use SeedsComputedFieldTrait;

  /**
   * Get the created menues manager.
   *
   * @return \Drupal\seeds_headless_helper\CreatedMenuesManagerInterface
   */
  protected function createdMenuesManager() {
    return \Drupal::service('seeds_headless_helper.created_menues_manager');
  }

  /**
   * Gets the menu link.
   *
   * @param string $node_id
   *   The node id.
   *
   * @return \Drupal\menu_link_content\MenuLinkContentInterface|\Drupal\menu_link_content\MenuLinkContentInterface[]|null
   *   The menu link.
   */
  public function loadMenuItemOfNode($node_id) {
    /** @var \Drupal\menu_link_content\MenuLinkContentInterface $menu_items */
    $menu_items = \Drupal::entityTypeManager()
      ->getStorage('menu_link_content')
      ->loadByProperties([
        'link__uri' => 'entity:node/' . $node_id,
      ]);

    if (empty($menu_items)) {
      return NULL;
    }

    $priority = function ($menu_name) {
      if (strpos($menu_name, 'entity-menu-')) {
        return 3;
      }
      elseif ($menu_name === 'main') {
        return 2;
      }
      else {
        return 1;
      }
    };

    $menu_item = NULL;
    // If there is more than one item, prioritize created menues, then main.
    if (is_array($menu_items)) {
      foreach ($menu_items as $found_menu_item) {
        $menu_name = $found_menu_item->getMenuName();
        if (!$menu_item) {
          $menu_item = $found_menu_item;
        }
        // Else if meuu_name contains entity_menu_*, this means it is created.
        elseif ($priority($menu_name) > $priority($menu_item->getMenuName())) {
          $menu_item = $found_menu_item;
        }
      }
    }
    else {
      $menu_item = $menu_items;
    }

    return $menu_item;
  }

  /**
   * Gets the parent menu link.
   *
   * @param string $node_id
   *   The node id.
   *
   * @return \Drupal\node\NodeInterface|null
   *   The menu link.
   */
  public function loadParentNode($node_id) {
    $menu_item = $this->loadMenuItemOfNode($node_id);
    $menu_item = $menu_item ? $this->prepareEntity($menu_item) : NULL;
    $node = NULL;
    // If the menu is created, get the node from the menu.
    // The regex is 'entity_menu_node_NODEID.
    if ($menu_item && preg_match('/entity-menu-node-(\d+)/', $menu_item->getMenuName(), $matches)) {
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($matches[1]);
    }

    $parent_menu_item = $this->loadParentMenuItemOfNode($node_id);
    $parent_menu_item = $parent_menu_item ? $this->prepareEntity($parent_menu_item) : NULL;
    if ($parent_menu_item) {
      $uri = $parent_menu_item->get('link')->uri;
      // URI is like 'entity:node/NUMBER', get the number using regex.
      if (preg_match('/\d+/', $uri, $matches)) {
        $parent_node_id = $matches[0];
        $parent_node = \Drupal::entityTypeManager()
          ->getStorage('node')
          ->load($parent_node_id);
        $node = $parent_node;
      }
    }

    return $this->prepareEntity($node);
  }

  /**
   * Gets the menu link.
   *
   * @param string $node_id
   *   The node id.
   *
   * @return \Drupal\menu_link_content\MenuLinkContentInterface|null
   *   The menu link.
   */
  public function loadParentMenuItemOfNode($node_id) {
    $menu_item = $this->loadMenuItemOfNode($node_id);
    if ($menu_item) {
      $uuid = NULL;
      $parent_id = $menu_item->getParentId();
      // Get uuid from parent_id.
      if ($parent_id) {
        $uuid = explode(':', $parent_id)[1];
      }
      if ($uuid) {
        // Load menu item by uuid.
        $menu_items = \Drupal::entityTypeManager()
          ->getStorage('menu_link_content')
          ->loadByProperties([
            'uuid' => $uuid,
          ]);
        $found = reset($menu_items);
        $found = $this->prepareEntity($found);
        return reset($menu_items);
      }
    }

    return NULL;
  }

  /**
   * @param \Drupal\menu_link_content\MenuLinkContentInterface $menu_item
   *   The menu item.
   *
   * @return array
   *   The serialized menu items.
   */
  protected function getChildMenuItems(MenuLinkContentInterface $menu_item, RefinableCacheableDependencyInterface $cache_metadata = NULL) {
    $parent_prop = "menu_link_content:{$menu_item->uuid()}";
    /** @var \Drupal\menu_link_content\MenuLinkContentInterface[] $menu_items */
    $menu_items = \Drupal::entityTypeManager()
      ->getStorage('menu_link_content')
      ->loadByProperties([
        'parent' => $parent_prop,
      ]);

    $serailized = [];
    foreach ($menu_items as $item) {
      if ($cache_metadata) {
        $cache_metadata->addCacheableDependency($item);
      }
      $serailized[] = $this->serializeMenuItem($item);
    }

    return $serailized;
  }

  /**
   * Return serialized menu items.
   *
   * @param EntityWithMenuItemsInterface $entity
   *   The entity.
   *
   * @return array
   *   The serialized menu items.
   */
  protected function loadMenuItemsOfEntityWithMenu(EntityWithMenuItemsInterface $entity, RefinableCacheableDependencyInterface $cache_metadata = NULL) {
    $menu = $this->createdMenuesManager()->getMenu($entity);
    $serailzied = [];
    if ($menu) {

      // Add the entity as well.
      $serailzied[] = [
        'id' => $entity->uuid(),
        'description' => $entity->label(),
        'meta' => [],
        'options' => [],
        'parent' => '',
        'provider' => '',
        'title' => $entity->label(),
        'url' => $entity->toUrl()->toString(),
      ];

      if ($cache_metadata) {
        $cache_metadata->addCacheableDependency($menu);
      }

      $menu_items = \Drupal::entityTypeManager()
        ->getStorage('menu_link_content')
        ->loadByProperties([
          'menu_name' => $menu->id(),
        ]);

      foreach ($menu_items as $item) {
        $item = $this->prepareEntity($item);
        if ($cache_metadata) {
          $cache_metadata->addCacheableDependency($item);
        }
        $serailzied[] = $this->serializeMenuItem($item);
      }

    }
    return $serailzied;
  }

  /**
   * Serialize a menu item.
   *
   * @param \Drupal\menu_link_content\MenuLinkContentInterface $menu_item
   *   The menu item.
   *
   * @return array
   *   The serialized menu item.
   */
  protected function serializeMenuItem(MenuLinkContentInterface $item) {
    /** @var \Drupal\menu_link_content\MenuLinkContentInterface $item */
    $item = $this->prepareEntity($item);
    return [
      'id' => $item->uuid(),
      'description' => $item->getDescription(),
      'meta' => $item->getPluginDefinition()['metadata'] ?? [],
      'options' => [],
      'parent' => $item->getParentId(),
      'provider' => '',
      'title' => $item->getTitle(),
      'url' => $item->getUrlObject()->toString(),
      'expanded' => FALSE,
      'enabled' => $item->isEnabled(),
      'weight' => $item->getWeight(),
      'items' => [],
    ];
  }

}
