<?php

require_once 'importConfig.inc.php';
require_once(join('/',array(SCRIPT_ROOT,'inc','islandoraNewspaperImport.php')));
require_once(join('/',array(SCRIPT_ROOT,'inc','islandoraNewspaperImportIssue.php')));
require_once(join('/',array(SCRIPT_ROOT,'inc','islandoraNewspaperImportPage.php')));
require_once(join('/',array(SCRIPT_ROOT,'lib','smarty/distribution/libs/Smarty.class.php')));

$import = new islandoraNewspaperImport(
										FEDORA_URL,
										FEDORA_USER,
										FEDORA_PASSWORD,
										$_SERVER['argv'][6],
										$_SERVER['argv'][7],
										$_SERVER['argv'][8]
										);
$import->ingestIssue();
