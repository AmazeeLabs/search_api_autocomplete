<?php

/**
 * @file
 * Contains code for integrating with the "Search pages" module.
 */

use Drupal\search_api_autocomplete\Entity\SearchApiAutocompleteSearch;

/**
 * Implements hook_form_FORM_ID_alter().
 *
 * Adds autocompletion to the keywords field on search pages, if enabled by the
 * user.
 */
function search_api_autocomplete_form_search_api_page_search_form_alter(array &$form, array &$form_state) {
  if (isset($form['form'])) {
    $form = &$form['form'];
  }
  $id = 'search_api_page_' . $form_state['build_info']['args'][0]->id();
  $search = SearchApiAutocompleteSearch::load($id);
  if ($search && $search->status()) {
    $search->alterElement($form['keys_' . $form['id']['#value']]);
  }
}
