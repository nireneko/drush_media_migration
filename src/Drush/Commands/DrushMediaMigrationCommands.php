<?php

namespace Drupal\drush_media_migration\Drush\Commands;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\drush_media_migration\MediaMigrationInterface;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\UserAbortException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Drush commandfile.
 */
class DrushMediaMigrationCommands extends DrushCommands {

  /**
   * Constructs a DrushMediaMigrationCommands object.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected MediaMigrationInterface $mediaMigration
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('drush_media_migration.migrate')
    );
  }

  /**
   * Command description here.
   *
   * @param array $options
   *   An associative array of options whose values come from cli, aliases, config, etc.
   * @option entity-type
   *   The entity of the source migration.
   * @option bundle
   *   The bundle of the entity.
   * @option source
   *   The source field of the file or image fields.
   * @option destination
   *   The destination field of the media entity.
   * @option media-bundle
   *   The bundle of the target media entity.
   * @usage dmm:migrate --entity-type=node --bundle=article --source=field_image --destination=field_media_image --media-bundle=image
   *   Execute the command with all the options.
   *
   * @command drush_media_migration:migrate
   * @aliases dmmm
   */
  public function mediaMigration(array $options = [
    'entity-type' => NULL,
    'bundle' => NULL,
    'source' => NULL,
    'destination' => NULL,
    'media-bundle' => NULL,
  ]) {

    if (is_null($options['entity-type'])) {
      $this->logger()->info(dt("the entity type must be set, '--entity-type=node' for example."));
      return null;
    }

    $file_field_map[] = [
      'source' => $options['source'],
      'destination' => $options['destination'],
      'media_bundle' => $options['media-bundle'],
    ];

    $entity_ids = $this->mediaMigration->getEntitiesIdsToMigrate($options['entity-type'], $options['bundle'], $options['source']);

    $count = count($entity_ids);

    if ($count == 0) {
      $this->logger()->info(dt("No entity found to migrate."));
      $this->output()->writeln('No entity found to migrate, aborting.');
      return null;
    }

    $this->output()->writeln($count . ' ' . $options['bundle'] . ' of the entity ' . $options['entity-type'] . ' will be updated.');

    if (!$this->io()->confirm('Do you want to continue?')) {
      throw new UserAbortException();
    }

    $batch = new BatchBuilder();

    $batch->setTitle('Migrating from files/image to Media entity...')
      ->setInitMessage('Starting')
      ->setProgressMessage('Processed @current out of @total.')
      ->setErrorMessage('An error occurred during processing');

    $results = array_chunk($entity_ids, 10);

    foreach ($results as $rows) {
      $batch->addOperation('\Drupal\drush_media_migration\Batch\MediaMigration::migrate',
        [
          $rows, $options['entity-type'], $file_field_map, $options['bundle'], $count
        ]);
    }

    $batch->setFinishCallback('\Drupal\drush_media_migration\Batch\MediaMigration::migrateFinishedCallback');

    batch_set($batch->toArray());

    $batch =& batch_get();

    // Drush integration.
    if (PHP_SAPI === 'cli') {
      $batch['progressive'] = FALSE;
      drush_backend_batch_process();
    }

  }

}
