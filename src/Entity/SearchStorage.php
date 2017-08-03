<?php

namespace Drupal\search_api_autocomplete\Entity;

use Drupal\Core\Config\Entity\ConfigEntityStorage;

/**
 * Provides a storage handler for our search entity.
 */
class SearchStorage extends ConfigEntityStorage {

  /**
   * Loads the search that uses the given type plugin, if one exists.
   *
   * @param string $type_id
   *   The type plugin ID.
   *
   * @return \Drupal\search_api_autocomplete\SearchInterface|null
   *   The autocomplete search entity with that type, or NULL if none exists.
   */
  public function loadByType($type_id) {
    // @todo Change to the following once #2899014 gets fixed.
//    $matching_entities = $this->getQuery()
//      ->exists("type_settings.$type_id")
//      ->execute();
    /** @var \Drupal\search_api_autocomplete\SearchInterface $search */
    foreach ($this->loadMultiple() as $search) {
      if ($search->getTypeId() === $type_id) {
        return $search;
      }
    }
    return NULL;
  }

}
