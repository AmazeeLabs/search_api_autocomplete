<?php

namespace Drupal\search_api_autocomplete\Type;

use Drupal\search_api\IndexInterface;
use Drupal\search_api_autocomplete\SearchApiAutocompleteSearchInterface;

/**
 * @todo
 *
 * @see \Drupal\search_api_autocomplete\Annotation\SearchapiAutocompleteType
 * @see \Drupal\search_api_autocomplete\Type\TypeManager
 */
interface TypeInterface {

  /**
   *
   */
  public function getLabel();

  /**
   *
   */
  public function getDescription();

  /**
   *
   */
  public function listSearches(IndexInterface $index);

  /**
   * @param \Drupal\search_api_autocomplete\SearchApiAutocompleteSearchInterface $search
   *   The autocomplete search configuration.
   * @param string $complete
   * @param string $incomplete
   *
   * @return \Drupal\search_api\Query\QueryInterface
   */
  public function createQuery(SearchApiAutocompleteSearchInterface $search, $complete, $incomplete);

}
