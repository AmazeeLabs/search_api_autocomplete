<?php

namespace Drupal\search_api_autocomplete\Type;

use Drupal\search_api_autocomplete\SearchInterface;
use Drupal\search_api_autocomplete\Plugin\SearchPluginInterface;

/**
 * Defines the autocomplete search type plugin.
 *
 * @see \Drupal\search_api_autocomplete\Annotation\SearchApiAutocompleteType
 * @see \Drupal\search_api_autocomplete\Type\TypeManager
 * @see \Drupal\search_api_autocomplete\Type\TypePluginBase
 * @see plugin_api
 */
interface TypeInterface extends SearchPluginInterface {

  /**
   * Retrieves a group label for this type.
   *
   * Used to group searches from the same source together in the UI.
   *
   * @return string
   *   A translated, human-readable label to group the type by.
   */
  public function getGroupLabel();

  /**
   * Retrieves a description for this type's group.
   *
   * Types with the same group label should aim to also return the same group
   * description.
   *
   * @return string
   *   A translated, human-readable description for this type's group.
   */
  public function getGroupDescription();

  /**
   * Retrieves the ID of the index to which this type plugin belongs.
   *
   * @return string
   *   The type plugin's index's ID.
   */
  public function getIndexId();

  /**
   * Retrieves the index to which this type plugin belongs.
   *
   * @return \Drupal\search_api\IndexInterface
   *   The type plugin's index.
   */
  public function getIndex();

  /**
   * Creates a search query based on this search type.
   *
   * @param \Drupal\search_api_autocomplete\SearchInterface $search
   *   The autocomplete search configuration.
   * @param string $keys
   *   The keywords to set on the query, if possible. Otherwise, this parameter
   *   can also be ignored.
   * @param array $data
   *   (optional) Additional data passed to the callback.
   *
   * @return \Drupal\search_api\Query\QueryInterface
   *   The created query.
   *
   * @throws \Drupal\search_api_autocomplete\SearchApiAutocompleteException
   *   Thrown if the query couldn't be created.
   */
  public function createQuery(SearchInterface $search, $keys, array $data = []);

}
