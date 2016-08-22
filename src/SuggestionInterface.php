<?php

namespace Drupal\search_api_autocomplete;

use Drupal\Core\Render\RenderableInterface;

/**
 * Defines a single autocompletion suggestion.
 *
 * All the keys are optional, with the exception that at least one of "keys",
 * "url", "suggestion_prefix", "user_input" or "suggestion_suffix" has to be
 * present.
 */
interface SuggestionInterface extends RenderableInterface {

  /**
   * The keywords this suggestion will autocomplete to.
   *
   * If it is not present, a direct concatenation (no spaces in between) of
   * "suggestion_prefix", "user_input" and "suggestion_suffix" will be used
   * instead.
   *
   * @return string[]|null
   */
  public function getKeys();

  /**
   * A URL to which the suggestion should redirect to.
   *
   * A URL to which the suggestion should redirect instead of completing the
   * user input in the text field. This overrides the normal behavior and thus
   * makes "keys" obsolete.
   *
   * @return string|null
   */
  public function getUrl();

  /**
   * For special suggestions, some kind of HTML prefix describing them.
   *
   * @return string|null
   */
  public function getPrefix();

  /**
   * A suggested prefix for the entered input.
   *
   * @return string|null
   */
  public function getSuggestionPrefix();

  /**
   * The input entered by the user. Defaults to $user_input.
   *
   * @return string|null
   */
  public function getUserInput();

  /**
   * A suggested suffix for the entered input.
   *
   * @return string|null
   */
  public function getSuggestionSuffix();

  /**
   * If available, the estimated number of results for these keys.
   *
   * @return int|null
   */
  public function getResults();

  /**
   * A render array.
   *
   * This should be displayed to the user for this suggestion. If missing, the
   * suggestion is instead passed to theme_search_api_autocomplete_suggestion().
   *
   * @return array|null
   */
  public function getRender();

  /**
   * Sets the keys.
   *
   * @param mixed $keys
   *   The keys.
   *
   * @return $this
   */
  public function setKeys($keys);

  /**
   * Sets the URL.
   *
   * @param mixed $url
   *   The URL.
   *
   * @return $this
   */
  public function setUrl($url);

  /**
   * Sets the prefix.
   *
   * @param mixed $prefix
   *   The prefix.
   *
   * @return $this
   */
  public function setPrefix($prefix);

  /**
   * Sets the suggestion prefix.
   *
   * @param mixed $suggestionPrefix
   *   The suggestion prefix.
   *
   * @return $this
   */
  public function setSuggestionPrefix($suggestionPrefix);

  /**
   * Sets the user input.
   *
   * @param mixed $userInput
   *   The user input.
   *
   * @return $this
   */
  public function setUserInput($userInput);

  /**
   * Sets the suggestion suffix.
   *
   * @param null|string $suggestionSuffix
   *   The suggestion suffix.
   *
   * @return $this
   */
  public function setSuggestionSuffix($suggestionSuffix);

  /**
   * Sets the result count.
   *
   * @param string $results
   *   The results count.
   *
   * @return $this
   */
  public function setResults($results);

  /**
   * Sets the render array.
   *
   * @param array|null $render
   *   The render array.
   *
   * @return $this
   */
  public function setRender($render);

}
