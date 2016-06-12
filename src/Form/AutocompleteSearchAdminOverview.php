<?php

namespace Drupal\search_api_autocomplete\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\search_api\IndexInterface;
use Drupal\search_api_autocomplete\AutocompleteSuggesterInterface;
use Drupal\search_api_autocomplete\Entity\SearchApiAutocompleteSearch;

class AutocompleteSearchAdminOverview extends FormBase {

  /**
   * @var \Drupal\search_api_autocomplete\Entity\SearchApiAutocompleteSearch
   */
  protected $entity;

  protected function getSuggestersForIndex(IndexInterface $index) {
    /** @var \Drupal\Component\Plugin\PluginManagerInterface $manager */
    $manager = \Drupal::service('plugin_manager.search_api_autocomplete_suggester');

    $suggesters = array_map(function ($suggester_info) use ($manager) {
      return $suggester_info['class'];
    }, $manager->getDefinitions());
    $suggesters = array_filter($suggesters, function ($suggester_class) use ($index) {
      return $suggester_class::supportsIndex($index);
    });
    return $suggesters;
  }

  /**
   * @param string $index_id
   *   The index ID.
   *
   * @return \Drupal\search_api_autocomplete\Entity\SearchApiAutocompleteSearch[]
   */
  protected function loadAutocompleteSearchByIndex($index_id) {
    return \Drupal::entityTypeManager()->getStorage('search_api_autocomplete_settings')->loadByProperties([
      'index_id' => $index_id,
    ]);
  }

