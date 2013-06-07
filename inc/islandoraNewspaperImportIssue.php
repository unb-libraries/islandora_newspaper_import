<?php

class islandoraNewspaperImportIssue {

	function __construct($api, $sourceMedia, $marcOrgID, $issueContentModelPID, $nameSpace, $parentCollectionPID, $issueTitle, $lccnID, $issueDate, $issueVolume, $issueIssue, $issueEdition, $missingPages) {
		$this->marcOrgID=$marcOrgID;
		$this->sourceMedia=$sourceMedia;
		$this->setupNameSpace($api, $nameSpace);
		$this->setupParentCollection($api, $parentCollectionPID);
		$this->cModel=$issueContentModelPID;
		$this->lccnID=$lccnID;
		$this->issueDate=$issueDate;
		$this->issueVolume=$issueVolume;
		$this->issueIssue=$issueIssue;
		$this->issueEdition=$issueEdition;
		$this->missingPages=$missingPages;
		$this->title=$issueTitle;
		$this->assignPID();
		$this->pages=array();
	}

	function addPage($pageNumber, $imagePath) {
		$this->pages[]=new islandoraNewspaperImportPage($pageNumber, $imagePath);
	}

	function addPages($filePathArray) {
		foreach ($filePathArray as $curImageFilePath) {
			print $curImageFilePath;
		}
	}

	function assignMODS($titleArray, $templatePath){
		$issueMODS= new Smarty;
		$issueMODS->assign('lccn_id', $this->lccnID);
		$issueMODS->assign('issue_volume', $this->issueVolume);
		$issueMODS->assign('issue_issue', $this->issueIssue);
		$issueMODS->assign('issue_edition', $this->issueEdition);
		$issueMODS->assign('non_sort_title', $titleArray[1]);
		$issueMODS->assign('sort_title', $titleArray[2]);
		$issueMODS->assign('iso_date', $this->getISODate());

		if ( $this->missingPages !='' ) $issueMODS->assign('missing_pages', $this->missingPages);
		$this->xml['MODS']=$issueMODS->fetch( join('/',array($templatePath,'issue_mods.tpl.php')) );
	}

	function assignRDF($templatePath){
		$issueRDF= new Smarty;
		$issueRDF->assign('issue_pid', $this->pid);
		$issueRDF->assign('issue_content_model_pid', $this->cModel);
		$issueRDF->assign('parent_collection_pid', $this->parentCollectionPID);
		$this->xml['RDF']=$issueRDF->fetch( join('/',array($templatePath,'issue_rdf.tpl.php')) );
	}

	function assignPID() {
		$this->pid=join(
					":",
					array(
						$this->namespace,
						$this->getISODate(),
					)
				);
	}

	function createContent($imagesToImport, $pageContentModelPID, $templatePath) {
		// TODO : Move this out!
		$non_sort_words=array(
				'the',
				'a',
				'an',
				);

		preg_match( '/^('.implode('|',$non_sort_words).')? *(.*)$/i' , $this->title, $titleArray);

		$this->assignMODS($titleArray, $templatePath);
		$this->assignRDF($templatePath);

		foreach ($imagesToImport as $curImageToImport) {
			$pageObject=new islandoraNewspaperImportPage($this->pid, $pageContentModelPID, $this->sourceMedia, $curImageToImport['pageno'], $curImageToImport['filepath'], $this->marcOrgID);
			$pageObject->createContent($this->marcOrgID, $templatePath);
			$this->pages[]=$pageObject;
		}
	}

	function ingest() {
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
