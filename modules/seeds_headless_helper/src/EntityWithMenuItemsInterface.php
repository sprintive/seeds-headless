<?php

namespace Drupal\seeds_headless_helper;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface for entities with menu items.
 */
interface EntityWithMenuItemsInterface extends ContentEntityInterface {

  /**
   * Check if the menu will be showing.
   *
   * @return bool
   *   TRUE if the menu will be showing.
   */
  public function isMenuShown();

  /**
   * Gets the summary of the node.
   *
   * @return string
   *   The summary of the node.
   */
  public function getSummary();

  /**
   * Check if the menu will be created.
   *
   * @return bool
   *   TRUE if the menu will be created.
   */
  public function withCreatedMenu();

  /**
   * Gets the page url.
   *
   * @return string
   *   The page url.
   */
  public function getPageUrl();

}
