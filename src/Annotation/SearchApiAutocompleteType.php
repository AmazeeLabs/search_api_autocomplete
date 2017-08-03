<?php

namespace Drupal\search_api_autocomplete\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an autocompletion type.
 *
 * @see \Drupal\search_api_autocomplete\Type\TypeInterface
 * @see \Drupal\search_api_autocomplete\Type\TypeManager
 * @see \Drupal\search_api_autocomplete\Type\TypePluginBase
 * @see plugin_api
 *
 * @Annotation
 */
class SearchApiAutocompleteType extends Plugin {

  /**
   * The type label.
   *
   * @var string
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The type description.
   *
   * @var string
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The type's group label.
   *
   * @var string
   *
   * @ingroup plugin_translatable
   */
  public $group_label = NULL;

  /**
   * The type's group's description.
   *
   * @var string
   *
   * @ingroup plugin_translatable
   */
  public $group_description = NULL;

  /**
   * The type's index ID.
   *
   * @var string
   */
  public $index;

}
