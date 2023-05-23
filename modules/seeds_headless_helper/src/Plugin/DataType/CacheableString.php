<?php

namespace Drupal\seeds_headless_helper\Plugin\DataType;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyTrait;
use Drupal\Core\TypedData\Plugin\DataType\StringData;

/**
 * The string data type with cacheability metadata.
 *
 * The plain value of a string is a regular PHP string. For setting the value
 * any PHP variable that casts to a string may be passed.
 *
 * @DataType(
 *   id = "cacheable_string",
 *   label = @Translation("Cacheable String")
 * )
 */
class CacheableString extends StringData implements RefinableCacheableDependencyInterface {

  use RefinableCacheableDependencyTrait;

}
