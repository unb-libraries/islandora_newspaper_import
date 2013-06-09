# Islandora Newspaper Object Ingest
This drush command leverages the Islandora Newspaper Solution Pack to ingest a newspaper issue object into a fedora commons repository.

This development was based on a model originally written by Paul Pound at UPEI (https://github.com/roblib/scripts/blob/master/drush/drupal7/islandora_newspapers.drush.inc).

Some assumptions about the source data are made for the sake of sanity:
+   All images for an issue must reside in a single path.
+   That path must contain a file named issue_metadata.inc.php, which provides metadata for that issue based on the sample model given in issue_metadata.inc.php.example.
+   All images must be in TIF format, and have an extension of TIF.
+   The names of the images should be the page number of the issue they represent (001.TIF, 002.TIF, etc.)
+   In the case of missing pages, be sure to omit those pages from the numbering sequence (007.TIF, 008.TIF, 010.TIF). The missing pages should be noted in the MISSING_PAGES variable defined in issue_metadata.inc.php

## Setup
Install this script as a drush command.

## Use
The required arguments, in order, are:
+   import_path : The path to the directory that contains the newspaper pages in TIF format
+   parent_pid : The PID of the collection that will contain the issue.
+   base_namespace : The base namespace to use for the issue
+   fedora_url : The full url, including port number for the fedora repository
+   fedora_user : A user with full write access to the fedora repository
+   fedora_password : Password for the fedora user
+   marcorg_id : The content creator marcorg ID code

```bash
drush -u 1 --root=/srv/www --uri=http://localhost islandora_newspaper_ingest http://fedora.lib.unb.ca:8080/fedora fedoraAdmin password /mnt/images/TJ/1974/01/01 newspapers:telegraph telegraph NBFU
```
