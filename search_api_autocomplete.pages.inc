<?php

/**
 * @file
 * Contains page callbacks and theme functions for the frontend UI.
 */

/**
 * Returns HTML for an autocomplete suggestion.
 *
 * @param array $variables
 *   An associative array containing:
 *   - keys: The keyword (or keywords) this suggestion will autocomplete to.
 *   - prefix: For special suggestions, some kind of HTML prefix describing
 *     them.
 *   - suggestion_prefix: A suggested prefix for the entered input.
 *   - user_input: The input entered by the user.
 *   - suggestion_suffix: A suggested suffix for the entered input.
 *   - results: If available, the estimated number of results for these keys.
 */
function theme_search_api_autocomplete_suggestion(array $variables) {
  $prefix = $variables['prefix'];
  $suggestion_prefix = $variables['suggestion_prefix'];
  $user_input = $variables['user_input'];
  $suggestion_suffix = $variables['suggestion_suffix'];
  $results = $variables['results'];

  $output = '';
  if ($prefix) {
    $output .= "<span class=\"autocomplete-suggestion-note\">$prefix</span> ";
  }
  if ($suggestion_prefix) {
    $output .= "<span class=\"autocomplete-suggestion-prefix\">$suggestion_prefix</span>";
  }
  if ($user_input) {
    $output .= "<span class=\"autocomplete-user-input\">$user_input</span>";
  }
  if ($suggestion_suffix) {
    $output .= "<span class=\"autocomplete-suggestion-suffix\">$suggestion_suffix</span>";
  }
  if ($results) {
    $output .= " <span class=\"autocomplete-suggestion-results\">$results</span>";
  }

  return "<div class=\"search-api-autocomplete-suggestion\">$output</div>";
}
