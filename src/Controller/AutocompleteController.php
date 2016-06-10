<?php

namespace Drupal\search_api_autocomplete\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\search_api\SearchApiException;
use Drupal\search_api_autocomplete\Entity\SearchApiAutocompleteSearch;
use Symfony\Component\HttpFoundation\JsonResponse;

class AutocompleteController {

  /**
   * Page callback for getting autocomplete suggestions.
   *
   * @param \Drupal\search_api_autocomplete\Entity\SearchApiAutocompleteSearch $search
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
  public function autocomplete(SearchApiAutocompleteSearch $search, $fields, $keys = '') {
    $ret = array();
    try {
      if ($search->supportsAutocompletion()) {
        list($complete, $incomplete) = $search->splitKeys($keys);
        $query = $search->getQuery($complete, $incomplete);
        if ($query) {
          // @todo Maybe make range configurable?
          $query->range(0, 10);
          $query->setOption('search id', 'search_api_autocomplete:' . $search->id());
          if (!empty($search->getOption('fields'))) {
            // @fixme.
            $query->fields($search->getOption('fields'));
          }
          elseif ($fields != '-') {
            $fields = explode(' ', $fields);
            $query->fields($fields);
          }
          $query->preExecute();
          $suggestions = $search->getSuggester()->getAutocompleteSuggestions($query, $incomplete, $keys);
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
              if (empty($search->getOption('results'))) {
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
              'search' => $search,
              'incomplete_key' => $incomplete,
              'user_input' => $keys,
            ];
            \Drupal::moduleHandler()->alter('search_api_autocomplete_suggestions', $ret, $alter_params);

            foreach ($ret as $key => $suggestion) {
              if (isset($suggestion['render'])) {
                $ret[$key] = render($suggestion['render']);
              }
              else {
                $escaped_variables = ['keys', 'suggestion_prefix', 'user_input', 'suggestion_suffix'];
                foreach ($escaped_variables as $variable) {
                  if ($suggestion[$variable]) {
                    $suggestion[$variable] = Html::escape($suggestion[$variable]);
                  }
                }
                $ret[$key] = [
                  '#theme' => 'search_api_autocomplete_suggestion',
                  '#suggestion' => $suggestion,
                ];
                $ret[$key] = \Drupal::service('renderer')->render($ret[$key]);
              }
            }
          }
        }
      }
    }
    catch (SearchApiException $e) {
      watchdog_exception('search_api_autocomplete', $e, '%type while retrieving autocomplete suggestions: !message in %function (line %line of %file).');
    }

    return new CacheableJsonResponse($ret);
  }

}
