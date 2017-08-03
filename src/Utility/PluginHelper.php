<?php

namespace Drupal\search_api_autocomplete\Utility;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\search_api_autocomplete\SearchApiAutocompleteException;
use Drupal\search_api_autocomplete\SearchInterface;
use Drupal\search_api_autocomplete\Suggester\SuggesterManager;
use Drupal\search_api_autocomplete\Type\TypeManager;

/**
 * Provides methods for creating autocomplete search plugins.
 */
class PluginHelper implements PluginHelperInterface {

  /**
   * The suggester plugin manager.
   *
   * @var \Drupal\search_api_autocomplete\Suggester\SuggesterManager
   */
  protected $suggesterPluginManager;

  /**
   * The type plugin manager.
   *
   * @var \Drupal\search_api_autocomplete\Type\TypeManager
   */
  protected $typePluginManager;

  /**
   * Constructs a PluginHelper object.
   *
   * @param \Drupal\search_api_autocomplete\Suggester\SuggesterManager $suggesterPluginManager
   *   The suggester plugin manager.
   * @param \Drupal\search_api_autocomplete\Type\TypeManager $typePluginManager
   *   The type plugin manager.
   */
  public function __construct(SuggesterManager $suggesterPluginManager, TypeManager $typePluginManager) {
    $this->suggesterPluginManager = $suggesterPluginManager;
    $this->typePluginManager = $typePluginManager;
  }

  /**
   * Creates a plugin object for the given search.
   *
   * @param \Drupal\search_api_autocomplete\SearchInterface $search
   *   The search for which to create the plugins.
   * @param string $type
   *   The type of plugin to create: "datasource", "processor" or "tracker".
   * @param string $plugin_id
   *   The plugin's ID.
   * @param array $configuration
   *   (optional) The configuration to set for the plugin.
   *
   * @return \Drupal\search_api_autocomplete\Plugin\SearchPluginInterface
   *   The new plugin object.
   *
   * @throws \Drupal\search_api_autocomplete\SearchApiAutocompleteException
   *   Thrown if an unknown $type or $plugin_id is given.
   */
  protected function createSearchPlugin(SearchInterface $search, $type, $plugin_id, array $configuration = []) {
    if (!isset($this->{$type . "PluginManager"})) {
      throw new SearchApiAutocompleteException("Unknown plugin type '$type'");
    }

    try {
      $configuration['#search'] = $search;
      return $this->{$type . "PluginManager"}->createInstance($plugin_id, $configuration);
    }
    catch (PluginException $e) {
      throw new SearchApiAutocompleteException("Unknown $type plugin with ID '$plugin_id'");
    }
  }

  /**
   * Creates multiple plugin objects for the given search.
   *
   * @param \Drupal\search_api_autocomplete\SearchInterface $search
   *   The search for which to create the plugins.
   * @param string $type
   *   The type of plugin to create: "datasource", "processor" or "tracker".
   * @param string[]|null $plugin_ids
   *   (optional) The IDs of the plugins to create, or NULL to create instances
   *   for all known plugins of this type.
   * @param array $configurations
   *   (optional) The configurations to set for the plugins, keyed by plugin ID.
   *   Missing configurations are either taken from the search's stored settings,
   *   if they are present there, or default to an empty array.
   *
   * @return \Drupal\search_api_autocomplete\Plugin\SearchPluginInterface[]
   *   The created plugin objects.
   *
   * @throws \Drupal\search_api_autocomplete\SearchApiAutocompleteException
   *   Thrown if an unknown $type or plugin ID is given.
   */
  protected function createSearchPlugins(SearchInterface $search, $type, array $plugin_ids = NULL, array $configurations = []) {
    if (!isset($this->{$type . "PluginManager"})) {
      throw new SearchApiAutocompleteException("Unknown plugin type '$type'");
    }

    if ($plugin_ids === NULL) {
      $plugin_ids = array_keys($this->{$type . "PluginManager"}->getDefinitions());
    }

    $plugins = [];
    $search_settings = $search->get($type . '_settings');
    foreach ($plugin_ids as $plugin_id) {
      $configuration = [];
      if (isset($configurations[$plugin_id])) {
        $configuration = $configurations[$plugin_id];
      }
      elseif (isset($search_settings[$plugin_id])) {
        $configuration = $search_settings[$plugin_id];
      }
      $plugins[$plugin_id] = $this->createSearchPlugin($search, $type, $plugin_id, $configuration);
    }

    return $plugins;
  }

  /**
   * {@inheritdoc}
   */
  public function createSuggesterPlugin(SearchInterface $search, $plugin_id, array $configuration = []) {
    return $this->createSearchPlugin($search, 'suggester', $plugin_id, $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function createTypePlugin(SearchInterface $search, $plugin_id, array $configuration = []) {
    return $this->createSearchPlugin($search, 'type', $plugin_id, $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function createSuggesterPlugins(SearchInterface $search, array $plugin_ids = NULL, array $configurations = []) {
    return $this->createSearchPlugins($search, 'suggester', $plugin_ids, $configurations);
  }

  /**
   * {@inheritdoc}
   */
  public function createTypePlugins(SearchInterface $search, array $plugin_ids = NULL, array $configurations = []) {
    return $this->createSearchPlugins($search, 'type', $plugin_ids, $configurations);
  }

  /**
   * {@inheritdoc}
   */
  public function createTypePluginsForIndex($index_id) {
    $definitions = $this->typePluginManager->getDefinitions();
    $types = [];
    foreach ($definitions as $type_id => $definition) {
      if (!empty($definition['index']) && $definition['index'] !== $index_id) {
        continue;
      }
      /** @var \Drupal\search_api_autocomplete\Type\TypeInterface $type */
      $type = $this->typePluginManager->createInstance($type_id);
      if ($type->getIndexId() === $index_id) {
        $types[$type_id] = $type;
      }
    }

    return $types;
  }

}
