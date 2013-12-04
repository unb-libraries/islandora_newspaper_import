<?php
require_once('inc/islandoraNewspaperImport.php');
require_once('inc/islandoraNewspaperImportIssue.php');
require_once('inc/islandoraNewspaperImportPage.php');
require_once('lib/smarty/distribution/libs/Smarty.class.php');

function bootstrapDrupal() {
	$drupal_core_path = DRUSH_DRUPAL_CORE;
	require_once $drupal_core_path . '/includes/bootstrap.inc';
	require_once $drupal_core_path . '/includes/common.inc';
	include_once $drupal_core_path . '/includes/unicode.inc';

	drupal_bootstrap(DRUPAL_BOOTSTRAP_SESSION);
	require_once $drupal_core_path . '/includes/install.inc';
	require_once $drupal_core_path . '/modules/system/system.install';

	include_once $drupal_core_path . '/includes/module.inc';
	drupal_load('module', 'islandora');
	drupal_load('module', 'islandora_newspaper');
	include_once('/srv/www/VRE7/sites/newspapers.lib.unb.ca/modules/islandora/includes/tuque.inc');
}

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
			'drush -u 1 --root=/srv/www/VRE7 --uri=http://localhost islandora_newspaper_ingest /mnt/images/TJ/1974/01/01 newspapers:telegraph telegraph http://fedora.lib.unb.ca:8080/fedora fedoraAdmin password UNBFU tv-guide',
		),
		'aliases' => array('ingestissue'),
		'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_LOGIN,
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
			'drush -u 1 --root=/srv/www/VRE7 --uri=http://localhost islandora_ingest_newspaper_title /mnt/images/TJ/ islandora:newspapers newspapers:telegraphjournal http://fedora.lib.unb.ca:8080/fedora fedoraAdmin password',
		),
		'aliases' => array('ingesttitle'),
		'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_LOGIN,
	);
	$items['islandora_audit_newspaper_pages'] = array(
		'description' => "Audit newspaper page objects for a specified datastream. Returns a list of failed PIDs.",
		'arguments' => array(
			'datastream_labels' => 'Comma separted list of datastream label(s) to check during audit',
			'fedora_url' => 'The full url, including port number to the fedora repository',
			'fedora_user' => 'A user with full write access to the fedora repository',
			'fedora_password' => 'Password for the fedora user',
		),
	);
	return $items;
}

function drush_islandora_newspaper_ingest_islandora_ingest_newspaper_title($import_path, $parent_pid, $base_namespace, $title_string, $fedora_url, $fedora_user, $fedora_password) {
	bootstrapDrupal();
	$import = new islandoraNewspaperImport($fedora_url, $fedora_user, $fedora_password, $import_path);
	$import->buildTitle($title_string, $parent_pid, $base_namespace);
	$import->ingestTitle();
}

function drush_islandora_newspaper_ingest_islandora_ingest_newspaper_issue($pids_to_modify, $datastream_labels, $fedora_url, $fedora_user, $fedora_password) {
	bootstrapDrupal();
	$import = new islandoraNewspaperImport($fedora_url, $fedora_user, $fedora_password, $import_path);
	$import->buildIssue($marcorg_id, $parent_pid, $base_namespace, $special_identifier);
	$import->ingestIssue();
}

function drush_islandora_newspaper_ingest_islandora_audit_newspaper_pages($datastream_labels, $fedora_url, $fedora_user, $fedora_password) {
	bootstrapDrupal();
	$repo_connection = new RepositoryConnection($fedora_url,$fedora_user,$fedora_password);
	$repo_query = new RepositoryQuery($repo_connection);

	foreach (explode(',',$datastream_labels) as $cur_datastream_label) {
		$find_missing_ds_itql=<<<EOT
select \$object from <#ri>
where
(\$object <fedora-model:hasModel> <info:fedora/islandora:newspaperPageCModel>
and \$object <fedora-model:state> <info:fedora/fedora-system:def/model#Active>
)
minus (\$object <fedora-view:disseminates> \$ds1 in <#ri>
and \$ds1 <fedora-view:disseminationType> <info:fedora/*/$cur_datastream_label> in <#ri>)
EOT;
		// TODO : Printing here is weak, but allows us to pipe input into another tool. Something else instead
		foreach( $repo_query->query($find_missing_ds_itql, 'itql') as $cur_result) {
			print $cur_result['object']['value']."\n";
		}
	}
}
