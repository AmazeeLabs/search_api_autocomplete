<?php

namespace Drupal\search_api_autocomplete\Suggester;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Plugin\ConfigurablePluginInterface;
use Drupal\search_api_autocomplete\SearchApiAutocompleteSearchInterface;

/**
 * Provides a base class for suggester plugins.
 *
 * @see \Drupal\search_api_autocomplete\Suggester\SuggesterInterface
 * @see \Drupal\search_api_autocomplete\Suggester\SuggesterManager
 * @see \Drupal\search_api_autocomplete\Annotation\SearchApiAutocompleteSuggester
 */
abstract class SuggesterPluginBase extends PluginBase implements SuggesterInterface, ConfigurablePluginInterface {

  /**
   * The search this suggester is attached to.
   *
   * @var \Drupal\search_api_autocomplete\SearchApiAutocompleteSearchInterface
   */
  protected $search;

  /**
   * The suggester plugin's ID.
   *
   * @var string
   */
  protected $pluginId;

  /**
   * The suggester plugin's definition.
   *
   * @var array
   */
  protected $pluginDefinition = [];

  /**
   * The suggester plugin's configuration.
   *
   * @var array
   */
  protected $configuration = [];

  /**
   * {@inheritdoc}
   */
  public static function supportsIndex(IndexInterface $index) {
    return TRUE;
  }

  /**
   * Constructs a SuggesterPluginBase object.
   *
   * @param \Drupal\search_api_autocomplete\SearchApiAutocompleteSearchInterface $search
   *   The search to which this suggester is attached.
   * @param array $configuration
   *   An associative array containing the suggester's configuration, if any.
   * @param string $plugin_id
   *   The suggester's plugin ID.
   * @param array $plugin_definition
   *   The suggester plugin's definition.
   */
  public function __construct(SearchApiAutocompleteSearchInterface $search, array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->setConfiguration($configuration);
    $this->search = $search;
  }

  /**
   * {@inheritdoc}
   */
  public function getSearch() {
    return $this->search;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return isset($this->pluginDefinition['description']) ? $this->pluginDefinition['description'] : NULL;
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
    $this->configuration = $configuration + $this->defaultConfiguration();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->setConfiguration($form_state->getValues());
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

}
