<?php

class islandoraNewspaperImportIssue {

	function __construct($importObject) {
		$this->importObject=$importObject;
		$this->pages=array();
		$this->issuedate=date("Ymd",ISSUE_DATE);
	}

	function addPage($pageNumber, $imagePath) {
		$this->pages[]=new islandoraNewspaperImportPage($pageNumber, $imagePath);
	}

}
