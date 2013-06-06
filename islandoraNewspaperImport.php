<?php

require_once 'islandoraNewspaperImportIssue.php';
require_once 'islandoraNewspaperImportPage.php';

class islandoraNewspaperImport {

	function __construct($repoURL,$repoUser,$repoPass,$parentCollectionPID=NULL,$nameSpace=NULL,$importPath=NULL) {
		module_load_include('libraries/tuque', 'islandora', 'FedoraApi');
		module_load_include('libraries/tuque', 'islandora', 'FedoraApiSerializer');
		module_load_include('libraries/tuque', 'islandora', 'Object');
		module_load_include('libraries/tuque', 'islandora', 'Repository');
		module_load_include('libraries/tuque', 'islandora', 'TestHelpers');

		$this->fedoraInit($repoURL,$repoUser,$repoPass);

		$this->setupParentCollection($parentCollectionPID);
		$this->setupNameSpace($nameSpace);
		$this->setupSourceData($importPath);

		$this->issue=new islandoraNewspaperImportIssue($this);
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

	function ingestPage() {
		// $this->api->m->ingest(array('pid' => $this->testPid));
		// $this->api->m->addDatastream($this->testPid, $this->testDsid, 'string', '<test> test </test>', NULL);
	}

	function ingestIssue() {
		
	}

	function setupNameSpace($nameSpace) {
		if (!$nameSpace) {
			$this->namespace=drush_prompt(dt('Base Page Namespace (i.e. northshoreleader , no trailing colon)'), NULL, TRUE);
		} else {
			$this->namespace=$nameSpace;
		}
		$this->validateNameSpace();
	}

	function setupParentCollection($parentCollectionPID){
		if (!$parentCollectionPID) {
			$this->parentCollectionPID=drush_prompt(dt('Parent Collection PID (i.e. newspapers:northshoreleader)'), NULL, TRUE);
		} else {
			$this->parentCollectionPID=$parentCollectionPID;
		}
		$this->validateParentCollectionPID();
	}

	function setupSourceData($importPath) {
		if (!$importPath) {
			$this->import_path=drush_prompt(dt('Path to Import Directory'), NULL, TRUE);
		} else {
			$this->import_path=$importPath;
		}
		$this->validateSourceData();
	}

	function validateNameSpace() {
		// Test if we can write and purge from this namespace.
		$randomString = uniqid();
		$testPID = "{$this->namespace}:$randomString";
		try {
			$this->api->m->ingest(array('pid' => $testPID));
			$this->api->m->purgeObject($testPID);
		} catch (Exception $e) {
			die("Failed to ingest and purge $testPID from namespace {$this->namespace} (Check permissions)");
		}
	}

	function validateParentCollectionPID() {
		try {
			$values = $this->api->a->getObjectProfile($this->parentCollectionPID);
		} catch (Exception $e) {
			die("Could not assign parent collection {$this->parentCollectionPID} : (Does collection exist?)\n");
		}
	}

	function validateSourceData() {
		$filename = join('/', array($this->import_path,'issue.csv'));
		if (!is_readable($filename)) {
			die("$filename does not exist, or is not readable!\n");
		}
	}
}
