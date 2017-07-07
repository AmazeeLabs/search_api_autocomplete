<?php

namespace Drupal\search_api_autocomplete\Plugin;

use Drupal\search_api\Plugin\ConfigurablePluginInterface;
use Drupal\search_api_autocomplete\SearchInterface;

/**
 * Provides a common interface for autocomplete search plugins.
 */
interface SearchPluginInterface extends ConfigurablePluginInterface {

  /**
   * Retrieves the search this plugin is configured for.
   *
   * @return \Drupal\search_api_autocomplete\SearchInterface
   *   The search this plugin is configured for.
   */
  public function getSearch();

  /**
   * Sets the search this plugin is configured for.
   *
   * @param \Drupal\search_api_autocomplete\SearchInterface $search
   *   The new search entity this plugin should be linked to.
   *
   * @return $this
   */
  public function setSearch(SearchInterface $search);

}