  public function submitDelete(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\search_api\IndexInterface $index */
    $index = $form_state->get('index');
    $ids = array_keys($this->loadAutocompleteSearchByIndex($index->id()));
    if ($ids) {
      entity_delete_multiple('search_api_autocomplete_search', $ids);
      drupal_set_message($this->t('All autocompletion settings stored for this index were deleted.'));
    }
    else {
      drupal_set_message($this->t('There were no settings to delete.'), 'warning');
    }
    $form_state->setRedirectUrl($index->toUrl());
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, IndexInterface $search_api_index = NULL) {
    $form_state->set('index', $search_api_index);
    $index_id = $search_api_index->id();

    $available_suggesters = $this->getSuggestersForIndex($search_api_index);
    if (!$available_suggesters) {
      $args = [
        '@feature' => 'search_api_autocomplete',
        ':service_classes_url' => 'https://www.drupal.org/node/1254698#service-classes',
      ];
      drupal_set_message(t('There are currently no suggester plugins installed that support this index. To solve this problem, you can either:<ul><li>move the index to a server which supports the "@feature" feature (see the <a href=":service_classes_url">available service class</a>)</li><li>or install a module providing a new suggester plugin that supports this index</li></ul>', $args), 'error');
      if ($this->loadAutocompleteSearchByIndex($index_id)) {
        $form['description'] = array(
          '#type' => 'item',
          '#title' => $this->t('Delete autocompletion settings'),
          '#description' => $this->t("If you won't use autocompletion with this index anymore, you can delete all autocompletion settings associated with it. " .
            "This will delete all autocompletion settings on this index. Settings on other indexes won't be influenced."),
        );
        $form['button'] = [
          '#type' => 'submit',
          '#value' => t('Delete autocompletion settings'),
          '#submit' => [$this, 'submitDelete'],
        ];
      }
      return $form;
    }

    $form['#tree'] = TRUE;
    /** @var \Drupal\search_api_autocomplete\Plugin\AutocompleteTypeManager $type_manager */
    $type_manager = \Drupal::service('plugin_manager.search_api_autocomplete_type');
    $types = array_map(function ($definition) use ($type_manager) {
      return $type_manager->createInstance($definition['plugin_id']);
    }, $type_manager->getDefinitions());
    $searches = $this->loadAutocompleteSearchByIndex($index_id);
    /** @var \Drupal\search_api_autocomplete\AutocompleteTypeInterface $autocomplete_type */
    foreach ($types as $type => $autocomplete_type) {
      $t_searches = $autocomplete_type->listSearches($search_api_index);
      foreach ($t_searches as $id => $search) {
        if (isset($searches[$id])) {
          $types[$type]->searches[$id] = $searches[$id];
          unset($searches[$id]);
        }
        else {
          reset($available_suggesters);
          $search += [
            'machine_name' => $id,
            'index_id' => $index_id,
            'suggester_id' => key($available_suggesters),
            'type' => $type,
            'enabled' => 0,
            'options' => [],
          ];
          $search['options'] += [
            'results' => TRUE,
            'fields' => [],
          ];
          // @todo this is ugly!
          $types[$type]->searches[$id] = SearchApiAutocompleteSearch::create($search);
        }
      }
    }
    /** @var \Drupal\search_api_autocomplete\Entity\SearchApiAutocompleteSearch $search */
    foreach ($searches as $id => $search) {
      $type = isset($types[$search->getType()]) ? $search->getType() : '';
      $types[$type]['searches'][$id] = $search;
      $types[$type]['unavailable'][$id] = TRUE;
    }
//    $base_path = 'admin/config/search/search_api/index/' . $index_id . '/autocomplete/';
    /** @var \Drupal\search_api_autocomplete\AutocompleteTypeInterface $autocomplete_type */
    foreach ($types as $type => $autocomplete_type) {

      if (empty($autocomplete_type->searches)) {
        continue;
      }
//      if (!$type) {
//        $info = [];
//        $info += [
//          'name' => $this->t('Unavailable search types'),
//          'description' => $this->t("The modules providing these searches were disabled or uninstalled. If you won't use them anymore, you can delete their settings."),
//        ];
//      }
//      elseif (!empty($info['unavailable'])) {
//        $info['description'] .= '</p><p>' . t("The searches marked with an asterisk (*) are currently not available, possibly because they were deleted. If you won't use them anymore, you can delete their settings.");
//      }
      $form[$type] = [
        '#type' => 'fieldset',
        '#title' => $autocomplete_type->getLabel(),
      ];
      if ($description = $autocomplete_type->getDescription()) {
        $form[$type]['#description'] = '<p>' . $description . '</p>';
      }
      $form[$type]['searches']['#theme'] = 'tableselect';
      $form[$type]['searches']['#header'] = [
        'name' => t('Name'),
        'operations' => t('Operations'),
      ];
      $form[$type]['searches']['#empty'] = '';
      $form[$type]['searches']['#js_select'] = TRUE;
      foreach ($autocomplete_type->searches as $id => $search) {
        $form[$type]['searches'][$id] = [
          '#type' => 'checkbox',
          '#default_value' => $search->status(),
          '#parents' => ['searches', $id],
        ];
        $unavailable = !empty($info['unavailable'][$id]);
        if ($unavailable) {
          $form[$type]['searches'][$id]['#default_value'] = FALSE;
          $form[$type]['searches'][$id]['#disabled'] = TRUE;
        }
//        $form_state['searches'][$id] = $search;
//        $options = &$form[$type]['searches']['#options'][$id];
//        if ($show_status) {
//          $options['status'] = isset($search->status) ? theme('entity_status', array('status' => $search->status)) : '';;
//        }
//        $options['name'] = $search->name;
//        if ($unavailable) {
//          $options['name'] = '* ' . $options['name'];
//        }
//        $items = array();
//        if (!$unavailable && !empty($search->id)) {
//          $items[] = l(t('edit'), $base_path . $id . '/edit');
//        }
//        if (!empty($search->status) && ($search->hasStatus(ENTITY_CUSTOM))) {
//          $title = $search->hasStatus(ENTITY_IN_CODE) ? t('revert') : t('delete');
//          $items[] = l($title, $base_path . $id . '/delete');
//        }
//        if ($items) {
//          $variables = array(
//            'items' => $items,
//            'attributes' => array('class' => array('inline')),
//          );
//          $options['operations'] = theme('item_list', $variables);
//        }
//        else {
//          $options['operations'] = '';
//        }
//        unset($options);
      }
    }

    if (!Element::children($form)) {
      $form['message']['#markup'] = '<p>' . $this->t('There are currently no searches known for this index.') . '</p>';
    }
    else {
      $form['submit'] = [
        '#type' => 'submit',
        '#value' => t('Save'),
      ];
    }

    return $form;
  }

