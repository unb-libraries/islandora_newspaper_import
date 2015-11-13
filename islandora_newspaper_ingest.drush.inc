<?php

/**
 * @file
 * Drush commands : Manage islandora_solution_pack newspapers in Fedora.
 */

require_once 'inc/IslandoraNewspaperImport.php';
require_once 'inc/IslandoraNewspaperImportIssue.php';
require_once 'inc/IslandoraNewspaperImportPage.php';
require_once 'vendor/autoload.php';

/**
 * Implements hook_drush_command().
 */
function islandora_newspaper_ingest_drush_command() {
  $items = array();

  $items['islandora_ingest_newspaper_issue'] = array(
    'description' => "Creates a newspaper issue object and its page children.",
    'arguments' => array(
      'import_path' => 'The path to the directory that contains the newspaper pages in TIF format',
      'parent_pid' => 'The PID of the collection that will contain the issue.',
      'base_namespace' => 'The base namespace to use for the issue',
      'fedora_url' => 'The full url, including port number to the fedora repository',
      'fedora_user' => 'A user with full write access to the fedora repository',
      'fedora_password' => 'Password for the fedora user',
      'marcorg_id' => 'The content creator marcorg ID code',
      'special_identifier' => 'A special identifier used for identifying supplements, etc.',
    ),
    'examples' => array(
      'drush -u 1 --root=/srv/www/htdocs --uri=http://localhost islandora_newspaper_ingest /mnt/images/TJ/1974/01/01 newspapers:telegraph telegraph http://fedora.lib.unb.ca:8080/fedora fedoraAdmin password UNBFU tv-guide',
    ),
    'aliases' => array('ingestissue'),
    'bootstrap' => DRUSH_BOOTSTRAP_MAX,
  );
  $items['islandora_ingest_newspaper_title'] = array(
    'description' => "Creates a newspaper title object.",
    'arguments' => array(
      'import_path' => 'The path to the directory that contains the newspaper metadata (MODS.xml)',
      'parent_pid' => 'The PID of the collection that will contain the title.',
      'base_namespace' => 'The base namespace to use for the issue',
      'title_string' => 'A text title to be used for the title\s title',
      'fedora_url' => 'The full url, including port number to the fedora repository',
      'fedora_user' => 'A user with full write access to the fedora repository',
      'fedora_password' => 'Password for the fedora user',
    ),
    'examples' => array(
      'drush -u 1 --root=/srv/www/htdocs --uri=http://localhost islandora_ingest_newspaper_title /mnt/images/TJ/ islandora:newspapers newspapers:telegraphjournal http://fedora.lib.unb.ca:8080/fedora fedoraAdmin password',
    ),
    'aliases' => array('ingesttitle'),
    'bootstrap' => DRUSH_BOOTSTRAP_MAX,
  );
  $items['islandora_audit_newspaper_pages'] = array(
    'description' => "Audit newspaper page objects for a specified datastream. Returns a list of failed PIDs.",
    'arguments' => array(
      'datastream_labels' => 'Comma separted list of datastream label(s) to check during audit',
      'fedora_url' => 'The full url, including port number to the fedora repository',
      'fedora_user' => 'A user with full write access to the fedora repository',
      'fedora_password' => 'Password for the fedora user',
    ),
    'bootstrap' => DRUSH_BOOTSTRAP_MAX,
  );
  $items['islandora_purge_newspaper_page_datastreams'] = array(
    'description' => "Purge defined datastreams from a list or ALL newspaper page objects.",
    'arguments' => array(
      'pid_values' => 'Comma separted list of PIDs to purge from. \'ALL\' purges from all active newspaper pages',
      'datastream_labels' => 'Comma separted list of datastream label(s) to purge',
      'fedora_url' => 'The full url, including port number to the fedora repository',
      'fedora_user' => 'A user with full write access to the fedora repository',
      'fedora_password' => 'Password for the fedora user',
    ),
    'bootstrap' => DRUSH_BOOTSTRAP_MAX,
  );
  return $items;
}

/**
 * Ingest a newspaper title into Fedora.
 *
 * Typically used to ingest the title newsobject for an upcoming issue ingest.
 *
 * Callback for islandora_newspaper_ingest_drush_command()
 *
 * @param string $import_path
 *   The path that contains the newspaper metadata file named MODS.xml.
 * @param string $parent_pid
 *   The PID of the collection that will contain the title.
 * @param string $base_namespace
 *   The PID to use for the title.
 * @param string $fedora_url
 *   The full URI (including port number) for the Fedora repository.
 * @param string $fedora_user
 *   Username for authentication to the Fedora repository.
 * @param string $fedora_password
 *   Password for authentication to the Fedora repository.
 *
 * @ingroup callbacks
 */
function drush_islandora_newspaper_ingest_islandora_ingest_newspaper_title($import_path, $parent_pid, $base_namespace, $title_string, $fedora_url, $fedora_user, $fedora_password) {
  $import = new IslandoraNewspaperImport($fedora_url, $fedora_user, $fedora_password, $import_path);
  $import->buildTitle($title_string, $parent_pid, $base_namespace);
  $import->ingestTitle();
}

