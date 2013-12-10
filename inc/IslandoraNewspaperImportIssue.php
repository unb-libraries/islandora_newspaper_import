<?php

class islandoraNewspaperImportIssue {

	function __construct($api, $sourceMedia, $marcOrgID, $issueContentModelPID, $nameSpace, $parentCollectionPID, $issueTitle, $lccnID, $issueDate, $issueVolume, $issueIssue, $issueEdition, $missingPages, $issueLanguage, $special_identifier, $issue_supplement_title, $issue_errata) {
		$this->marcOrgID=$marcOrgID;
		$this->specialIdentifier=$special_identifier;
		$this->sourceMedia=$sourceMedia;
		$this->issueErrata=$issue_errata;
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
		$this->issueLanguage=$issueLanguage;
		$this->issueSupplementTitle=$issue_supplement_title;
		$this->sequenceNumber=1;
		$this->assignPID();
		$this->pages=array();
		$this->assignTemplatePath();
		$this->assignXSLPath();
	}

	function addPage($pageNumber, $imagePath) {
		$this->pages[]=new islandoraNewspaperImportPage($pageNumber, $imagePath);
	}

	function addPages($filePathArray) {
		foreach ($filePathArray as $curImageFilePath) {
			print $curImageFilePath;
		}
	}

	function assignDC(){
		$transformXSL = new DOMDocument();
		$transformXSL->load(join('/', array($this->XSLpath, 'mods_to_dc.xsl')));
		$processor = new XSLTProcessor();
		$processor->importStylesheet($transformXSL);
		$this->xml['DC'] = new DOMDocument();
		$this->xml['DC']->loadXML($processor->transformToXML($this->xml['MODS']));
	}

	function assignMODS($titleArray){
		$issueMODS= new Smarty;
		$issueMODS->assign('lccn_id', $this->lccnID);
		$issueMODS->assign('issue_volume', $this->issueVolume);
		$issueMODS->assign('issue_issue', $this->issueIssue);
		$issueMODS->assign('issue_edition', $this->issueEdition);
		$issueMODS->assign('non_sort_title', $titleArray[1]);
		$issueMODS->assign('sort_title', $titleArray[2]);
		$issueMODS->assign('iso_date', $this->getISODate());
		if ( $this->issueErrata !='' ) $issueMODS->assign('issue_errata', $this->issueErrata);
		if ( $this->missingPages !='' ) $issueMODS->assign('missing_pages', $this->missingPages);
		if ( $this->issueSupplementTitle !='' ) $issueMODS->assign('issue_supplement_title', $this->issueSupplementTitle);
		$this->xml['MODS'] = new DOMDocument();
		$this->xml['MODS']->loadXML($issueMODS->fetch(join('/', array($this->templatepath, 'issue_mods.tpl.php'))));
	}

	function assignRDF(){
		$issueRDF= new Smarty;
		$issueRDF->assign('issue_pid', $this->pid);
		$issueRDF->assign('issue_content_model_pid', $this->cModel);
		$issueRDF->assign('parent_collection_pid', $this->parentCollectionPID);
		$issueRDF->assign('sequence_number', $this->sequenceNumber);
		$issueRDF->assign('date_issued', date("Y-m-d",$this->issueDate));
		if ( $this->specialIdentifier !='' ) {
			$issueRDF->assign('issue_supplement_parent', $this->namespace.':'.$this->getISODate());
		} else {
			$issueRDF->assign('issue_supplement_parent', '');
		}
		$this->xml['RDF'] = new DOMDocument();
		$this->xml['RDF']->loadXML($issueRDF->fetch(join('/', array($this->templatepath, 'issue_rdf.tpl.php'))));

	}

	function assignPID() {
		$pidArray = array(
						$this->namespace,
					);

		if ($this->specialIdentifier != '') {
			$pidArray[]=$this->getISODate().'-'.$this->specialIdentifier;
		} else {
			$pidArray[]=$this->getISODate();
		}

		$this->pid=join(
					":",
					$pidArray
				);

		// Truncate the PID to 60 characters, since '-###' will be added for the page numbers and 64 is the max.
		$this->pid = substr($this->pid,0,60);
	}

	function createContent($imagesToImport, $pageContentModelPID) {
		// TODO : Move this out!
		$non_sort_words=array(
				'the',
				'a',
				'an',
				);

		preg_match( '/^('.implode('|',$non_sort_words).')? *(.*)$/i' , $this->title, $titleArray);

		$this->assignMODS($titleArray);
		$this->assignRDF();
		$this->assignDC();

		foreach ($imagesToImport as $curImageToImport) {
			$pageObject=new islandoraNewspaperImportPage($this->pid, $pageContentModelPID, $this->sourceMedia, $curImageToImport['pageno'], $curImageToImport['filepath'], $this->marcOrgID, $this->issueLanguage);
			$pageObject->createContent($this->marcOrgID);
			$this->pages[]=$pageObject;
		}
	}

