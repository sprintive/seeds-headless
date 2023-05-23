<?php

namespace Drupal\seeds_headless_helper;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;

/**
 * Seeds nextjs cache invalidator.
 */
class SeedsNextJsCacheTagsInvalidator implements CacheTagsInvalidatorInterface {

  /**
   * {@inheritdoc}
   */
  public function invalidateTags(array $tags) {
    // Do nothing.
    $tags_to_invalidate = &drupal_static('seeds_nextjs_tags_to_invalidate', []);
    $tags_to_invalidate = array_merge($tags_to_invalidate, $tags);
  }

}
