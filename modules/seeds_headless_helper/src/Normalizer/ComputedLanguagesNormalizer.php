<?php

namespace Drupal\seeds_headless_helper\Normalizer;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\TypedData\Plugin\DataType\StringData;
use Drupal\seeds_headless_helper\JSONComputedFieldInterface;
use Drupal\serialization\Normalizer\NormalizerBase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * The computed languages normalizer.
 */
class ComputedLanguagesNormalizer extends NormalizerBase implements NormalizerInterface {

  /**
   * {@inheritDoc}
   */
  protected $supportedInterfaceOrClass = FieldItemListInterface::class;

  /**
   * {@inheritDoc}
   */
  public function supportsNormalization($data, $format = NULL,$context = []): bool {
    if ($data instanceof StringData) {
      return $data->getParent()->getParent() instanceof JSONComputedFieldInterface;
    }

    return FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    if ($object instanceof CacheableDependencyInterface) {
      $cacheability = $context["cacheability"] ?? NULL;
      if ($cacheability instanceof CacheableMetadata) {
        $cacheability->addCacheableDependency($object);
      }
    }
    /** @var \Drupal\Core\TypedData\Plugin\DataType\StringData $object */
    return json_decode($object->getValue(), TRUE);
  }

}
