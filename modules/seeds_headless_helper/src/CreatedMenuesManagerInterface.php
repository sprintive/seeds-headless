<?php

namespace Drupal\seeds_headless_helper;

/**
 * Interface CreatedMenuesManager.
 */
interface CreatedMenuesManagerInterface {

  /**
   * Creates a new menu for an entity.
   *
   * @param \Drupal\seeds_headless_helper\EntityWithMenuItemsInterface $entity
   *   The entity.
   *
   * @return \Drupal\system\MenuInterface
   *   The menu.
   */
  public function createMenu(EntityWithMenuItemsInterface $entity);

  /**
   * Removes a menu from an entity.
   *
   * @param \Drupal\seeds_headless_helper\EntityWithMenuItemsInterface $entity
   *   The entity.
   */
  public function removeMenu(EntityWithMenuItemsInterface $entity);

  /**
   * Gets the menu of an entity.
   *
   * @param \Drupal\seeds_headless_helper\EntityWithMenuItemsInterface $entity
   *   The entity.
   *
   * @return \Drupal\system\MenuInterface|null
   *   The menu.
   */
  public function getMenu(EntityWithMenuItemsInterface $entity);

  /**
   * Lods the parent node.
   *
   * @param string $node_id
   *   The node id.
   *
   * @return \Drupal\node\NodeInterface|null
   *   The node.
   */
  public function loadParentNode($node_id);

}
