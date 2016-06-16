<?php

namespace Drupal\search_api_autocomplete\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Session\AccountInterface;
use Drupal\search_api\SearchApiException;
use Drupal\search_api_autocomplete\Entity\SearchApiAutocompleteSearch;
use Symfony\Component\HttpFoundation\JsonResponse;

class AutocompleteController {

  /**
   * Page callback for getting autocomplete suggestions.
   *
   * @param \Drupal\search_api_autocomplete\Entity\SearchApiAutocompleteSearch $search_api_autocomplete_settings
   *   The search for which to retrieve autocomplete suggestions.
   * @param string $fields
   *   A comma-separated list of fields on which to do autocompletion. Or "-"
   *   to use all fulltext fields.
   * @param string $keys
   *   The user input so far.
   *
   * @return \Drupal\Core\Cache\CacheableJsonResponse
   *   The autocompletion response.
   */
  public function autocomplete(SearchApiAutocompleteSearch $search_api_autocomplete_settings, $fields, $keys = '') {
    $matches = [];
    try {
      if ($search_api_autocomplete_settings->supportsAutocompletion()) {
        list($complete, $incomplete) = $search_api_autocomplete_settings->splitKeys($keys);
        $query = $search_api_autocomplete_settings->getQuery($complete, $incomplete);
        if ($query) {
          // @todo Maybe make range configurable?
          $query->range(0, 10);
          $query->setOption('search id', 'search_api_autocomplete:' . $search_api_autocomplete_settings->id());
          if (!empty($search_api_autocomplete_settings->getOption('fields'))) {
            $query->setFulltextFields($search_api_autocomplete_settings->getOption('fields'));
          }
          elseif ($fields != '-') {
            $fields = explode(' ', $fields);
            $query->setFulltextFields($fields);
          }
          $query->preExecute();
          $suggestions = $search_api_autocomplete_settings->getSuggester()->getAutocompleteSuggestions($query, $incomplete, $keys);
          if ($suggestions) {
            foreach ($suggestions as $suggestion) {
              // Convert suggestion strings into an array.
              if (is_string($suggestion)) {
                $pos = strpos($suggestion, $keys);
                if ($pos === FALSE) {
                  $suggestion = [
                    'user_input' => '',
                    'suggestion_suffix' => $suggestion,
                  ];
                }
                else {
                  $suggestion = [
                    'suggestion_prefix' => substr($suggestion, 0, $pos),
                    'user_input' => $keys,
                    'suggestion_suffix' => substr($suggestion, $pos + strlen($keys)),
                  ];
                }
              }
              // Add defaults.
              $suggestion += [
                'url' => NULL,
                'keys' => NULL,
                'prefix' => NULL,
                'suggestion_prefix' => '',
                'user_input' => $keys,
                'suggestion_suffix' => '',
                'results' => NULL,
              ];
              if (empty($search_api_autocomplete_settings->getOption('results'))) {
                unset($suggestion['results']);
              }

              // Decide what the action of the suggestion is – entering specific
              // search terms or redirecting to a URL.
              if (isset($suggestion['url'])) {
                $key = ' ' . $suggestion['url'];
              }
              else {
                // Also set the "keys" key so it will always be available in alter
                // hooks and the theme function.
                if (!isset($suggestion['keys'])) {
                  $suggestion['keys'] = $suggestion['suggestion_prefix'] . $suggestion['user_input'] . $suggestion['suggestion_suffix'];
                }
                $key = trim($suggestion['keys']);
              }

              if (!isset($ret[$key])) {
                $ret[$key] = $suggestion;
              }
            }

            $alter_params = [
              'query' => $query,
              'search' => $search_api_autocomplete_settings,
              'incomplete_key' => $incomplete,
              'user_input' => $keys,
            ];
            \Drupal::moduleHandler()->alter('search_api_autocomplete_suggestions', $ret, $alter_params);

            foreach ($ret as $key => $suggestion) {
              if (isset($suggestion['render'])) {
                $matches[] = [
                  'value' => $key,
                  'label' => \Drupal::service('renderer')->render($suggestion['render']),
                ];
              }
              else {
                $ret[$key] = [
                  '#theme' => 'search_api_autocomplete_suggestion',
                ]
                  //  Convert the suggestion into a suitable variable for
                  // templates, by adding # in front.
                  + array_combine(array_map(function ($key) {
                    return '#' . $key;
                  }, array_keys($suggestion)), array_values($suggestion));
                $matches[] = [
                  'value' => $key,
                  'label' => \Drupal::service('renderer')->render($ret[$key]),
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

    return new JsonResponse($matches);
  }

  /**
   * Checks access to the autocompletion route.
   *
   * @param \Drupal\search_api_autocomplete\Entity\SearchApiAutocompleteSearch $search_api_autocomplete_settings
   *   The configured autocompletion search.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function access(SearchApiAutocompleteSearch $search_api_autocomplete_settings, AccountInterface $account) {
    $access = AccessResult::allowedIf($search_api_autocomplete_settings->status())
      ->andIf(AccessResult::allowedIfHasPermission($account, 'use search_api_autocomplete for ' . $search_api_autocomplete_settings->id()))
      ->andIf(AccessResult::allowedIf($search_api_autocomplete_settings->supportsAutocompletion()));
    return $access;
  }

}
