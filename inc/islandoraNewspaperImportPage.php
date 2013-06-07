<?php

class islandoraNewspaperImportPage {

	function __construct($pageNumber, $imagePath) {
		$this->pageNumber=$pageNumber;
		$this->image=new stdClass();
		$this->image->path=$imagePath;
	}

}
