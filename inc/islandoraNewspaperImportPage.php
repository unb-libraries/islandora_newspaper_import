<?php

class islandoraNewspaperImportPage {

	function __construct($parentPID, $pageContentModelString, $sourceMedia, $pageNumber, $imageFilePath, $marcOrgID, $pageLanguage) {
		$this->pageNumber=$pageNumber;
		$this->sourceMedia=$sourceMedia;
		$this->sequenceNumber=$this->pageNumber;
		$this->sectionNumber=1;
		$this->pageLanguage=$pageLanguage;
		$this->image['filepath']=$imageFilePath;
		$this->parentPID=$parentPID;
		$this->cModel=$pageContentModelString;
		$this->marcOrgID=$marcOrgID;
		$this->assignPID();
		$this->assignTemplatePath();
		$this->assignXSLPath();
	}

	function assignDC(){
		$transformXSL = new DOMDocument();
		$transformXSL->load(join('/', array($this->XSLpath, 'mods_to_dc.xsl')));
		$processor = new XSLTProcessor();
		$processor->importStylesheet($transformXSL);
		$this->xml['DC'] = new DOMDocument();
		$this->xml['DC']->loadXML($processor->transformToXML($this->xml['MODS']));
	}

	function assignMODS(){
		$pageMODS= new Smarty;
		$pageMODS->assign('pid', $this->pid);
		$pageMODS->assign('page_number_short', $this->pageNumber);
		$pageMODS->assign('sequence_number', $this->sequenceNumber);
		$pageMODS->assign('source_media', $this->sourceMedia);
		$pageMODS->assign('marcorg_id', $this->marcOrgID);
		$this->xml['MODS'] = new DOMDocument();
		$this->xml['MODS']->loadXML($pageMODS->fetch( join('/',array($this->templatepath,'page_mods.tpl.php')) ));
	}

	function assignPID(){
		$this->pid=join('-',array(
						$this->parentPID,
						sprintf("%03d", $this->pageNumber)
						)
				);
	}

	function assignRDF(){
		$pageRDF= new Smarty;
		$pageRDF->assign('page_pid',$this->pid );
		$pageRDF->assign('page_content_model_pid', $this->cModel);
		$pageRDF->assign('page_number_short', $this->pageNumber);
		$pageRDF->assign('parent_issue_pid', $this->parentPID);
		$pageRDF->assign('sequence_number', $this->sequenceNumber);
		$pageRDF->assign('section_number', $this->sectionNumber);
		$pageRDF->assign('page_language', $this->pageLanguage);
		$this->xml['RDF'] = new DOMDocument();
		$this->xml['RDF']->loadXML($pageRDF->fetch( join('/',array($this->templatepath,'page_rdf.tpl.php')) ));
	}

	function createContent() {
		$this->assignMODS();
		$this->assignRDF();
		$this->assignDC();
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
}
