<?php

namespace Drupal\drush_media_migration;

/**
 * Interface MediaMigrationInterface.
 */
interface MediaMigrationInterface {

  /**
   * Return one array with the ID's of the entities
   *   that will be migrated.
   *
   * @param string $entity_type
   *    The id of the entities to migrate, for example 'node'.
   * @param string $bundle_id
   *    The id of the bundle of the entities to migrate, for example 'article'.
   * @param array $file_field_map
   *    An array of arrays with the source, destination and Media bundle.
   *
   * @return array
   *   Array with the ID's of the entities to migrate.
   */
  public function getEntitiesIdsToMigrate(string $entity_type, string $bundle_id, array $file_field_map): array;

}
