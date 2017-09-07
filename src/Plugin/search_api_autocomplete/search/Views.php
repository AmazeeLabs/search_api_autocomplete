<?php

namespace Drupal\search_api_autocomplete\Plugin\search_api_autocomplete\search;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\search_api\Plugin\views\query\SearchApiQuery;
use Drupal\search_api_autocomplete\SearchApiAutocompleteException;
use Drupal\search_api_autocomplete\Search\SearchPluginBase;
use Drupal\views\ViewEntityInterface;
use Drupal\views\ViewExecutableFactory;
use Drupal\views\Views as ViewsViews;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides autocomplete support for Views search.
 *
 * @SearchApiAutocompleteSearch(
 *   id = "views",
 *   group_label = @Translation("Search views"),
 *   group_description = @Translation("Searches provided by Views"),
 *   provider = "search_api",
 *   deriver = "Drupal\search_api_autocomplete\Plugin\search_api_autocomplete\search\ViewsDeriver"
 * )
 */
class Views extends SearchPluginBase implements PluginFormInterface {

  use PluginFormTrait;

  /**
   * The views executable factory.
   *
   * @var \Drupal\views\ViewExecutableFactory|null
   */
  protected $viewsExecutableFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $plugin */
    $plugin = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $plugin->setViewsExecutableFactory($container->get('views.executable'));

    return $plugin;
  }

  /**
   * Retrieves the Views executable factory.
   *
   * @return \Drupal\views\ViewExecutableFactory
   *   The Views executable factory.
   */
  public function getViewsExecutableFactory() {
    return $this->viewsExecutableFactory ?: \Drupal::service('views.executable');
  }

  /**
   * Sets the Views executable factory.
   *
   * @param \Drupal\views\ViewExecutableFactory $views_executable_factory
   *   The new Views executable factory.
   *
   * @return $this
   */
  public function setViewsExecutableFactory(ViewExecutableFactory $views_executable_factory) {
    $this->viewsExecutableFactory = $views_executable_factory;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'displays' => [
        'default' => TRUE,
        'selected' => [],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $view = ViewsViews::getView($this->getDerivativeId());
    if (!$view) {
      return [];
    }
    $options = [];
    $view->initDisplay();
    foreach ($view->displayHandlers as $id => $display) {
      /** @var \Drupal\views\Plugin\views\display\DisplayPluginBase $display */
      $options[$id] = $display->display['display_title'];
    }

    $form['displays']['default'] = [
      '#type' => 'radios',
      '#title' => $this->t('For which Views displays should Autocomplete be active?'),
      '#options' => [
        1 => $this->t('All except those selected'),
        0 => $this->t('None except those selected'),
      ],
      '#default_value' => (int) $this->configuration['displays']['default'],
    ];
    $form['displays']['selected'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Displays'),
      '#options' => $options,
      '#default_value' => $this->configuration['displays']['selected'],
      '#size' => min(4, count($options)),
      '#multiple' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Filter out empty checkboxes.
    $parents = ['displays', 'selected'];
    $value = $form_state->getValue($parents, []);
    $value = array_keys(array_filter($value));
    $form_state->setValue($parents, $value);

    $this->setConfiguration($form_state->getValues());
  }

  /**
   * {@inheritdoc}
   */
  public function createQuery($keys, array $data = []) {
    $views_id = $this->getDerivativeId();
    $view = $this->getEntityTypeManager()->getStorage('view')->load($views_id);
    if ($view instanceof ViewEntityInterface) {
      $view = $this->getViewsExecutableFactory()->get($view);
    }
    if (!$view) {
      $vars['@view'] = $views_id;
      throw new SearchApiAutocompleteException($this->t('Could not load view @view.', $vars));
    }

    $data += [
      'display' => NULL,
      'arguments' => [],
    ];

    $view->setDisplay($data['display']);
    $view->setArguments($data['arguments']);

    // If we know the filter's identifier, use that to get the correct keys
    // placed on the query.
    if (!empty($data['filter'])) {
      $view->setExposedInput([
        $data['filter'] => $keys,
      ]);
    }

    $view->preExecute();
    $view->build();

    $query_plugin = $view->getQuery();
    if (!($query_plugin instanceof SearchApiQuery)) {
      $views_label = $view->storage->label() ?: $views_id;
      throw new SearchApiAutocompleteException("Could not create search query for view '$views_label': view is not based on Search API.");
    }
    $query = $query_plugin->getSearchApiQuery();
    if (!$query) {
      $views_label = $view->storage->label() ?: $views_id;
      throw new SearchApiAutocompleteException("Could not create search query for view '$views_label'.");
    }

    if (!empty($data['fields'])) {
      $query->setFulltextFields($data['fields']);
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $this->dependencies = parent::calculateDependencies();

    $view_id = $this->getDerivativeId();
    $view = $this->getEntityTypeManager()->getStorage('view')->load($view_id);
    if ($view) {
      $key = $view->getConfigDependencyKey();
      $name = $view->getConfigDependencyName();
      $this->addDependency($key, $name);
    }

    return $this->dependencies;
  }

}
