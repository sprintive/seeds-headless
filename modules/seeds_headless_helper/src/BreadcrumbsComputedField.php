<?php
namespace Drupal\seeds_headless_helper;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * The breadcrums computed fields.
 */
class BreadcrumbsComputedField extends FieldItemList implements JSONComputedFieldInterface {
  use ComputedItemListTrait;
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
    if ($entity->isNew() || !($entity instanceof FieldableEntityInterface)) {
      return;
    }

    $entity = $this->prepareEntity($entity);

    // Get url.
    /** @var \Drupal\Core\Url $url */
    $url = $entity->toUrl();
    $url_generated = $url->toString(TRUE);
    $url_string = $url_generated->getGeneratedUrl();
    // Get route context, and set the path info.
    $request_context = \Drupal::service('router.request_context');
    $request_context->setPathInfo($url_string);
    // Get breadcrumb manager and build.
    /** @var \Drupal\Core\Breadcrumb\ChainBreadcrumbBuilderInterface $breadcrumb_manager */
    $breadcrumb_manager = \Drupal::service('breadcrumb');
    // Create a new route match using url.
    $route = \Drupal::service('router.route_provider')->getRouteByName($url->getRouteName());
    $route_match = new RouteMatch($url->getRouteName(), $route, $url->getRouteParameters());
    // Build breadcrumb inside render context.
    $context = new RenderContext();
    /** @var \Drupal\Core\Cache\CacheableDependencyInterface $result */
    $links = \Drupal::service('renderer')->executeInRenderContext($context, function () use ($breadcrumb_manager, $route_match, $cache_metadata, $entity) {
      // Build breadcrumb.
      $breadcrumb = $breadcrumb_manager->build($route_match);
      // Get links.
      $links = [];
      /** @var \Drupal\Core\Link $link */
      foreach ($breadcrumb->getLinks() as $link) {
        $parameters = $link->getUrl()->getRouteParameters();

        // Foreach parameters, key is entity_type, and value is an id, check if it is an entity.
        foreach ($parameters as $key => $value) {
          // Check if entity type exists.
          if (\Drupal::entityTypeManager()->hasDefinition($key)) {
            // Load entity.
            $parameter_entity = \Drupal::entityTypeManager()->getStorage($key)->load($value);
            // If entity exists.
            if ($parameter_entity) {
              // Add cache tags.
              $cache_metadata->addCacheableDependency($parameter_entity);
            }
          }
        }
        $links[] = [
          'text' => $link->getText(),
          'url' => $link->getUrl()->toString(),
          'active' => FALSE,
          'front' => $link->getUrl()->getRouteName() === "<front>"
        ];
      }

      // Add the current entity link.
      $links[] = [
        'text' => $entity->label(),
        'url' => $entity->toUrl()->toString(),
        'active' => TRUE,
      ];

      return $links;
    });

    // Handle any bubbled cacheability metadata.
    if (!$context->isEmpty()) {
      $bubbleable_metadata = $context->pop();
      $cache_metadata->addCacheableDependency($bubbleable_metadata);
    }

    $cache_metadata->addCacheableDependency($entity);
    $cache_metadata->addCacheableDependency($url_generated);

    /** @var \Drupal\seeds_headless_helper\Plugin\Field\FieldType\CacheableStringItem $item */
    $item = $this->createItem(0, json_encode($links));
    /** @var \Drupal\Core\Cache\RefinableCacheableDependencyInterface $property */
    $property = $item->get('value');
    $property->addCacheableDependency($cache_metadata);
    $this->list[0] = $item;
  }
}
