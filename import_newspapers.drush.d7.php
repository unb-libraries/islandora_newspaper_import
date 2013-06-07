<?php

require_once 'inc/islandoraNewspaperImport.php';
require_once 'importConfig.inc.php';

$import = new islandoraNewspaperImport(
										FEDORA_URL,
										FEDORA_USER,
										FEDORA_PASSWORD,
										$_SERVER['argv'][6],
										$_SERVER['argv'][7],
										$_SERVER['argv'][8]
										);
$import->ingestIssue();
