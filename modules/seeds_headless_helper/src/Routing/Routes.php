<?php

namespace Drupal\seeds_headless_helper\Routing;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\seeds_headless_helper\Resource\WebformResource;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Defines dynamic routes for each webform.
 *
 */
class Routes implements ContainerInjectionInterface {
  const RESOURCE_NAME = WebformResource::class;
  const JSONAPI_RESOURCE_KEY = '_jsonapi_resource';

  /**
   * {@inheritdoc}
   */
  public function __construct() {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static();
  }

  /**
   * {@inheritdoc}
   */
  public function routes() {
    $routes = new RouteCollection();

    $route = new Route('/%jsonapi%/webform/{webform}');
    $route->addDefaults([
      static::JSONAPI_RESOURCE_KEY => static::RESOURCE_NAME,
    ]);
    $route->setOption('parameters', [
      'webform' => [
        'type' => 'entity:webform',
      ],
    ]);
    $routes->add('seeds_headless_helper.webform', $route);
    $routes->addRequirements(['_access' => 'TRUE']);

    return $routes;
  }

}
