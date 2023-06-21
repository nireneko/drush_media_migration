# Drush media migration
This module provides one drush command to migrate from the file and image fields
to new fields provided by the media module.

## Installation
1. Install the module as usual with composer.
2. Enable the module with drush or the UI.
3. Run the drush command dmm:migrate.

## Usage
Run the command `drush dmm:migrate` to start the migration.
Set the options with the entity type and the bundle name, also the source field,
destination field and the media type.

Example:

`$ drush dmm:migrate --entity-type=node --bundle=article --source=field_image
  --destination=field_media_image --media-bundle=image`

In that example the command will migrate all the images from the nodes of
article type field_image to the field_media_image and will create a new media
entity with the bundle image.

All the options are mandatory.
