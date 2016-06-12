<?php

namespace Drupal\search_api_autocomplete;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;

class RouteProvider extends DefaultHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  protected function getEditFormRoute(EntityTypeInterface $entity_type) {
//    $route = parent::getEditFormRoute($entity_type);
//    $route->setOption('parameters', ['search_api_index' => [
//      'type' => 'entity:' . 'search_api_index',
//    ]]);
//    $route->setRequirement('_entity_access', 'search_api_index.update');
//
//    return $route;
  }


}
