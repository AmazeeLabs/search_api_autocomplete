<?php

namespace Drupal\search_api_autocomplete;

use Drupal\search_api\IndexInterface;
use Drupal\search_api_autocomplete\Entity\SearchApiAutocompleteSearch;

interface AutocompleteTypeInterface {

  public function getLabel();

  public function getDescription();

  public function listSearches(IndexInterface $index);

  public function createQuery(SearchApiAutocompleteSearch $search, $complete, $incomplete);

}
