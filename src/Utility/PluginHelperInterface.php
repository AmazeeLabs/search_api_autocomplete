<?php

namespace Drupal\search_api_autocomplete\Utility;

use Drupal\search_api_autocomplete\SearchInterface;

/**
 * Defines an interface for the autocomplete search "plugin helper" service.
 */
interface PluginHelperInterface {

  /**
   * Creates a suggester plugin object for the given search.
   *
   * @param \Drupal\search_api_autocomplete\SearchInterface $search
   *   The search for which to create the plugin.
   * @param string $plugin_id
   *   The plugin's ID.
   * @param array $configuration
   *   (optional) The configuration to set for the plugin.
   *
   * @return \Drupal\search_api_autocomplete\Suggester\SuggesterInterface
   *   The new suggester plugin object.
   *
   * @throws \Drupal\search_api_autocomplete\SearchApiAutocompleteException
   *   Thrown if an unknown $type or $plugin_id is given.
   */
  public function createSuggesterPlugin(SearchInterface $search, $plugin_id, array $configuration = []);

  /**
   * Creates a type plugin object for the given search.
   *
   * @param \Drupal\search_api_autocomplete\SearchInterface $search
   *   The search for which to create the plugin.
   * @param string $plugin_id
   *   The plugin's ID.
   * @param array $configuration
   *   (optional) The configuration to set for the plugin.
   *
   * @return \Drupal\search_api_autocomplete\type\TypeInterface
   *   The new type plugin object.
   *
   * @throws \Drupal\search_api_autocomplete\SearchApiAutocompleteException
   *   Thrown if an unknown $type or $plugin_id is given.
   */
  public function createTypePlugin(SearchInterface $search, $plugin_id, array $configuration = []);

  /**
   * Creates multiple suggester plugin objects for the given search.
   *
   * @param \Drupal\search_api_autocomplete\SearchInterface $search
   *   The search for which to create the plugins.
   * @param string[]|null $plugin_ids
   *   (optional) The IDs of the plugins to create, or NULL to create instances
   *   for all known plugins of this type.
   * @param array $configurations
   *   (optional) The configurations to set for the plugins, keyed by plugin ID.
   *   Missing configurations are either taken from the search's stored settings,
   *   if they are present there, or default to an empty array.
   *
   * @return \Drupal\search_api_autocomplete\Suggester\SuggesterInterface[]
   *   The created suggester plugin objects.
   *
   * @throws \Drupal\search_api_autocomplete\SearchApiAutocompleteException
   *   Thrown if an unknown plugin ID is given.
   */
  public function createSuggesterPlugins(SearchInterface $search, array $plugin_ids = NULL, array $configurations = []);

  /**
   * Creates multiple type plugin objects for the given search.
   *
   * @param \Drupal\search_api_autocomplete\SearchInterface $search
   *   The search for which to create the plugins.
   * @param string[]|null $plugin_ids
   *   (optional) The IDs of the types to create, or NULL to create
   *   instances for all known types that support the given search.
   * @param array $configurations
   *   (optional) The configurations to set for the plugins, keyed by plugin ID.
   *   Missing configurations are either taken from the search's stored settings,
   *   if they are present there, or default to an empty array.
   *
   * @return \Drupal\search_api_autocomplete\type\TypeInterface[]
   *   The created type plugin objects.
   *
   * @throws \Drupal\search_api_autocomplete\SearchApiAutocompleteException
   *   Thrown if an unknown plugin ID is given.
   */
  public function createTypePlugins(SearchInterface $search, array $plugin_ids = NULL, array $configurations = []);

  /**
   * Creates objects for all type plugins associated with the given index.
   *
   * Type plugins are first filtered by their "index" definition key and then
   * via their getIndexId() method.
   *
   * @param string $index_id
   *   The ID of the search index for which to create type plugins.
   *
   * @return \Drupal\search_api_autocomplete\type\TypeInterface[]
   *   The created type plugin objects.
   */
  public function createTypePluginsForIndex($index_id);

}
