<?php
namespace Drupal\seeds_headless_helper\Resource;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\RenderContext;
use Drupal\jsonapi\JsonApiResource\LinkCollection;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi\JsonApiResource\ResourceObjectData;
use Drupal\jsonapi\ResourceResponse;
use Drupal\jsonapi_resources\Resource\ResourceBase;
use Drupal\webform\WebformInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Processes a request for a collection of webforms.
 *
 * @internal
 */
final class WebformResource extends ResourceBase {
  /**
   * Process the resource request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param \Drupal\system\MenuInterface $menu
   *   The menu.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The response.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function process(Request $request, WebformInterface $webform): ResourceResponse {
    $cacheability = new CacheableMetadata();

    $resource_object_cacheability = new CacheableMetadata();
    $resource_object_cacheability->addCacheableDependency($webform);
    $resource_type = $this->resourceTypeRepository->get('webform', 'webform');
    $fields = [
      'name' => $webform->label(),
      'isOpen' => $webform->isOpen(),
      'elements' => $this->getElements($webform, $resource_object_cacheability),
    ];
    $items[$webform->id()] = new ResourceObject($resource_object_cacheability, $resource_type, $webform->id(), NULL, $fields, new LinkCollection([]));
    $data = new ResourceObjectData($items);
    /** @var \Drupal\Core\Cache\CacheableJsonResponse $response */
    $response = $this->createJsonapiResponse($data, $request, 200, [] /* , $pagination_links */);

    return $response;
  }

  /**
   * Clean up any object.
   *
   * @param array $elements
   *   The elemenets to clean up.
   *
   * @return void
   */
  private function cleanupObjects(array &$elements) {
    foreach ($elements as $key => &$element) {
      if (is_array($element)) {
        $this->cleanupObjects($element);
      }

      if (is_object($element)) {
        $element = NULL;
      }
    }
  }

  /**
   * Fixes the #required attribute for elements inside wizard page.
   *
   * @param array $elements
   *   The elements.
   * @param \Drupal\webform\WebformInterface $webform
   *   A The webform.
   *
   * @return void
   */
  private function setRequired(array &$elements, WebformInterface $webform) {
    foreach ($elements as $key => &$element) {
      if (!empty($element) && is_array($element)) {
        $this->setRequired($element, $webform);
      }

      if (isset($element['#required'])) {
        $getElement = $webform->getElementDecoded($key);
        if (isset($getElement) && $getElement['#required'] === TRUE) {
          $element['#required'] = TRUE;
        }
      }

      // Assume the access to be TRUE for elements inside wizard page.
      // @todo This might cause problems in the future by forcing the elements to have #access TRUE.
      if (isset($element['#access']) && isset($elements["#webform_plugin_id"])) {
        $element['#access'] = TRUE;
      }
    }
  }

  /**
   * Gets the elements of a webforms.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform.
   *
   * @return array
   *   An array of elements.
   */
  protected function getElements(WebformInterface $webform, CacheableMetadata $cache) {
    $renderer = \Drupal::service('renderer');
    $context = new RenderContext();
    $elements = $renderer->executeInRenderContext($context, function () use ($webform) {
      $elements = $webform->getSubmissionForm()['elements'] ?? [];
      $this->cleanupObjects($elements);
      // Make sure all required elements are actually requried.
      // Multi-step wizard pages sets anything after page 1 to required = false.
      $decoded_elements = $webform->getElementsDecoded();
      $this->setRequired($elements, $webform);

      return $this->cleanupElements($elements);
    });

    // If there is metadata left on the context, apply it on the response.
    if (!$context->isEmpty()) {
      $metadata = $context->pop();
      $cache->addCacheableDependency($metadata);
    }

    return $elements;
  }

  /**
   * Gets the elements of a webforms.
   *
   * @param array $elements
   *   An array of elements.
   *
   * @return array
   *   Cleaned up elements.
   */
  protected function cleanupElements(array $elements) {
    $children = [];
    foreach (Element::children($elements) as $child) {
      $children[$child] = $elements[$child];
    }

    return $children;
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteResourceTypes(Route $route, string $route_name): array {
    $resource_types = [];

    foreach (['webform'] as $type) {
      $resource_type = $this->resourceTypeRepository->get($type, $type);
      if ($resource_type) {
        $resource_types[] = $resource_type;
      }
    }

    return $resource_types;
  }
}
