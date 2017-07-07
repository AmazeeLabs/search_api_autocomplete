<?php

namespace Drupal\search_api_autocomplete\Suggester;

use Drupal\search_api_autocomplete\Plugin\SearchPluginBase;
use Drupal\search_api_autocomplete\SearchInterface;

/**
 * Provides a base class for suggester plugins.
 *
 * Plugins extending this class need to define a plugin definition array through
 * annotation. These definition arrays may be altered through
 * hook_search_api_autocomplete_suggester_alter(). The definition includes the
 * following keys:
 * - id: The unique, system-wide identifier of the suggester plugin.
 * - label: The human-readable name of the suggester plugin, translated.
 * - description: A human-readable description for the suggester plugin,
 *   translated.
 * - default_weight: (optional) The default weight to assign to the suggester.
 *   Defaults to 0.
 *
 * A complete plugin definition should be written as in this example:
 *
 * @code
 * @SearchApiAutocompleteSuggester(
 *   id = "my_suggester",
 *   label = @Translation("My Suggester"),
 *   description = @Translation("Creates suggestions based on internet memes."),
 *   default_weight = -10,
 * )
 * @endcode
 *
 * @see \Drupal\search_api_autocomplete\Annotation\SearchApiAutocompleteSuggester
 * @see \Drupal\search_api_autocomplete\Suggester\SuggesterInterface
 * @see \Drupal\search_api_autocomplete\Suggester\SuggesterManager
 * @see plugin_api
 */
abstract class SuggesterPluginBase extends SearchPluginBase implements SuggesterInterface {

  /**
   * {@inheritdoc}
   */
  public static function supportsSearch(SearchInterface $search) {
    return TRUE;
  }

}
