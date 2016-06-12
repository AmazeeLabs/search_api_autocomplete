<?php

namespace Drupal\search_api_autocomplete\Plugin\search_api_autocomplete\AutocompleteSuggester;

use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\SearchApiException;
use Drupal\search_api_autocomplete\AutocompleteSuggesterInterface;
use Drupal\search_api_autocomplete\SearchApiAutocompleteInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a suggester plugin that retrieves suggestions from the server.
 *
 * The server needs to support the "search_api_autocomplete" feature for this to
 * work.
 *
 * @AutocompletionSuggester(
 *   id = "server",
 *   label = @Translation("Retrieve from server"),
 *   description = @Translation("For compatible servers, ask the server for autocomplete suggestions."),
 * )
 */
class Server extends SuggesterPluginBase implements AutocompleteSuggesterInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $search = $plugin_definition['search'];
    return new static(
      $search,
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function supportsIndex(IndexInterface $index) {
    try {
      return $index->getServerInstance() && $index->getServerInstance()->supportsFeature('search_api_autocomplete');
    }
    catch (SearchApiException $e) {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'fields' => array(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Add a list of fields to include for autocomplete searches.
    $search = $this->getSearch();
    $fields = $search->index()->getFields();
    $fulltext_fields = $search->index()->getFulltextFields();
    $options = [];
    foreach ($fulltext_fields as $field) {
      $options[$field] = $fields[$field]['name'];
    }
    $form['fields'] = [
      '#type' => 'checkboxes',
      '#title' => t('Override used fields'),
      '#description' => t('Select the fields which should be searched for matches when looking for autocompletion suggestions. Leave blank to use the same fields as the underlying search.'),
      '#options' => $options,
      '#default_value' => array_combine($this->configuration['fields'], $this->configuration['fields']),
      '#attributes' => ['class' => ['search-api-checkboxes-list']],
    ];
    $form['#attached']['library'][] = 'search_api/drupal.search_api.admin_css';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $values = $form_state->getValues();
    $values['fields'] = array_keys(array_filter($values['fields']));
    $this->setConfiguration($values);
  }

  /**
   * {@inheritdoc}
   */
  public function getAutocompleteSuggestions(QueryInterface $query, $incomplete_key, $user_input) {
    if ($this->configuration['fields']) {
      $query->setFulltextFields($this->configuration['fields']);
    }

    if (($server = $query->getIndex()->getServerInstance()) && $server instanceof SearchApiAutocompleteInterface) {
      return $server->getAutocompleteSuggestions($query, $this->getSearch(), $incomplete_key, $user_input);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
  }

}
