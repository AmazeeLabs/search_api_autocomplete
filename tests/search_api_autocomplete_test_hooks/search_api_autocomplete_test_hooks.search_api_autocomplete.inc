<?php

/**
 * @file
 * Implements all Search API Autocomplete hooks, for testing purposes.
 */

use Drupal\search_api_autocomplete\SearchInterface;
use Drupal\search_api_autocomplete\Suggestion\Suggestion;
use Drupal\views\ViewExecutable;

/**
 * Implements hook_search_api_autocomplete_suggestions_alter().
 */
function search_api_autocomplete_test_hooks_search_api_autocomplete_suggestions_alter(array &$suggestions, array $alter_params) {
  $suggestions[] = new Suggestion('dark chest of wonders');
}

/**
 * Implements hook_search_api_autocomplete_suggester_alter().
 */
function search_api_autocomplete_test_hooks_search_api_autocomplete_suggester_alter(array &$suggesters) {
  $suggesters['search_api_autocomplete_test']['label'] = 'Wish I Had an Angel';
}

/**
 * Implements hook_search_api_autocomplete_type_alter().
 */
function search_api_autocomplete_test_hooks_search_api_autocomplete_type_alter(array &$types) {
  foreach (array_keys($types) as $type_id) {
    $types[$type_id]['group_label'] = 'The Siren';
    $types[$type_id]['group_description'] = 'Planet Hell';
  }
}

/**
 * Implements hook_search_api_autocomplete_views_fulltext_fields_alter().
 */
function search_api_autocomplete_test_hooks_search_api_autocomplete_views_fulltext_fields_alter(array &$fields, SearchInterface $search, ViewExecutable $view) {
  drupal_set_message("Creek Mary's Blood");
}
