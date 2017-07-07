<?php

namespace Drupal\search_api_autocomplete\Type;

use Drupal\search_api_autocomplete\Plugin\SearchPluginBase;

/**
 * Provides a base class for type plugins.
 *
 * Plugins extending this class need to define a plugin definition array through
 * annotation. These definition arrays may be altered through
 * hook_search_api_autocomplete_type_alter(). The definition includes the
 * following keys:
 * - id: The unique, system-wide identifier of the type plugin.
 * - label: The human-readable name of the type plugin, translated.
 * - description: A human-readable description for the type plugin,
 *   translated.
 *
 * A complete plugin definition should be written as in this example:
 *
 * @code
 * @SearchApiAutocompleteType(
 *   id = "my_type",
 *   label = @Translation("My Type"),
 *   description = @Translation("Custom-defined searches for this site."),
 * )
 * @endcode
 *
 * @see \Drupal\search_api_autocomplete\Annotation\SearchApiAutocompleteType
 * @see \Drupal\search_api_autocomplete\Type\TypeInterface
 * @see \Drupal\search_api_autocomplete\Type\TypeManager
 * @see plugin_api
 */
abstract class TypePluginBase extends SearchPluginBase implements TypeInterface {}
