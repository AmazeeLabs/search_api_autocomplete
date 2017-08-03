<?php

namespace Drupal\search_api_autocomplete;

/**
 * Provides some helper methods to deal with the autocomplete form.
 *
 * @todo This should be a service.
 */
class AutocompleteFormUtility {

  /**
   * Split a string with search keywords into two parts.
   *
   * The first part consists of all words the user has typed completely, the
   * second one contains the beginning of the last, possibly incomplete word.
   *
   * @param string $keys
   *   The passed in keys.
   *
   * @return array
   *   An array with $keys split into exactly two parts, both of which may be
   *   empty.
   */
  public static function splitKeys($keys) {
    $keys = ltrim($keys);
    // If there is whitespace or a quote on the right, all words have been
    // completed.
    if (rtrim($keys, " \"") != $keys) {
      return [rtrim($keys, ' '), ''];
    }
    if (preg_match('/^(.*?)\s*"?([\S]*)$/', $keys, $m)) {
      return [$m[1], $m[2]];
    }
    return ['', $keys];
  }

  /**
   * Helper method for altering a textfield form element to use autocompletion.
   *
   * @param array $element
   *   The altered element.
   * @param \Drupal\search_api_autocomplete\SearchInterface $search
   *   The autocomplete search.
   * @param array $data
   *   (optional) Additional data to pass to the autocomplete callback.
   */
  public static function alterElement(array &$element, SearchInterface $search, array $data = []) {
    $element['#type'] = 'search_api_autocomplete';
    $element['#search_id'] = $search->id();
    $element['#additional_data'] = $data;
  }

}