	function ingest($repository) {
		// TODO : refactor
		// ISSUE
		$issueObjectToIngest = new NewFedoraObject($this->pid, $repository);
		$issueObjectToIngest->label = $this->getLabel();

		// MODS
		$issueDSMODS = new NewFedoraDatastream('MODS', 'M', $issueObjectToIngest, $repository);
		$issueDSMODS->content = $this->xml['MODS']->saveXML();
		$issueDSMODS->mimetype = 'text/xml';
		$issueDSMODS->label = 'MODS Record';
		$issueDSMODS->checksum = TRUE;
		$issueDSMODS->checksumType = 'MD5';
		$issueDSMODS->logMessage = 'MODS datastream created using Newspapers batch ingest script || SUCCESS';
		$issueObjectToIngest->ingestDatastream($issueDSMODS);

		// DC
		$issueDSDC = new NewFedoraDatastream('DC', 'X', $issueObjectToIngest, $repository);
		$issueDSDC->content = $this->xml['DC']->saveXML();
		$issueDSDC->mimetype = 'text/xml';
		$issueDSDC->label = 'DC Record';
		$issueDSDC->logMessage = 'DC datastream created using Newspapers batch ingest script || SUCCESS';
		$issueObjectToIngest->ingestDatastream($issueDSDC);

		// RDF
		$issueDSRDF = new NewFedoraDatastream('RELS-EXT', 'X', $issueObjectToIngest, $repository);
		$issueDSRDF->content = $this->xml['RDF']->saveXML();
		$issueDSRDF->mimetype = 'application/rdf+xml';
		$issueDSRDF->label = 'Fedora Object to Object Relationship Metadata.';
		$issueDSRDF->logMessage = 'RELS-EXT datastream created using Newspapers batch ingest script || SUCCESS';
		$issueObjectToIngest->ingestDatastream($issueDSRDF);

		$repository->ingestObject($issueObjectToIngest);
		islandora_newspaper_islandora_newspaperissuecmodel_mods_islandora_datastream_ingested($issueObjectToIngest, $issueDSMODS);

		// PAGES
		foreach ($this->pages as $currentImportPage) {
			$pageObjectToIngest = new NewFedoraObject($currentImportPage->pid, $repository);
			$pageObjectToIngest->label = $currentImportPage->getLabel();

			// Primary JPG Datastream
			$pageDSJPG = new NewFedoraDatastream('OBJ', 'M', $issueObjectToIngest, $repository);
			$pageDSJPG->setContentFromFile($currentImportPage->image['filepath'], FALSE);
			$pageDSJPG->mimetype = 'image/jpeg';
			$pageDSJPG->label = 'JPG image';
			$pageDSJPG->checksum = TRUE;
			$pageDSJPG->checksumType = 'MD5';
			$pageDSJPG->logMessage = 'JPG datastream created using Newspapers batch ingest script || SUCCESS';
			$pageObjectToIngest->ingestDatastream($pageDSJPG);

			// DC
			$pageDSDC = new NewFedoraDatastream('DC', 'X', $issueObjectToIngest, $repository);
			$pageDSDC->content = $currentImportPage->xml['DC']->saveXML();
			$pageDSDC->mimetype = 'text/xml';
			$pageDSDC->label = 'DC Record';
			$pageDSDC->logMessage = 'DC datastream created using Newspapers batch ingest script || SUCCESS';
			$pageObjectToIngest->ingestDatastream($pageDSDC);

			// RDF
			$pageDSRDF = new NewFedoraDatastream('RELS-EXT', 'X', $issueObjectToIngest, $repository);
			$pageDSRDF->content = $currentImportPage->xml['RDF']->saveXML();
			$pageDSRDF->mimetype = 'application/rdf+xml';
			$pageDSRDF->label = 'Fedora Object to Object Relationship Metadata.';
			$pageDSRDF->logMessage = 'RELS-EXT datastream created using Newspapers batch ingest script || SUCCESS';
			$pageObjectToIngest->ingestDatastream($pageDSRDF);

			$repository->ingestObject($pageObjectToIngest);
			sleep (5);
		}
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
		return date("Y-m-d",$this->issueDate);
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

	function assignTemplatePath() {
		$reflector = new ReflectionClass(get_class($this));
		$fn = $reflector->getFileName();
		$this->templatepath=dirname($fn). '/../templates';
	}

	function assignXSLPath() {
		$reflector = new ReflectionClass(get_class($this));
		$fn = $reflector->getFileName();
		$this->XSLpath=dirname($fn). '/../xsl';
	}

	function getLabel() {
		$issueLabel = "Volume {$this->issueVolume}, Number {$this->issueIssue}";
		if ( $this->specialIdentifier !='' ) {
			$issueLabel = $issueLabel . ' (' . ucwords(str_replace('-',' ',$this->specialIdentifier)) . ')';
		}
		return ($issueLabel);
	}
}
