<?php

namespace Drupal\search_api_autocomplete\Type;

use Drupal\search_api\IndexInterface;
use Drupal\search_api_autocomplete\SearchApiAutocompleteSearchInterface;

/**
 * Defines the auto complete type plugin.
 *
 * @see \Drupal\search_api_autocomplete\Annotation\SearchapiAutocompleteType
 * @see \Drupal\search_api_autocomplete\Type\TypeManager
 */
interface TypeInterface {

  /**
   * Returns the label of the autocompletion type.
   *
   * @return string
   *   The label of the type.
   */
  public function getLabel();

  /**
   * Returns the description of the autocompletion type.
   *
   * @return string
   *   The type description.
   */
  public function getDescription();

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
   * Creates the searchapi query based upon the typed strings.
   *
   * @param \Drupal\search_api_autocomplete\SearchApiAutocompleteSearchInterface $search
   *   The autocomplete search configuration.
   * @param string $complete
   *   A complete word.
   * @param string $incomplete
   *   An incomplete word.
   *
   * @return \Drupal\search_api\Query\QueryInterface
   *   The created query.
   */
  public function createQuery(SearchApiAutocompleteSearchInterface $search, $complete, $incomplete);

}
