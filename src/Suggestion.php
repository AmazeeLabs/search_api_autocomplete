<?php

namespace Drupal\search_api_autocomplete;

/**
 * Provides a value object meant to be used as result of suggestions.
 */
class Suggestion implements SuggestionInterface {

  /**
   * The keywords this suggestion will autocomplete to.
   *
   * @return string[]|null
   */
  protected $keys = NULL;

  /**
   * A URL to which the suggestion should redirect to.
   *
   * @return string|null
   */
  protected $url = NULL;

  /**
   * For special suggestions, some kind of HTML prefix describing them.
   *
   * @return string|null
   */
  protected $prefix = NULL;

  /**
   * A suggested prefix for the entered input.
   *
   * @return string|null
   */
  protected $suggestionPrefix = '';

  /**
   * The input entered by the user. Defaults to $user_input.
   *
   * @return string|null
   */
  protected $userInput;

  /**
   * A suggested suffix for the entered input.
   *
   * @var string|null
   */
  protected $suggestionSuffix = '';

  /**
   * If available, the estimated number of results for these keys.
   *
   * @var string
   */
  protected $results = NULL;

  /**
   * If given, an HTML string or render array.
   *
   * @var array
   */
  protected $render;

  /**
   * Creates a new Suggestion instance.
   *
   * @param array $keys
   *   The keys.
   * @param string $url
   *   The url.
   * @param string $prefix
   *   The prefix.
   * @param $suggestionPrefix
   *   The suggestion prefix.
   * @param string $userInput
   *   The user input.
   * @param null|string $suggestionSuffix
   *   The suggestion suffix.
   * @param int $results
   *   The number of results.
   * @param array $render
   *   The render array.
   */
  public function __construct($keys = NULL, $url = '', $prefix = '', $suggestionPrefix = '', $userInput = '', $suggestionSuffix = '', $results = 0, $render = []) {
    $this->keys = $keys;
    $this->url = $url;
    $this->prefix = $prefix;
    $this->suggestionPrefix = $suggestionPrefix;
    $this->userInput = $userInput;
    $this->suggestionSuffix = $suggestionSuffix;
    $this->results = $results;
    $this->render = $render;
  }

  /**
   * Creates a new suggestion from a string.
   *
   * @param string $suggestion
   *   The suggestion string.
   * @param string $keys
   *   The search keys.
   *
   * @return static
   */
  public static function fromString($suggestion, $keys) {
    $pos = strpos($suggestion, $keys);
    if ($pos === FALSE) {
      return new static(NULL, NULL, '', '', '', $suggestion);
    }
    else {
      return new static(NULL, '', '', substr($suggestion, 0, $pos), $keys, substr($suggestion, $pos + strlen($keys)));
    }
  }

  /**
   * Creates a suggestion from a suggestion suffix.
   *
   * @param string $suggestion_suffix
   *   The suggestion suffix.
   * @param int $results
   *   (optional) The amount of results.
   * @param string $user_input
   *   (optional) The user input.
   *
   * @return static
   */
  public static function fromSuggestionSuffix($suggestion_suffix, $results = 0, $user_input = '') {
    return new static(NULL, '', '', '', $user_input, $suggestion_suffix, $results);
  }

  /**
   * {@inheritdoc}
   */
  public function getKeys() {
    return $this->keys;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl() {
    return $this->url;
  }

  /**
   * {@inheritdoc}
   */
  public function getPrefix() {
    return $this->prefix;
  }

  /**
   * {@inheritdoc}
   */
  public function getSuggestionPrefix() {
    return $this->suggestionPrefix;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserInput() {
    return $this->userInput;
  }

  /**
   * {@inheritdoc}
   */
  public function getSuggestionSuffix() {
    return $this->suggestionSuffix;
  }

  /**
   * {@inheritdoc}
   */
  public function getResults() {
    return $this->results;
  }

  /**
   * {@inheritdoc}
   */
  public function getRender() {
    return $this->render;
  }

  /**
   * {@inheritdoc}
   */
  public function setKeys($keys) {
    $this->keys = $keys;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setUrl($url) {
    $this->url = $url;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setPrefix($prefix) {
    $this->prefix = $prefix;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSuggestionPrefix($suggestionPrefix) {
    $this->suggestionPrefix = $suggestionPrefix;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setUserInput($userInput) {
    $this->userInput = $userInput;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSuggestionSuffix($suggestionSuffix) {
    $this->suggestionSuffix = $suggestionSuffix;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setResults($results) {
    $this->results = $results;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setRender($render) {
    $this->render = $render;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function toRenderable() {
    if (!empty($this->render)) {
      return $this->render;
    }
    else {
      return [
        '#theme' => 'search_api_autocomplete_suggestion',
        '#keys' => $this->getKeys(),
        '#prefix' => $this->getPrefix(),
        '#results' => $this->getResults(),
        '#suggestion_prefix' => $this->getSuggestionPrefix(),
        '#suggestion_suffix' => $this->getSuggestionSuffix(),
        '#url' => $this->getUrl(),
        '#user_input' => $this->getUserInput(),
      ];
    }
  }

}
