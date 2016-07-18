<?php

namespace Drupal\search_api_autocomplete;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Describes the autocomplete settings for a certain search.
 */
interface SearchApiAutocompleteSearchInterface extends ConfigEntityInterface {

  /**
   * Returns the index this search belongs to.
   *
   * @return \Drupal\search_api\IndexInterface
   *   The index this search belongs to.
   */
  public function index();

  /**
   * Retrieves the server this search would at the moment be executed on.
   *
   * @return \Drupal\search_api\ServerInterface
   *   The server this search would at the moment be executed on.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   If a server is set for the index but it doesn't exist.
   */
  public function server();

  /**
   * Determines whether autocompletion is currently supported for this search.
   *
   * @return bool
   *   TRUE if autocompletion is possible for this search with the current
   *   settings; FALSE otherwise.
   */
  public function supportsAutocompletion();

  /**
   * Create the query that would be issued for this search for the complete
   * keys.
   *
   * @param $complete
   *   A string containing the complete search keys.
   * @param $incomplete
   *   A string containing the incomplete last search key.
   *
   * @return \Drupal\search_api\Query\QueryInterface
   *   The query that would normally be executed when only $complete was
   *   entered
   *   as the search keys for this search.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   If the query couldn't be created.
   */
  public function getQuery($complete, $incomplete);

  /**
   * Retrieves the ID of the suggester plugin for this search.
   *
   * @return string
   *   This search's suggester plugin's ID.
   */
  public function getSuggesterId();

  /**
   * Sets the suggester ID.
   *
   * @param string $suggester_id
   *   The suggester plugin ID.
   *
   * @return $this
   */
  public function setSuggesterId($suggester_id);

  /**
   * Retrieves the suggester plugin for this search.
   *
   * @param bool $reset
   *   (optional) If TRUE, clear the internal static cache and reload the
   *   suggester.
   *
   * @return \Drupal\search_api_autocomplete\Suggester\SuggesterInterface|null
   *   This search's suggester plugin, or NULL if it could not be loaded.
   */
  public function getSuggester($reset = FALSE);

  /**
   * @param string $label
   */
  public function setLabel($label);

  /**
   * @return int
   */
  public function getIndexId();

  /**
   * Returns the index instance.
   *
   * @return mixed
   */
  public function getIndexInstance();

  /**
   * Sets the index ID.
   *
   * @param string $index_id
   *   The index ID.
   *
   * @return $this
   */
  public function setIndexId($index_id);

  /**
   * Gets the autocompletion type.
   *
   * @return string
   */
  public function getType();

  /**
   * Sets the autocompletion type.
   *
   * @param string $type
   *   The autocompletion type.
   *
   * @return $this
   */
  public function setType($type);

  /**
   * Gets the options.
   *
   * @return array
   *   The options.
   */
  public function getOptions();

  /**
   * Sets the search options.
   *
   * @param array $options
   *   The options.
   *
   * @return $this
   */
  public function setOptions($options);

  /**
   * Gets a specific option.
   *
   * @param string $key
   *   The key of the option.
   * @param mixed|null $default
   *   (optional) The default value.
   *
   * @return mixed|null
   */
  public function getOption($key, $default = NULL);

}
