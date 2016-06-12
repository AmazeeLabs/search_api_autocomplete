<?php

/**
 * @file
 * Contains code for integrating with the "Search views" module.
 */

use Drupal\search_api\Entity\Index;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\SearchApiException;
use Drupal\search_api_autocomplete\Entity\SearchApiAutocompleteSearch;
use Drupal\views\Views;

/**
 * Implements hook_form_FORM_ID_alter().
 *
 * Adds autocompletion to input fields for fulltext keywords on views with
 * exposed filters.
 */
function search_api_autocomplete_form_views_exposed_form_alter(array &$form, array &$form_state) {
  $view = $form_state['view'];
  if (substr($view->base_table, 0, 17) != 'search_api_index_') {
    return;
  }
  $search_id = 'search_api_views_' . $view->name;
  $search = SearchApiAutocompleteSearch::load($search_id);
  if (!$search || !search_api_autocomplete_access($search)) {
    return;
  }
  $index_id = substr($view->base_table, 17);
  
  $index = Index::load($index_id);
  if (empty($index->getOption('fields'))) {
    return;
  }
  $fields = $index->getFulltextFields();
  // Add the "Search: Fulltext search" filter as another text field.
  $fields[] = 'search_api_views_fulltext';
  \Drupal::moduleHandler()->alter('search_api_autocomplete_views_fulltext_fields', $fields, $search, $view);
  foreach ($view->filter as $filter_name => $filter) {
    if (in_array($filter->real_field, $fields)) {
      if (!empty($filter->options['expose']['identifier'])) {
        $key = $filter->options['expose']['identifier'];
        if (isset($form[$key]) && $form[$key]['#type'] == 'textfield') {
          if ($filter->real_field == 'search_api_views_fulltext') {
            $autocomplete_fields = $filter->options['fields'];
          }
          else {
            $autocomplete_fields = array($filter->real_field);
          }
          $search->alterElement($form[$key], $autocomplete_fields);
        }
      }
    }
  }
}
