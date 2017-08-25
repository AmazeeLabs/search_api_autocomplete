<?php

/**
 * @file
 * Outputs some autocomplete suggestions as JSON, for testing purposes.
 *
 * Contained within a /core/ sub-directory to trick Drupal's .htaccess file.
 */

search_api_autocomplete_test_custom_autocomplete_callback();

/**
 * Outputs some autocomplete suggestions as JSON, for testing purposes.
 */
function search_api_autocomplete_test_custom_autocomplete_callback() {
  $suggestions = [];
  foreach ($_GET as $key => $value) {
    $suggestions[] = [
      'value' => $value,
      'label' => htmlentities("$key: $value"),
    ];
  }
  header('Content-type: application/json');
  echo json_encode($suggestions, JSON_PRETTY_PRINT);
  exit;
}
