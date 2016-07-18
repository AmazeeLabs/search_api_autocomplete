<?php

/**
 * @file
 * Contains code for integrating with the "Search views" module.
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Entity\Index;
use Drupal\search_api_autocomplete\AutocompleteFormUtility;
use Drupal\search_api_autocomplete\Controller\AutocompleteController;
use Drupal\search_api_autocomplete\Entity\SearchApiAutocompleteSearch;

/**
 * Implements hook_form_FORM_ID_alter().
 *
 * Adds autocompletion to input fields for fulltext keywords on views with
 * exposed filters.
 */
function search_api_autocomplete_form_views_exposed_form_alter(array &$form, FormStateInterface $form_state) {
  /** @var \Drupal\views\ViewExecutable $view */
  $view = $form_state->get('view');
  if (substr($view->storage->get('base_table'), 0, 17) != 'search_api_index_') {
    return;
  }
  $search_id = 'search_api_views_' . $view->id();
  $search = SearchApiAutocompleteSearch::load($search_id);
  $controller = new AutocompleteController(\Drupal::service('renderer'));
  if (!$search || !$controller->access($search, \Drupal::currentUser())->isAllowed()) {
    return;
  }
  $index_id = substr($view->storage->get('base_table'), 17);

  $index = Index::load($index_id);
  if (!$fields = $index->getFulltextFields()) {
    return;
  }
  // Add the "Search: Fulltext search" filter as another text field.
  $fields[] = 'search_api_fulltext';

  $autocomplete_form_utlity = new AutocompleteFormUtility(\Drupal::service('renderer'));
  \Drupal::moduleHandler()->alter('search_api_autocomplete_views_fulltext_fields', $fields, $search, $view);
  foreach ($view->filter as $filter_name => $filter) {
    if (in_array($filter->realField, $fields)) {
      if (!empty($filter->options['expose']['identifier'])) {
        $key = $filter->options['expose']['identifier'];
        if (isset($form[$key]) && $form[$key]['#type'] == 'textfield') {
          if ($filter->realField == 'search_api_fulltext') {
            $autocomplete_fields = $filter->options['fields'];
          }
          else {
            $autocomplete_fields = [$filter->realField];
          }
          $autocomplete_form_utlity->alterElement($form[$key], $search, $autocomplete_fields);
        }
      }
    }
  }
}
