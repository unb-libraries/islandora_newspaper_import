<?php

class islandoraNewspaperImportIssue {

	function __construct($api, $marcOrgID, $issueContentModelPID, $nameSpace, $parentCollectionPID, $issueTitle, $lccnID, $issueDate, $issueVolume, $issueIssue, $issueEdition, $missingPages) {
		$this->marcOrgID=$marcOrgID;
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

	function assignRDF($contentModelPID, $parentCollectionPID, $issuePID, $templatePath){
		$issueRDF= new Smarty;
		$issueRDF->assign('issue_pid', $issuePID);
		$issueRDF->assign('issue_content_model_pid', $contentModelPID);
		$issueRDF->assign('parent_collection_pid', $parentCollectionPID);
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
		$non_sort_words=array(
				'the',
				'a',
				'an',
				);

		preg_match( '/^('.implode('|',$non_sort_words).')? *(.*)$/i' , $this->title, $titleArray);

		// Assign Issue Metadata
		$this->assignMODS($titleArray, $this->lccnID, $this->issueVolume, $this->issueIssue, $this->issueEdition, $this->missingPages, $templatePath);
		$this->assignRDF($this->cModel, $this->parentCollectionPID, $this->pid, $templatePath);

		// Build Pages
		foreach ($imagesToImport as $curImageToImport) {
			$pageObject=new islandoraNewspaperImportPage($this->pid, $pageContentModelPID, $curImageToImport['pageno'], $curImageToImport['filepath']);
			$pageObject->createContent($this->marcOrgID, $templatePath);
			$this->pages[]=$pageObject;
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
