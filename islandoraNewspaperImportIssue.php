<?php

class islandoraNewspaperImportIssue {

	function __construct($importObject) {
		$this->importObject=$importObject;
		$this->pages=array();
		$this->issuedate=new date();
	}

	function addPage($pageNumber, $imagePath) {
		$this->pages[]=new islandoraNewspaperImportPage($pageNumber, $imagePath);
	}

}
