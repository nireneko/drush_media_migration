<?php

namespace Drupal\drush_media_migration\Batch;

use Drupal\Core\StringTranslation\TranslatableMarkup;

class MediaMigration {

  /**
   * Migrate image fields to media fields.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param array $image_field_map
   *   An array of mappings, each sub array should have the following keys:
   *   - source: The source image field name.
   *   - destination: The destination media field name.
   *   - media_bundle: The media bundle to create.
   * @param string|NULL $bundle_id
   *  The bundle id.
   *
   * @return void
   */
  public static function migrate(array $entity_ids, string $entity_type, array $file_field_map, string $bundle_id = NULL, int $count, &$context): void {

    if (!isset($context['results']['init'])) {
      $context['results']['init'] = TRUE;
      $context['results']['file_rows_processed'] = 0;
      $context['results']['file_rows_updated'] = 0;
      $context['results']['file_rows_errors'] = 0;
      $context['results']['file_error_ids'] = [];
    }

    $media_fields = [
      'image' => [
        'field' => 'field_media_image',
      ],
      'document' => [
        'field' => 'field_media_document',
      ],
    ];

    /** @var \Drupal\Core\Extension\ModuleHandlerInterface $module_handler */
    $module_handler = \Drupal::service('module_handler');

    $module_handler->alter('drush_media_migration_bundle', $media_fields);

    $entity_storage = \Drupal::entityTypeManager()->getStorage($entity_type);

    ++$context['results']['file_rows_processed'];
    foreach ($entity_storage->loadMultiple($entity_ids) as $entity) {
      foreach ($file_field_map as $field_map) {

        $file_ids = $entity->get($field_map['source'])->getValue();
        $entity->set($field_map['destination'], NULL);

        foreach ($file_ids as $file_id) {
          $file = \Drupal::entityTypeManager()
            ->getStorage('file')
            ->load($file_id['target_id']);
          if (!$file) {
            $message = 'Media cannot be created. The %entity_type_id with ID: %entity_id of bundle: %bundle refers to the image file with ID: %fid. But there is no information about the file with this ID in the database.';
            \Drupal::logger('drush_media_migration')->error($message, [
              '%fid' => $file_id['target_id'],
              '%entity_type_id' => $entity_type,
              '%bundle' => $bundle_id,
              '%entity_id' => $entity->id(),
            ]);
            continue;
          }
          $media_data = [
            'bundle' => $field_map['media_bundle'],
            'uid' => $file->getOwnerId(),
            'created' => $file->getCreatedTime(),
            // @TODO: Make this work for languages.
          ];

          if (!isset($media_fields[$field_map['media_bundle']])) {
            $message = 'Cannot migrate to Media because bundle is not defined. Use the hook drush_media_migration_bundle_alter to define the bundle for the media type.';
            \Drupal::logger('drush_media_migration')->error($message, [
              '%fid' => $file_id['target_id'],
              '%entity_type_id' => $entity_type,
              '%bundle' => $bundle_id,
              '%entity_id' => $entity->id(),
            ]);
            continue;
          }

          $media_data[$media_fields[$field_map['media_bundle']]['field']] = $file_id;

          $media = \Drupal::entityTypeManager()->getStorage('media')->create($media_data);
          $media->save();
          $entity->get($field_map['destination'])->appendItem($media->id());
        }
      }
      $entity->save();
    }

    $context['message'] = new TranslatableMarkup('Processed @processed file rows out of @count. @updated uris updated. @errors errors found', [
      '@processed' => $context['results']['file_rows_processed'],
      '@count' => $count,
      '@updated' => $context['results']['file_rows_updated'],
      '@errors' => $context['results']['file_rows_errors'],
    ]);
  }

  public static function migrateFinishedCallback($success, $results, $operations) {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    if ($success) {
      if ($results['file_rows_errors']) {
        $errorFids = implode(',', $results['file_error_ids']);
        $message = new TranslatableMarkup('Migrated @processed files to media succesfully. @errors found in files @error_fids', [
          '@processed' => $results['file_rows_updated'],
          '@errors' => $results['file_rows_errors'],
          '@error_fids' => $errorFids,
        ]);
      }
      $message = new TranslatableMarkup('Processed @processed files succesfully.', [
        '@processed' => $results['file_rows_updated'],
      ]);
    }
    else {
      $message = t('Finished with an error.');
    }
    \Drupal::messenger()->addMessage($message);
  }

}
