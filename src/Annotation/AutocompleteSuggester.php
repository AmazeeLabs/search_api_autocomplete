<?php

namespace Drupal\search_api_autocomplete\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * @Annotation
 */
class AutocompleteSuggester extends Plugin {

  public $label;

  public $description;

}
