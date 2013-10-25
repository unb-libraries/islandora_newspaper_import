<?php

class islandoraNewspaperImport {

	function __construct($repoURL,$repoUser,$repoPass,$importPath) {
		$this->fedoraInit($repoURL,$repoUser,$repoPass);
		$this->setupSourceData($importPath);
		$this->validateConfigData();
	}

	function fedoraInit($repoURL,$repoUser,$repoPass) {
		$this->connection = new RepositoryConnection($repoURL,$repoUser,$repoPass);
		$this->api = new FedoraApi($this->connection);
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

	function buildIssue($sourceMedia, $marcOrgID, $issueContentModelPID, $pageContentModelPID, $parentCollectionPID, $nameSpace, $issueTitle, $lccnID, $issueDate, $issueVolume, $issueIssue, $issueEdition, $missingPages, $issueLanguage, $special_identifier, $issue_supplement_title, $issue_errata) {
		$this->issue=new islandoraNewspaperImportIssue($this->api, $sourceMedia, $marcOrgID, $issueContentModelPID, $nameSpace, $parentCollectionPID, $issueTitle, $lccnID, $issueDate, $issueVolume, $issueIssue, $issueEdition, $missingPages, $issueLanguage, $special_identifier, $issue_supplement_title, $issue_errata);
		$this->issue->createContent($this->imagesToImport, $pageContentModelPID, $templatePath, $xslPath);
	}

	function ingest() {
		$this->issue->ingest($this->repository);
	}

	function setupSourceData($importPath) {
		if (!$importPath) {
			$this->import_path=drush_prompt(dt('Path to Import Directory'), NULL, TRUE);
		} else {
			$this->import_path=$importPath;
		}
		$this->imagesToImport=array();
		$file_list=glob(join('/',array($this->import_path,'*.[jJ][pP][gG]')));
		foreach ($file_list as $filePathToImport) {
			$this->imagesToImport[]=array(
								'pageno' => str_replace('.jpg','', array_pop(explode('_', basename($filePathToImport)))),
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
				'ISSUE_VOLUME',
				'ISSUE_ISSUE',
				# 'ISSUE_EDITION',
				);
		foreach ($must_exist_data as $cur_data) if (!constant($cur_data) || constant($cur_data) == '') die ("$cur_data is not set in metadata.php\n");
	}

	function validateIssueDate(){
		if (!checkdate(date('m',ISSUE_DATE), date('d',ISSUE_DATE), date('Y',ISSUE_DATE))) {
			die("Date from metadata [{ISSUE_DATE}]is invalid\n");
		}
	}

	function validateSourcePath() {
		if(!@include(join('/', array($this->import_path,'metadata.php')))) die("Failed to load 'metadata.php' from {$this->import_path}\n");
		if (sizeof($this->imagesToImport) < 1 ) die("No *.jpg files exist in import path {$this->import_path}\n");
	}
}
