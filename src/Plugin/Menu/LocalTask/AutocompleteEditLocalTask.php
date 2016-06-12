<?php

namespace Drupal\search_api_autocomplete\Plugin\Menu\LocalTask;

use Drupal\Core\Menu\LocalTaskDefault;
use Drupal\Core\Routing\RouteMatchInterface;

class AutocompleteEditLocalTask extends LocalTaskDefault {

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(RouteMatchInterface $route_match) {
    $parameters = parent::getRouteParameters($route_match);

    // @fixme This is an incredible ugly fix!
//    if (!$settings_entity = SearchApiAutocompleteSearch::load($parameters['search_api_index'])) {
//      $settings_entity = SearchApiAutocompleteSearch::create([
//        'id' => $parameters['search_api_index'],
//        'index_id' => $parameters['search_api_index'],
//      ]);
//      $settings_entity->save();
//    }
//    unset($parameters['search_api_index']);
    return $parameters;
  }


}
