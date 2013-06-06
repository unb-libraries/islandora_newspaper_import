<?php

require_once 'islandoraNewspaperImport.php';
require_once 'importConfig.php';

$import = new islandoraNewspaperImport(
										FEDORA_URL,
										FEDORA_USER,
										FEDORA_PASSWORD,
										$_SERVER['argv'][6],
										$_SERVER['argv'][7],
										$_SERVER['argv'][8]
										);
$import->ingestIssue();
