<?php

namespace Drupal\seeds_headless_helper\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Routing\RouteMatch;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 *
 */
class BreadcrumbController extends ControllerBase {

  /**
   * Get breadcrumb for entity.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $entity_id
   *   The entity id.
   *
   * @return array
   *   The breadcrumb.
   */
  public function getBreadcrumb($entity_type, $entity_id) {
    // Load entity.
    $entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($entity_id);

    // No entity? throw not found.
    if (!$entity) {
      throw new NotFoundHttpException();
    }

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
    // Cachable json response.
    $response = new CacheableJsonResponse();
    $metadata = $response->getCacheableMetadata();
    // Build breadcrumb inside render context.
    $context = new RenderContext();
    /** @var \Drupal\Core\Cache\CacheableDependencyInterface $result */
    $links = \Drupal::service('renderer')->executeInRenderContext($context, function () use ($breadcrumb_manager, $route_match, $metadata, $entity) {
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
              $metadata->addCacheableDependency($parameter_entity);
            }
          }
        }
        $links[] = [
          'text' => $link->getText(),
          'url' => $link->getUrl()->toString(),
          'active' => FALSE,
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
      $metadata->addCacheableDependency($bubbleable_metadata);
    }

    $metadata->addCacheableDependency($entity);
    $metadata->addCacheableDependency($url_generated);
    $response->setData($links);
    return $response;
  }

  /**
   * Checks access.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $entity_id
   *   The entity id.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function access($entity_type, $entity_id) {
    // Load entity.
    $entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($entity_id);
    // Check if entity exists.
    if ($entity) {
      return $entity->access('view', $this->currentUser(), TRUE);
    }

    return AccessResult::allowed();
  }

}
