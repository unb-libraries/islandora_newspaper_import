<?php

require_once 'importConfig.inc.php';
require_once(join('/',array(SCRIPT_ROOT,'inc','islandoraNewspaperImport.php')));
require_once(join('/',array(SCRIPT_ROOT,'inc','islandoraNewspaperImportIssue.php')));
require_once(join('/',array(SCRIPT_ROOT,'inc','islandoraNewspaperImportPage.php')));
require_once(join('/',array(SCRIPT_ROOT,'lib','smarty/distribution/libs/Smarty.class.php')));

define('IMPORT_PATH', $_SERVER['argv'][6]);
define('PARENT_PID', $_SERVER['argv'][7]);
define('BASE_NAMESPACE', $_SERVER['argv'][8]);

$import = new islandoraNewspaperImport(
										FEDORA_URL,
										FEDORA_USER,
										FEDORA_PASSWORD,
										IMPORT_PATH
										);
$import->buildIssue(
				SOURCE_MEDIA,
				MARC_ORG_ID,
				ISSUE_CONTENT_MODEL_PID,
				PAGE_CONTENT_MODEL_PID,
				PARENT_PID,
				BASE_NAMESPACE,
				ISSUE_TITLE,
				ISSUE_LCCN,
				ISSUE_DATE,
				ISSUE_VOLUME,
				ISSUE_ISSUE,
				ISSUE_EDITION,
				MISSING_PAGES,
				TEMPLATE_PATH
);
