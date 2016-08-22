<?php

namespace Drupal\search_api_autocomplete\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\search_api\SearchApiException;
use Drupal\search_api_autocomplete\AutocompleteFormUtility;
use Drupal\search_api_autocomplete\SearchApiAutocompleteSearchInterface;
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
   * Page callback for getting autocomplete suggestions.
   *
   * @param \Drupal\search_api_autocomplete\SearchApiAutocompleteSearchInterface $search_api_autocomplete_search
   *   The search for which to retrieve autocomplete suggestions.
   * @param string $fields
   *   A comma-separated list of fields on which to do autocompletion. Or "-"
   *   to use all fulltext fields.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The autocompletion response.
   */
  public function autocomplete(SearchApiAutocompleteSearchInterface $search_api_autocomplete_search, $fields, Request $request) {
    $matches = [];
    $autocomplete_utility = new AutocompleteFormUtility($this->renderer);
    try {
      if ($search_api_autocomplete_search->supportsAutocompletion()) {
        $keys = $request->query->get('q');
        list($complete, $incomplete) = $autocomplete_utility->splitKeys($keys);
        $query = $search_api_autocomplete_search->getQuery($complete, $incomplete);
        if ($query) {
          // @todo Maybe make range configurable?
          $query->range(0, 10);
          $query->setOption('search id', 'search_api_autocomplete:' . $search_api_autocomplete_search->id());
          if (!empty($search_api_autocomplete_search->getOption('fields'))) {
            $query->setFulltextFields($search_api_autocomplete_search->getOption('fields'));
          }
          elseif ($fields != '-') {
            $fields = explode(' ', $fields);
            $query->setFulltextFields($fields);
          }
          $query->preExecute();
          $suggestions = $search_api_autocomplete_search->getSuggester()->getAutocompleteSuggestions($query, $incomplete, $keys);
          if ($suggestions) {
            foreach ($suggestions as $suggestion) {
              if (empty($search_api_autocomplete_search->getOption('results'))) {
                $suggestion->setResults(NULL);
              }

              // Decide what the action of the suggestion is – entering specific
              // search terms or redirecting to a URL.
              if ($suggestion->getUrl()) {
                $key = ' ' . $suggestion->getUrl();
              }
              else {
                // Also set the "keys" key so it will always be available in
                // alter hooks and the theme function.
                if (!$suggestion->getKeys()) {
                  $suggestion->setKeys($suggestion->getSuggestionPrefix() . $suggestion->getUserInput() . $suggestion->getSuggestionSuffix());
                }
                $key = trim($suggestion->getKeys());
              }

              if (!isset($ret[$key])) {
                $ret[$key] = $suggestion;
              }
            }

            $alter_params = [
              'query' => $query,
              'search' => $search_api_autocomplete_search,
              'incomplete_key' => $incomplete,
              'user_input' => $keys,
            ];
            $this->moduleHandler()->alter('search_api_autocomplete_suggestions', $ret, $alter_params);

            /*** @var \Drupal\search_api_autocomplete\SuggestionInterface $suggestion */
            foreach ($ret as $key => $suggestion) {
              if ($build = $suggestion->toRenderable()) {
                $matches[] = [
                  'value' => $key,
                  'label' => $this->renderer->render($build),
                ];
              }
            }
          }
        }
      }
    }
    catch (SearchApiException $e) {
      watchdog_exception('search_api_autocomplete', $e, '%type while retrieving autocomplete suggestions: !message in %function (line %line of %file).');
    }

    // @todo Get cacheability metadata from search_api and use
    //   \Drupal\Core\Cache\CacheableJsonResponse instead.
    return new JsonResponse($matches);
  }

  /**
   * Checks access to the autocompletion route.
   *
   * @param \Drupal\search_api_autocomplete\SearchApiAutocompleteSearchInterface $search_api_autocomplete_search
   *   The configured autocompletion search.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function access(SearchApiAutocompleteSearchInterface $search_api_autocomplete_search, AccountInterface $account) {
    $access = AccessResult::allowedIf($search_api_autocomplete_search->status())
      ->andIf(AccessResult::allowedIfHasPermission($account, 'use search_api_autocomplete for ' . $search_api_autocomplete_search->id()))
      ->andIf(AccessResult::allowedIf($search_api_autocomplete_search->supportsAutocompletion()));
    return $access;
  }

}
