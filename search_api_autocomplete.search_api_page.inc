<?php

/**
 * @file
 * Contains code for integrating with the "Search pages" module.
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api_autocomplete\AutocompleteFormUtility;
use Drupal\search_api_autocomplete\Entity\SearchApiAutocompleteSearch;

/**
 * Implements hook_form_FORM_ID_alter().
 *
 * Adds autocompletion to the keywords field on search pages, if enabled by the
 * user.
 */
function search_api_autocomplete_form_search_api_page_block_form_alter(array &$form, FormStateInterface $form_state) {
  $id = 'search_api_page_' . $form_state->getBuildInfo()['args'][0]['search_api_page'];
  $search = SearchApiAutocompleteSearch::load($id);

  $autocomplete_form_utlity = new AutocompleteFormUtility(\Drupal::service('renderer'));
  if ($search && $search->status()) {
    $autocomplete_form_utlity->alterElement($form['keys'], $search);
  }
}
