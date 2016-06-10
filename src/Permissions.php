<?php

namespace Drupal\search_api_autocomplete;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Permissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity storage interface.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * Creates a new Permissions instance.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The storage.
   */
  public function __construct(EntityStorageInterface $storage) {
    $this->storage = $storage;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('search_api_autocomplete_settings')
    );
  }


  public function bySearch() {
    $perms = [];
    /** @var \Drupal\search_api_autocomplete\Entity\SearchApiAutocompleteSearch $search */
    foreach ($this->storage->loadMultiple() as $id => $search) {
      $perms['use search_api_autocomplete for ' . $id] = [
        'title' => $this->t('Use autocomplete for the %search search', ['%search' => $search->id()]),
      ];
    }
    return $perms;
  }

}
