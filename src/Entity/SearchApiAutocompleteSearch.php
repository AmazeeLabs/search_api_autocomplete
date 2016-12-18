<?php

namespace Drupal\search_api_autocomplete\Entity;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\search_api\Entity\Index;
use Drupal\search_api_autocomplete\SearchApiAutocompleteSearchInterface;

/**
 * Describes the autocomplete settings for a certain search.
 *
 * @ConfigEntityType(
 *   id = "search_api_autocomplete_search",
 *   label = @Translation("Autocomplete search"),
 *   handlers = {
 *     "form" = {
 *       "default" = "\Drupal\search_api_autocomplete\Form\AutocompleteSearchEditForm",
 *       "edit" = "\Drupal\search_api_autocomplete\Form\AutocompleteSearchEditForm",
 *       "delete" = "\Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "\Drupal\Core\Entity\EntityListBuilder",
 *     "route_provider" = {
 *       "default" = "\Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer search_api_autocomplete",
 *   config_prefix = "search_api_autocomplete",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/search/search-api/index/autocomplete/{search_api_autocomplete_search}/edit",
 *     "delete-form" = "/admin/config/search/search-api/index/autocomplete/{search_api_autocomplete_search}/delete",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "index_id",
 *     "suggester_id",
 *     "status",
 *     "type",
 *     "options",
 *   }
 * )
 */
class SearchApiAutocompleteSearch extends ConfigEntityBase implements SearchApiAutocompleteSearchInterface {

  /**
   * The entity ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The entity label.
   *
   * @var string
   */
  protected $label;

  /**
   * The index ID.
   *
   * @var string
   */
  protected $index_id;

  /**
   * The suggester ID.
   *
   * @var string
   */
  protected $suggester_id;

  /**
   * The autocomplete type.
   *
   * @var string
   */
  protected $type;

  /**
   * An array of options for this search, containing any of the following:
   * - results: Boolean indicating whether to also list the estimated number of
   *   results for each suggestion (if possible).
   * - fields: Array containing the fulltext fields to use for autocompletion.
   * - custom: An array of type-specific settings.
   *
   * @var array
   */
  protected $options = [];

  /**
   * The searchapi index instance.
   *
   * @var \Drupal\search_api\IndexInterface
   */
  protected $index;

  /**
   * The searchapi server instance.
   *
   * @var \Drupal\search_api\ServerInterface
   */
  protected $server;

  /**
   * The suggester plugin this search uses.
   *
   * @var \Drupal\search_api_autocomplete\Suggester\SuggesterInterface
   */
  protected $suggester;

  /**
   * @return \Drupal\search_api\IndexInterface
   *   The index this search belongs to.
   */
  public function index() {
    if (!isset($this->index)) {
      $this->index = Index::load($this->index_id);
      if (!$this->index) {
        $this->index = FALSE;
      }
    }
    return $this->index;
  }

  /**
   * Retrieves the server this search would at the moment be executed on.
   *
   * @return \Drupal\search_api\ServerInterface
   *   The server this search would at the moment be executed on.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   If a server is set for the index but it doesn't exist.
   */
  public function server() {
    if (!isset($this->server)) {
      if (!$this->index() || !$this->index()->getServerInstance()) {
        $this->server = FALSE;
      }
      else {
        $this->server = $this->index()->getServerInstance();
        if (!$this->server) {
          $this->server = FALSE;
        }
      }
    }
    return $this->server;
  }

  /**
   * {@inheritdoc}
   */
  public function getSuggesterId() {
    return $this->suggester_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setSuggesterId($suggester_id) {
    $this->suggester_id = $suggester_id;
    unset($this->suggester);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSuggester($reset = FALSE) {
    if (!isset($this->suggester) || $reset) {
      $configuration = !empty($this->options['suggester_configuration']) ? $this->options['suggester_configuration'] : [];
      $this->suggester = $this->getSuggesterManager()->createInstance($this->suggester_id, [
        'search' => $this,
      ] + $configuration);
      if (!$this->suggester) {
        $variables['@search'] = $this->id();
        $variables['@index'] = $this->index() ? $this->index()->label() : $this->index_id;
        $variables['@suggester_id'] = $this->suggester_id;
        $this->getLogger()->error('Autocomplete search @search on index @index specifies an invalid suggester plugin @suggester_id.', $variables);
        $this->suggester = FALSE;
      }
    }
    return $this->suggester ? $this->suggester : NULL;
  }

  /**
   * Returns the autocomplete suggester plugin manager.
   *
   * @return \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected function getSuggesterManager() {
    return \Drupal::service('plugin_manager.search_api_autocomplete_suggester');
  }

  /**
   * Returns a logger.
   *
   * @return \Psr\Log\LoggerInterface
   */
  protected function getLogger() {
    return \Drupal::logger('search_api_autocomplete');
  }

  /**
   * Determines whether autocompletion is currently supported for this search.
   *
   * @return bool
   *   TRUE if autocompletion is possible for this search with the current
   *   settings; FALSE otherwise.
   */
  public function supportsAutocompletion() {
    return $this->index() && $this->getSuggester() && $this->getSuggester()->supportsIndex($this->index());
  }

  /**
   * Create the query that would be issued for this search for the complete keys.
   *
   * @param $complete
   *   A string containing the complete search keys.
   * @param $incomplete
   *   A string containing the incomplete last search key.
   *
   * @return \Drupal\search_api\Query\QueryInterface
   *   The query that would normally be executed when only $complete was entered
   *   as the search keys for this search.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   If the query couldn't be created.
   */
  public function getQuery($complete, $incomplete) {
    $type = $this->getTypeInstance();
    $query = $type->createQuery($this, $complete, $incomplete);
    if ($complete && !$query->getKeys()) {
      $query->keys($complete);
    }
    return $query;
  }

  /**
   * Returns the autocomplete instance for this autocomplete search.
   *
   * @return \Drupal\search_api_autocomplete\Type\TypeInterface
   */
  protected function getTypeInstance() {
    return \Drupal::service('plugin_manager.search_api_autocomplete_type')->createInstance($this->getType());
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->label = $label;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getIndexId() {
    return $this->index_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getIndexInstance() {
    if (!isset($this->index)) {
      $this->index = Index::load($this->getIndexId());
    }
    return $this->index;
  }

  /**
   * {@inheritdoc}
   */
  public function setIndexId($index_id) {
    $this->index_id = $index_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function setType($type) {
    $this->type = $type;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    return $this->options;
  }

  /**
   * {@inheritdoc}
   */
  public function setOptions($options) {
    $this->options = $options;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOption($key, $default = NULL) {
    $parts = explode('.', $key);
    return NestedArray::getValue($this->options, $parts) ?: $default;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    $this->addDependency($this->index()->getConfigDependencyKey(), $this->index()->getConfigDependencyName());
    return $this;
  }

}
