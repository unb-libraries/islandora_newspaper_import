<?php

require_once('inc/islandoraNewspaperImport.php');
require_once('inc/islandoraNewspaperImportIssue.php');
require_once('inc/islandoraNewspaperImportPage.php');
require_once('lib/smarty/distribution/libs/Smarty.class.php');

function islandora_newspaper_ingest_drush_command() {
	$items = array();
	$items['islandora_ingest_newspapers'] = array(
		'description' => "Creates the parent issue object and uploads the pages as child objects",
		'arguments' => array(
			'import_path' => 'The path to the directory that contains the newspaper pages in TIF format',
			'parent_pid' => 'if TRUE then you will be asked to confirm the update for each object',
			'base_namespace' => 'The reel number',
			'fedora_url' => 'The full url, including port number to the fedora repository',
			'fedora_user' => 'A user with full write access to the fedora repository',
			'fedora_password' => 'Password for the fedora user',
			'marcorg_id' => 'The content creator marcorg ID code',
		),
		'examples' => array(
			'drush -u 1 --root=/srv/www/VRE7 --uri=http://localhost islandora_newspaper_ingest http://fedora.lib.unb.ca:8080/fedora fedoraAdmin password /mnt/images/TJ/1974/01/01 newspapers:telegraph telegraph',
		),
		'aliases' => array('islandoranews'),
		'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_LOGIN,
	);
	return $items;
}

function drush_islandora_newspaper_ingest_islandora_ingest_newspapers($import_path, $parent_pid, $base_namespace, $fedora_url, $fedora_user, $fedora_password, $marcorg_id) {

	$core = DRUSH_DRUPAL_CORE;
	require_once $core . '/includes/bootstrap.inc';
	require_once $core . '/includes/common.inc';
	include_once $core . '/includes/unicode.inc';

	drupal_bootstrap(DRUPAL_BOOTSTRAP_SESSION);
	require_once $core . '/includes/install.inc';
	require_once $core . '/modules/system/system.install';

	include_once $core . '/includes/module.inc';
	drupal_load('module', 'islandora');
	drupal_load('module', 'islandora_newspaper');

	$import = new islandoraNewspaperImport(
											$fedora_url,
											$fedora_user,
											$fedora_password,
											$import_path
											);
	$import->buildIssue(
					SOURCE_MEDIA,
					$marcorg_id,
					ISSUE_CONTENT_MODEL_PID,
					PAGE_CONTENT_MODEL_PID,
					$parent_pid,
					$base_namespace,
					ISSUE_TITLE,
					ISSUE_LCCN,
					ISSUE_DATE,
					ISSUE_VOLUME,
					ISSUE_ISSUE,
					ISSUE_EDITION,
					MISSING_PAGES
	);
	print_r($import);

	// $import->ingest();
}
