<?php

namespace Drupal\search_api_autocomplete\Type;

use Drupal\search_api\IndexInterface;
use Drupal\search_api_autocomplete\SearchInterface;
use Drupal\search_api_autocomplete\Plugin\SearchPluginInterface;

/**
 * Defines the auto complete type plugin.
 *
 * @see \Drupal\search_api_autocomplete\Annotation\SearchApiAutocompleteType
 * @see \Drupal\search_api_autocomplete\Type\TypeManager
 * @see \Drupal\search_api_autocomplete\Type\TypePluginBase
 * @see plugin_api
 */
interface TypeInterface extends SearchPluginInterface {

  /**
   * Returns a list of searches for this index.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   A search api index.
   *
   * @return array
   *   An array of searches.
   */
  public function listSearches(IndexInterface $index);

  /**
   * Creates a search query based on this search type.
   *
   * @param \Drupal\search_api_autocomplete\SearchInterface $search
   *   The autocomplete search configuration.
   * @param string $keys
   *   The keywords to set on the query.
   *
   * @return \Drupal\search_api\Query\QueryInterface
   *   The created query.
   *
   * @throws \Drupal\search_api_autocomplete\SearchApiAutocompleteException
   *   Thrown if the query couldn't be created.
   */
  public function createQuery(SearchInterface $search, $keys);

}
