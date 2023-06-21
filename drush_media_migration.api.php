<?php

/**
 * @file drush_media_migration.api.php
 */

/**
 * Define the media bundle and fields for the migration.
 *
 * @param $media_fields
 *   Array with the media bundle and fields.
 *
 * @return void
 */
function hook_drush_media_migration_bundle_alters(&$media_fields) {
  $media_fields['image']['field'] = 'field_media_image';
  $media_fields['document']['field'] = 'field_media_document';
}