/**
 * Ingest a newspaper issue into Fedora.
 *
 * Callback for islandora_newspaper_ingest_drush_command()
 *
 * @param string $import_path
 *   The path that contains the newspaper metadata file named MODS.xml.
 * @param string $parent_pid
 *   The parent PID of the collection that will contain the title.
 * @param string $base_namespace
 *   The base string to use in the namespace for the title.
 * @param string $fedora_url
 *   The full URI (including port number) for the Fedora repository.
 * @param string $fedora_user
 *   Username for authentication to the Fedora repository.
 * @param string $fedora_password
 *   Password for authentication to the Fedora repository.
 * @param string $marcorg_id
 *   The content creator marcorg ID code to use in issue MODS.
 * @param string $special_identifier
 *   A string used to construct unique PIDs for supplements.
 *
 * @ingroup callbacks
 */
function drush_islandora_newspaper_ingest_islandora_ingest_newspaper_issue($import_path, $parent_pid, $base_namespace, $fedora_url, $fedora_user, $fedora_password, $marcorg_id, $special_identifier) {
  $import = new IslandoraNewspaperImport($fedora_url, $fedora_user, $fedora_password, $import_path);
  $import->buildIssue($marcorg_id, $parent_pid, $base_namespace, $special_identifier);
  $import->ingestIssue();
}

/**
 * Audit the existence of Fedora object datastreams.
 *
 * Callback for islandora_newspaper_ingest_drush_command()
 *
 * @param string $datastream_labels
 *   Comma separted list of datastream label(s) to audit.
 * @param string $fedora_url
 *   The full URI (including port number) for the Fedora repository.
 * @param string $fedora_user
 *   Username for authentication to the Fedora repository.
 * @param string $fedora_password
 *   Password for authentication to the Fedora repository.
 *
 * @ingroup callbacks
 */
function drush_islandora_newspaper_ingest_islandora_audit_newspaper_pages($datastream_labels, $fedora_url, $fedora_user, $fedora_password) {
  $repo_connection = new RepositoryConnection($fedora_url, $fedora_user, $fedora_password);
  $repo_query = new RepositoryQuery($repo_connection);

  foreach (explode(',', $datastream_labels) as $cur_datastream_label) {
    $find_missing_ds_itql = <<<EOT
select \$object from <#ri>
where
(\$object <fedora-model:hasModel> <info:fedora/islandora:newspaperPageCModel>
and \$object <fedora-model:state> <info:fedora/fedora-system:def/model#Active>
)
minus (\$object <fedora-view:disseminates> \$ds1 in <#ri>
and \$ds1 <fedora-view:disseminationType> <info:fedora/*/$cur_datastream_label> in <#ri>)
EOT;
    // TODO : Printing here is weak, but allows us to pipe input into another
    // tool. Should we do something else instead?
    foreach ($repo_query->query($find_missing_ds_itql, 'itql') as $cur_result) {
      print "{$cur_result['object']['value']},$cur_datastream_label\n";
    }
  }
}

/**
 * Purge datastreams from Fedora objects.
 *
 * Callback for islandora_newspaper_ingest_drush_command()
 *
 * @param string $pid_values
 *   Comma separted list of PIDs from which to purge datastreams. If 'ALL' is
 *   specified, then all objects in the repository will be purged of datastreams
 *   with labels matching those specified in $datastream_labels.
 * @param string $datastream_labels
 *   Comma separted list of datastream label(s) to purge.
 * @param string $fedora_url
 *   The full URI (including port number) for the Fedora repository.
 * @param string $fedora_user
 *   Username for authentication to the Fedora repository.
 * @param string $fedora_password
 *   Password for authentication to the Fedora repository.
 *
 * @ingroup callbacks
 */
function drush_islandora_newspaper_ingest_islandora_purge_newspaper_page_datastreams($pid_values, $datastream_labels, $fedora_url, $fedora_user, $fedora_password) {
  $repo_connection = new RepositoryConnection($fedora_url, $fedora_user, $fedora_password);
  $api_connection = new FedoraApi($repo_connection);
  $pids_to_purge = array();

  if ($pid_values == 'ALL') {
    $repo_query = new RepositoryQuery($repo_connection);
    $find_missing_ds_itql = <<<EOT
select \$object from <#ri>
where
(\$object <fedora-model:hasModel> <info:fedora/islandora:newspaperPageCModel>
and \$object <fedora-model:state> <info:fedora/fedora-system:def/model#Active>
)
EOT;
    foreach ($repo_query->query($find_missing_ds_itql, 'itql') as $cur_result) {
      $pids_to_purge[] = $cur_result['object']['value'];
    }
  }
  else {
    foreach (explode(',', $pid_values) as $cur_pid) {
      $pids_to_purge[] = $cur_pid;
    }
  }

  foreach ($pids_to_purge as $cur_purge_pid) {
    foreach (explode(',', $datastream_labels) as $cur_datastream_label) {
      $api_connection->m->purgeDatastream($cur_purge_pid, $cur_datastream_label);
    }
  }
}
