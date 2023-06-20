<?php

namespace Drupal\drush_media_migration;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class MediaMigration.
 */
class MediaMigration implements MediaMigrationInterface {

  /**
   * Constructs a MediaMigration object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The service entity_type.manager.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getEntitiesIdsToMigrate(string $entity_type, string $bundle_id, array $file_field_map): array {

    $entity_storage = $this->entityTypeManager->getStorage($entity_type);

    // Construct a query to find all entities with the image fields.
    $entity_query = $entity_storage->getQuery();
    $entity_query->condition('type', $bundle_id);

    // We don't want to check access.
    $entity_query->accessCheck(FALSE);

    // Add a condition for each old file field.
    $field_conditions_group = $entity_query->orConditionGroup();
    foreach ($file_field_map as $field_map) {
      $field_conditions_group->exists($field_map['source']);
    }
    $entity_query->condition($field_conditions_group);

    return $entity_query->execute();
  }
}
