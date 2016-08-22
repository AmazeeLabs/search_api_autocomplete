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
   * Returns the label of the autocompletion type.
   *
   * @return string
   */
  public function getLabel();

  /**
   * Returns the description of the autocompletion type.
   *
   * @return string
   */
  public function getDescription();

  /**
   */
  public function listSearches(IndexInterface $index);

  /**
   * Creates the searchapi query based upon the typed strings.
   *
   * @param \Drupal\search_api_autocomplete\SearchApiAutocompleteSearchInterface $search
   *   The autocomplete search configuration.
   * @param string $complete
   * @param string $incomplete
   *
   * @return \Drupal\search_api\Query\QueryInterface
   *   The created query.
   */
  public function createQuery(SearchApiAutocompleteSearchInterface $search, $complete, $incomplete);

}
