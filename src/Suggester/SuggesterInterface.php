<?php

namespace Drupal\search_api_autocomplete\Suggester;

use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api_autocomplete\Plugin\SearchPluginInterface;
use Drupal\search_api_autocomplete\SearchInterface;

/**
 * Represents a plugin for creating autocomplete suggestions.
 *
 * @see \Drupal\search_api_autocomplete\Annotation\SearchApiAutocompleteSuggester
 * @see \Drupal\search_api_autocomplete\Suggester\SuggesterManager
 * @see \Drupal\search_api_autocomplete\Suggester\SuggesterPluginBase
 * @see plugin_api
 */
interface SuggesterInterface extends SearchPluginInterface {

  /**
   * Determines whether this plugin supports the given search.
   *
   * @param \Drupal\search_api_autocomplete\SearchInterface $search
   *   The search in question.
   *
   * @return bool
   *   TRUE if this plugin supports the given search, FALSE otherwise.
   */
  public static function supportsSearch(SearchInterface $search);

  /**
   * Retrieves autocompletion suggestions for some user input.
   *
   * For example, when given the user input "teach us", with "us" being
   * considered incomplete, \Drupal\search_api_autocomplete\SuggestionInterface
   * objects representing the following suggestions might be returned:
   *
   * @code
   *   [
   *     [
   *       'prefix' => t('Did you mean:'),
   *       'user_input' => 'reach us',
   *     ],
   *     [
   *       'user_input' => 'teach us',
   *       'suggestion_suffix' => 'ers',
   *     ],
   *     [
   *       'user_input' => 'teach us',
   *       'suggestion_suffix' => ' swimming',
   *     ],
   *   ];
   * @endcode
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   A query representing the completed user input so far.
   * @param string $incomplete_key
   *   The start of another fulltext keyword for the search, which should be
   *   completed. Might be empty, in which case all user input up to now was
   *   considered completed. Then, additional keywords for the search could be
   *   suggested.
   * @param string $user_input
   *   The complete user input for the fulltext search keywords so far.
   *
   * @return \Drupal\search_api_autocomplete\SuggestionInterface[]
   *   An array of autocomplete suggestions.
   */
  public function getAutocompleteSuggestions(QueryInterface $query, $incomplete_key, $user_input);

}
