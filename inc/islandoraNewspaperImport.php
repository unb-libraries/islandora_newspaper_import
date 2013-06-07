<?php

class islandoraNewspaperImport {

	function __construct($repoURL,$repoUser,$repoPass,$importPath) {
		module_load_include('libraries/tuque', 'islandora', 'FedoraApi');
		module_load_include('libraries/tuque', 'islandora', 'FedoraApiSerializer');
		module_load_include('libraries/tuque', 'islandora', 'Object');
		module_load_include('libraries/tuque', 'islandora', 'Repository');
		module_load_include('libraries/tuque', 'islandora', 'TestHelpers');

		$this->fedoraInit($repoURL,$repoUser,$repoPass);
		$this->setupSourceData($importPath);
		$this->validateConfigData();
	}

	function fedoraInit($repoURL,$repoUser,$repoPass) {
		$connection = new RepositoryConnection($repoURL,$repoUser,$repoPass);
		$this->api = new FedoraApi($connection);
		$cache = new SimpleCache();
		$this->repository = new FedoraRepository($this->api, $cache);
		try {
			$randPID=uniqid().':'.uniqid();
			$this->api->m->ingest(array('pid' => $randPID));
			$this->api->m->purgeObject($randPID);
		} catch (Exception $e) {
			die("Cannot ingest or purge items from random pid $randPID (Check URL/credentials)\n");
		}
	}

	function buildIssue($sourceMedia, $marcOrgID, $issueContentModelPID, $pageContentModelPID, $parentCollectionPID, $nameSpace, $issueTitle, $lccnID, $issueDate, $issueVolume, $issueIssue, $issueEdition, $missingPages, $templatePath) {
		$this->issue=new islandoraNewspaperImportIssue($this->api, $sourceMedia, $marcOrgID, $issueContentModelPID, $nameSpace, $parentCollectionPID, $issueTitle, $lccnID, $issueDate, $issueVolume, $issueIssue, $issueEdition, $missingPages);
		$this->issue->createContent($this->imagesToImport, $pageContentModelPID, $templatePath);
	}

	function setupSourceData($importPath) {
		if (!$importPath) {
			$this->import_path=drush_prompt(dt('Path to Import Directory'), NULL, TRUE);
		} else {
			$this->import_path=$importPath;
		}
		$this->imagesToImport=array();
		foreach (glob(join('/',array($this->import_path,'*.[tT][iI][fF]'))) as $filePathToImport) {
			$this->imagesToImport[]=array(
								'pageno' => ltrim(array_shift(explode('.', basename($filePathToImport))), '0'),
								'filepath' => $filePathToImport
								);
		}
		$this->validateSourcePath();
	}

	function validateConfigData() {
		$this->validateIssueDate();
		$must_exist_data=array(
				'ISSUE_TITLE',
				'ISSUE_MEDIA',
				'ISSUE_LCCN',
				'ISSUE_VOLUME',
				'ISSUE_ISSUE',
				'ISSUE_EDITION',
				);
		foreach ($must_exist_data as $cur_data) if (!constant($cur_data) || constant($cur_data) == '') die ("$cur_data is not set in issue_metadata.inc.php\n");
	}

	function validateIssueDate(){
		if (!checkdate(date('m',ISSUE_DATE), date('d',ISSUE_DATE), date('Y',ISSUE_DATE))) {
			die("Date from metadata [{ISSUE_DATE}]is invalid\n");
		}
	}

	function validateSourcePath() {
		if(!@include(join('/', array($this->import_path,'issue_metadata.inc.php')))) die("Failed to load 'issue_metadata.inc.php' from {$this->import_path}\n");
		if (sizeof($this->imagesToImport) < 1 ) die("No *.tif files exist in import path {$this->import_path}\n");
	}
}