  public static function suggestAjaxCallback(array $form, FormStateInterface $form_state) {
    return $form['options']['suggester_configuration'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
//
//    $values = &$form_state['values'];
//    // Call the config form validation method of the selected suggester plugin,
//    // but only if it was the same plugin that created the form.
//    if ($values['suggester_id'] == $values['old_suggester_id']) {
//      $configuration = array();
//      if (!empty($values['options']['suggester_configuration'])) {
//        $configuration = $values['options']['suggester_configuration'];
//      }
//      $suggester = search_api_autocomplete_suggester_load($values['suggester_id'], $form_state['search'], $configuration);
//      $suggester_form = $form['options']['suggester_configuration'];
//      unset($suggester_form['old_suggester_id']);
//      $suggester_form_state = &search_api_autocomplete_get_plugin_form_state($form_state);
//      $suggester->validateConfigurationForm($suggester_form, $suggester_form_state);
//    }
//
//    if (!empty($form_state['type']['config form'])) {
//      $f = $form_state['type']['config form'] . '_validate';
//      if (function_exists($f)) {
//        $custom_form = empty($form['options']['custom']) ? array() : $form['options']['custom'];
//        $f($custom_form, $form_state, $values['options']['custom']);
//      }
//    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

//    $values = &$form_state['values'];
//    if (!empty($form_state['type']['config form'])) {
//      $f = $form_state['type']['config form'] . '_submit';
//      if (function_exists($f)) {
//        $custom_form = empty($form['options']['custom']) ? array() : $form['options']['custom'];
//        $f($custom_form, $form_state, $values['options']['custom']);
//      }
//    }
//
//    $search = $form_state['search'];
//    $search->enabled = $values['enabled'];
//    $search->suggester_id = $values['suggester_id'];
//
//    $form_state['redirect'] = 'admin/config/search/search_api/index/' . $search->index_id . '/autocomplete';
//
//    // Take care of custom options that aren't changed in the config form.
//    if (!empty($search->options['custom'])) {
//      if (!isset($values['options']['custom'])) {
//        $values['options']['custom'] = array();
//      }
//      $values['options']['custom'] += $search->options['custom'];
//    }
//
//    // Allow the suggester to decide how to save its configuration. If the user
//    // has disabled JS in the browser, or AJAX didn't work for some other reason,
//    // a different suggester might be selected than that which created the config
//    // form. In that case, we don't call the form submit method, save empty
//    // configuration for the plugin and stay on the page.
//    if ($values['suggester_id'] == $values['old_suggester_id']) {
//      $configuration = array();
//      if (!empty($values['options']['suggester_configuration'])) {
//        $configuration = $values['options']['suggester_configuration'];
//      }
//      $suggester = search_api_autocomplete_suggester_load($values['suggester_id'], $search, $configuration);
//      $suggester_form = $form['options']['suggester_configuration'];
//      unset($suggester_form['old_suggester_id']);
//      $suggester_form_state = &search_api_autocomplete_get_plugin_form_state($form_state);
//      $suggester->submitConfigurationForm($suggester_form, $suggester_form_state);
//      $values['options']['suggester_configuration'] = $suggester->getConfiguration();
//    }
//    else {
//      $values['options']['suggester_configuration'] = array();
//      $form_state['redirect'] = NULL;
//      drupal_set_message(t('The used suggester plugin has changed. Please review the configuration for the new plugin.'), 'warning');
//    }
//
//    $search->options = $values['options'];
//
//    $search->save();
//    drupal_set_message(t('The autocompletion settings for the search have been saved.'));
//  }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'search_api_autocomplete_admin_overview';
  }

}
