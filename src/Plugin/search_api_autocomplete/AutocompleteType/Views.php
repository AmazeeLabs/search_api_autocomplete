<?php

namespace Drupal\search_api_autocomplete\Plugin\search_api_autocomplete\AutocompleteType;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Url;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\SearchApiException;
use Drupal\search_api_autocomplete\Type\TypeInterface;
use Drupal\search_api_autocomplete\SearchApiAutocompleteSearchInterface;
use Drupal\views\Views as ViewsViews;

/**
 * @SearchapiAutocompleteType(
 *   id = "views",
 *   label = @Translation("Search views"),
 *   description = @Translation("Searches provided by views"),
 *   provider = "search_api",
 * )
 */
class Views extends PluginBase implements TypeInterface, ConfigurablePluginInterface, PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'display' => 'default',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\search_api_autocomplete\SearchApiAutocompleteSearchInterface $search */
    $search = $form_state->getFormObject()->getEntity();
    $views_id = substr($search->id(), 17);
    $view = ViewsViews::getView($views_id);
    $options = [];
    $view->initDisplay();
    foreach ($view->displayHandlers as $id => $display) {
      /** @var \Drupal\views\Plugin\views\display\DisplayPluginBase $display */
      $options[$id] = $display->display['display_title'];
    }
    $form['display'] = [
      '#type' => 'select',
      '#title' => $this->t('Views display'),
      '#description' => $this->t('Please select the Views display whose settings should be used for autocomplete queries.<br />' .
        "<strong>Note:</strong> Autocompletion doesn't work well with contextual filters. Please see the <a href=':readme_url'>README.txt</a> file for details.",
        [':readme_url' => Url::fromUri('base://' . drupal_get_path('module', 'search_api_autocomplete') . '/README.txt')->toString()]),
      '#options' => $options,
      '#default_value' => $search->getOption('custom.display') ?: 'default',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\search_api_autocomplete\SearchApiAutocompleteSearchInterface $search */
    $search = $form_state->getFormObject()->getEntity();
    $views_id = substr($search->id(), 17);
    $view = ViewsViews::getView($views_id);
    $view->setDisplay($form_state->getValue('display'));
    $view->preExecute();
    if ($view->argument) {
      drupal_set_message(t('You have selected a display with contextual filters. This can lead to various problems. Please see the <a href=":readme_url">README.txt</a> file for details.',
        [':readme_url' => Url::fromUri('base://' . drupal_get_path('module', 'search_api_autocomplete') . '/README.txt')->toString()]), 'warning');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function listSearches(IndexInterface $index) {
    $ret = [];
    $base_table = 'search_api_index_' . $index->id();
    foreach (ViewsViews::getAllViews() as $id => $view) {
      if ($view->get('base_table') === $base_table) {
        // @todo Check whether there is an exposed fulltext filter
        $ret['search_api_views_' . $id] = [
          'name' => $id,
        ];
      }
    }
    return $ret;
  }

  /**
   * {@inheritdoc}
   */
  public function createQuery(SearchApiAutocompleteSearchInterface $search, $complete, $incomplete) {
    $views_id = substr($search->id(), 17);
    $view = ViewsViews::getView($views_id);
    if (!$view) {
      $vars['@view'] = $views_id;
      throw new SearchApiException($this->t('Could not load view @view.', $vars));
    }
    $view->setDisplay($search->getOption('custom.display'));
    $view->preExecute();
    $view->build();
    /** @var \Drupal\search_api\Query\Query $query */
    $query = $view->getQuery()->getSearchApiQuery();
    if (!$query) {
      $vars['@view'] = $view->storage->label() ?: $views_id;
      throw new SearchApiException($this->t('Could not create query for view @view.', $vars));
    }
    // $query->setFulltextFields([$complete]);.
    // @todo What are the right values to use here?
    $query->setFulltextFields();
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

}
