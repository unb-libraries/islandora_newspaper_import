<?php

class islandoraNewspaperImportPage {

	function __construct($parentPID, $pageContentModelString, $sourceMedia, $pageNumber, $imageFilePath, $marcOrgID) {
		$this->pageNumber=$pageNumber;
		$this->sourceMedia=$sourceMedia;
		$this->sequenceNumber=1;
		$this->image['filepath']=$imageFilePath;
		$this->parentPID=$parentPID;
		$this->pid=join('-',array(
								$this->parentPID,
								sprintf("%03d", $this->pageNumber)
								)
						);
		$this->cModel=$pageContentModelString;
		$this->marcOrgID=$marcOrgID;
	}

	function assignMODS($templatePath){
		$pageMODS= new Smarty;
		$pageMODS->assign('pid', $this->pid);
		$pageMODS->assign('page_number_short', $this->pageNumber);
		$pageMODS->assign('sequence_number', $this->sequenceNumber);
		$pageMODS->assign('source_media', $this->sourceMedia);
		$pageMODS->assign('marcorg_id', $this->marcOrgID);
		$this->xml['MODS']=$pageMODS->fetch( join('/',array($templatePath,'page_mods.tpl.php')) );
	}

	function assignRDF($templatePath){
		$pageRDF= new Smarty;
		$pageRDF->assign('page_pid',$this->pid );
		$pageRDF->assign('page_content_model_pid', $this->cModel);
		$pageRDF->assign('page_number_short', $this->pageNumber);
		$pageRDF->assign('parent_issue_pid', $this->parentPID);
		$this->xml['RDF']=$pageRDF->fetch( join('/',array($templatePath,'page_rdf.tpl.php')) );
	}

	function createContent($marcOrgID, $templatePath) {
		$this->assignMODS($templatePath);
		$this->assignRDF($templatePath);
		print_r($this);
	}

}
