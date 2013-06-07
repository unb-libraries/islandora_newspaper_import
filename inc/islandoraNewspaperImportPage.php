<?php

class islandoraNewspaperImportPage {

	function __construct($parentPID, $pageContentModelString, $pageNumber, $imageFilePath) {
		$this->pageNumber=$pageNumber;
		$this->image['filepath']=$imageFilePath;
		$this->parentPID=$parentPID;
		$this->pid=join('-',array(
								$this->parentPID,
								sprintf("%03d", $this->pageNumber)
								)
						);
		$this->cModel=$pageContentModelString;
	}

	function createContent($marcOrgID, $templatePath) {
	}

}
