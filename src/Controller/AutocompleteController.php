<?php

namespace Drupal\search_api_autocomplete\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultReasonInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\search_api_autocomplete\AutocompleteFormUtility;
use Drupal\search_api_autocomplete\SearchApiAutocompleteException;
use Drupal\search_api_autocomplete\SearchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a controller for autocompletion.
 */
class AutocompleteController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Creates a new AutocompleteController instance.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer')
    );
  }

  /**
   * Page callback: Retrieves autocomplete suggestions.
   *
   * @param \Drupal\search_api_autocomplete\SearchInterface $search_api_autocomplete_search
   *   The search for which to retrieve autocomplete suggestions.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The autocompletion response.
   */
  public function autocomplete(SearchInterface $search_api_autocomplete_search, Request $request) {
    $matches = [];
    $search = $search_api_autocomplete_search;

    if (!$search->status()|| !$search->hasValidIndex()) {
      return new JsonResponse($matches);
    }

    try {
      $keys = $request->query->get('q');
      list($complete, $incomplete) = AutocompleteFormUtility::splitKeys($keys);
      $data = $request->query->all();
      unset($data['q']);
      $query = $search->createQuery($complete, $data);
      if (!$query) {
        return new JsonResponse($matches);
      }

      // Prepare the query.
      $query->setSearchId('search_api_autocomplete:' . $search->id());
      $query->preExecute();

      // Get total limit and per-suggester limits.
      $limit = $search->getOption('limit', 10);
      $suggester_limits = $search->getSuggesterLimits();

      // Get all enabled suggesters, ordered by weight.
      $suggesters = $search->getSuggesters();
      $suggester_weights = $search->getSuggesterWeights();
      $suggester_weights = array_intersect_key($suggester_weights, $suggesters);
      $suggester_weights += array_fill_keys(array_keys($suggesters), 0);
      asort($suggester_weights);

      /** @var \Drupal\search_api_autocomplete\SuggestionInterface[] $suggestions */
      $suggestions = [];
      // Go through all enabled suggesters in order of increasing weight and
      // add their suggestions (until the limit is reached).
      foreach (array_keys($suggester_weights) as $suggester_id) {
        // Clone the query in case the suggester makes any modifications.
        $tmp_query = clone $query;

        // Compute the applicable limit based on (remaining) total limit and
        // the suggester-specific limit (if set).
        if (isset($suggester_limits[$suggester_id])) {
          $suggester_limit = min($limit, $suggester_limits[$suggester_id]);
        }
        else {
          $suggester_limit = $limit;
        }
        $tmp_query->range(0, $suggester_limit);

        // Add suggestions in a loop so we're sure they're numerically
        // indexed.
        $new_suggestions = $suggesters[$suggester_id]
          ->getAutocompleteSuggestions($tmp_query, $incomplete, $keys);
        foreach ($new_suggestions as $suggestion) {
          // Decide what the action of the suggestion is – entering specific
          // search terms or redirecting to a URL.
          if ($suggestion->getUrl()) {
            $key = ' ' . $suggestion->getUrl()->toString();
          }
          else {
            $key = trim($suggestion->getKeys());
          }

          if (!isset($suggestions[$key])) {
            $suggestions[$key] = $suggestion;
          }

          if (--$limit == 0) {
            // If we've reached the limit, stop adding suggestions.
            break 2;
          }
        }
      }

      // Allow other modules to alter the created suggestions.
      $alter_params = [
        'query' => $query,
        'search' => $search,
        'incomplete_key' => $incomplete,
        'user_input' => $keys,
      ];
      $this->moduleHandler()
        ->alter('search_api_autocomplete_suggestions', $suggestions, $alter_params);

      // Then, add the suggestions to the $matches return array in the form
      // expected by Drupal's autocomplete Javascript.
      $show_count = $search->getOption('show_count');
      foreach ($suggestions as $suggestion) {
        // If "Display result count estimates" was disabled, remove the
        // count from the suggestion.
        if (!$show_count) {
          $suggestion->setResults(NULL);
        }
        if ($build = $suggestion->toRenderable()) {
          $matches[] = [
            'value' => $suggestion->getKeys(),
            'label' => $this->renderer->render($build),
          ];
        }
      }
    }
    catch (SearchApiAutocompleteException $e) {
      watchdog_exception('search_api_autocomplete', $e, '%type while retrieving autocomplete suggestions: !message in %function (line %line of %file).');
    }

    return new JsonResponse($matches);
  }

  /**
   * Checks access to the autocompletion route.
   *
   * @param \Drupal\search_api_autocomplete\SearchInterface $search_api_autocomplete_search
   *   The configured autocompletion search.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(SearchInterface $search_api_autocomplete_search, AccountInterface $account) {
    $search = $search_api_autocomplete_search;
    $permission = 'use search_api_autocomplete for ' . $search->id();
    $access = AccessResult::allowedIf($search->status())
      ->andIf(AccessResult::allowedIf($search->hasValidIndex() && $search->getIndex()->status()))
      ->andIf(AccessResult::allowedIfHasPermissions($account, [$permission, 'administer search_api_autocomplete'], 'OR'))
      ->addCacheableDependency($search);
    if ($access instanceof AccessResultReasonInterface) {
      $access->setReason("The \"$permission\" permission is required and autocomplete for this search must be enabled.");
    }
    return $access;
  }

}
