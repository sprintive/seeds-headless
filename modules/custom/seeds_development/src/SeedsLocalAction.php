<?php

namespace Drupal\seeds_development;

use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 *
 */
class SeedsLocalAction extends LocalActionDefault {

  /**
   *
   */
  public function getRouteParameters(RouteMatchInterface $route_match) {
    $params = $route_match->getParameters();
    return [
      'entity_type_id' => $params->get('entity_type_id'),
      'bundle' => $params->get('bundle'),
      'form_mode' => $params->get('form_mode_name'),
    ];
  }

}
