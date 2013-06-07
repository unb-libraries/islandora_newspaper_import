<?php

class islandoraNewspaperImportIssue {

	function __construct() {
		$this->pages=array();
		$this->issueDate=date("Ymd",ISSUE_DATE);
	}

	function addPage($pageNumber, $imagePath) {
		$this->pages[]=new islandoraNewspaperImportPage($pageNumber, $imagePath);
	}

	function addPages($filePathArray) {
		// $fileArray glob(join('/',array($this->import_path,'*.[tT][iI][fF]')))
		foreach ($filePathArray as $curImageFilePath) {
			print $curImageFilePath;
		}
	}

	function assignMODS($titleArray, $lccnID, $issueVolume, $issueIssue, $issueEdition, $missingPages, $templatePath){
		$issueMODS= new Smarty;
		$issueMODS->assign('lccn_id', $lccnID);
		$issueMODS->assign('issue_volume', $issueVolume);
		$issueMODS->assign('issue_issue', $issueIssue);
		$issueMODS->assign('issue_edition', $issueIssue);
		$issueMODS->assign('non_sort_title', $titleArray[1]);
		$issueMODS->assign('sort_title', $titleArray[2]);
		$issueMODS->assign('iso_date', $this->getISODate());

		if ( $missingPages !='' ) $issueMODS->assign('missing_pages', $missingPages);
		$this->xml['MODS']=$issueMODS->fetch( join('/',array($templatePath,'issue_mods.tpl.php')) );
	}

	function assignRDF($contentModelPID, $parentCollectionPID, $templatePath){
		$issueRDF= new Smarty;
		$issueRDF->assign('issue_pid', join(
											":",
											array(
												$this->namespace,
												$this->getISODate(),
											)
										)
							);
		$issueRDF->assign('content_model_pid', $contentModelPID);
		$issueRDF->assign('collection_pid', $parentCollectionPID);
		$this->xml['RDF']=$issueRDF->fetch( join('/',array($templatePath,'issue_rdf.tpl.php')) );
	}

	function createContent($imagesToImport, $issueContentModelPID, $pageContentModelPID, $parentCollectionPID, $lccnID, $issueVolume, $issueIssue, $issueEdition, $missingPages, $templatePath) {
		$non_sort_words=array(
				'the',
				'a',
				'an',
				);
		preg_match( '/^('.implode('|',$non_sort_words).')? *(.*)$/i' , ISSUE_TITLE, $titleArray);

		// Assign Issue Metadata
		$this->assignMODS($titleArray, $lccnID, $issueVolume, $issueIssue, $issueEdition, $missingPages, $templatePath);
		$this->assignRDF($issueContentModelPID, $parentCollectionPID, $templatePath);

		// Build Pages
		foreach ($imagesToImport as $curImageToImport) {
			$this->pages[]=new islandoraNewspaperImportPage($curImageToImport['pageno'],$curImageToImport['filepath']);
		}
	}

	function ingest() {
		$issueRDF= new Smarty;
		$label = $title . ' -- ' . $date;
		$dsid = 'MODS';
		$content_model_pid = 'islandora:issueCModel';
		$collection_pid = 'newspapers:guardian';
	}

	// Deprecated?
	function ingestPage($page) {
		// $this->api->m->ingest(array('pid' => $this->testPid));
		// $this->api->m->addDatastream($this->testPid, $this->testDsid, 'string', '<test> test </test>', NULL);
		print "Ingesting ".print_r($page,TRUE);
		// TODO: TRIGGER HOOKS THAT GENERATE SURROGATES
	}

	function setupParentCollection($api, $parentCollectionPID){
		if (!$parentCollectionPID) {
			$this->parentCollectionPID=drush_prompt(dt('Parent Collection PID (i.e. newspapers:northshoreleader)'), NULL, TRUE);
		} else {
			$this->parentCollectionPID=$parentCollectionPID;
		}
		$this->validateParentCollectionPID($api);
	}

	function setupNameSpace($api, $nameSpace) {
		if (!$nameSpace) {
			$this->namespace=drush_prompt(dt('Base Page Namespace (i.e. northshoreleader , no trailing colon)'), NULL, TRUE);
		} else {
			$this->namespace=$nameSpace;
		}
		$this->validateNameSpace($api);
	}

	function getISODate() {
		return date("Ymd",$this->issueDate);
	}

	function validateNameSpace($api) {
		$testPID = "{$this->namespace}:".uniqid();
		try {
			$api->m->ingest(array('pid' => $testPID));
			$api->m->purgeObject($testPID);
		} catch (Exception $e) {
			die("Failed to ingest and purge $testPID from namespace {$this->namespace} (Check permissions)");
		}
	}

	function validateParentCollectionPID($api) {
		try {
			$values = $api->a->getObjectProfile($this->parentCollectionPID);
		} catch (Exception $e) {
			die("Could not assign parent collection {$this->parentCollectionPID} : (Does collection exist?)\n");
		}
	}

}
