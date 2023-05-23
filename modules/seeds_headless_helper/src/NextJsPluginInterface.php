<?php

namespace Drupal\seeds_headless_helper;

/**
 * Interface for defining NextJs compatible plugins.
 */
interface NextJsPluginInterface {

  /**
   * Gets the properties of the block.
   *
   * @return array|null
   *   The properties of the block.
   */
  public function getProperties();

}
