<?php

namespace Drupal\seeds_headless_helper;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * CreatedMenuesManager class.
 */
class CreatedMenuesManager implements CreatedMenuesManagerInterface {

  use EntityWithMenuItemsTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new CreatedMenuesManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   *
   * @return void
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Gets the menu id of an entity.
   *
   * @param \Drupal\seeds_nextjs\EntityWithMenuItemsInterface $entity
   *   The entity.
   *
   * @return string
   *   The menu id.
   */
  private function getMenuId(EntityWithMenuItemsInterface $entity) {
    $entity_type_id = $entity->getEntityTypeId();
    $entity_id = $entity->id();
    return 'entity-menu-' . $entity_type_id . '-' . $entity_id;
  }

  /**
   * {@inheritdoc}
   */
  public function createMenu(EntityWithMenuItemsInterface $entity) {
    // Check first if it is created already.
    $menu = $this->getMenu($entity);
    if ($menu) {
      return $menu;
    }

    $menu_id = $this->getMenuId($entity);
    // Create the menu.
    $menu = $this->entityTypeManager->getStorage('menu')->create([
      'id' => $menu_id,
      'label' => $entity->label(),
      'locked' => TRUE,
    ]);
    $menu->save();

    return $menu;
  }

  /**
   * {@inheritdoc}
   */
  public function removeMenu(EntityWithMenuItemsInterface $entity) {
    $menu = $this->getMenu($entity);

    // Delete the menu.
    if ($menu) {
      $menu->delete();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getMenu(EntityWithMenuItemsInterface $entity) {
    $menu_id = $this->getMenuId($entity);
    return $this->entityTypeManager->getStorage('menu')->load($menu_id);
  }

}
