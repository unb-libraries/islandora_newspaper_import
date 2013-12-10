<?php

/**
 * @file
 * Contains IslandoraNewspaperImport.
 */

/**
 * Represents a newspaper import session.
 *
 * The IslandoraNewspaperImport class provides an import method for either
 * issues or Titles into Fedora/Islandora.
 */
class IslandoraNewspaperImport {
  /**
   * Constructs an IslandoraNewspaperImport object.
   *
   * @param string $fedora_url
   *   The full URI (including port number) for the Fedora repository
   * @param string $fedora_user
   *   Username for authentication to the Fedora repository
   * @param string $fedora_password
   *   Password for authentication to the Fedora repository
   * @param string $import_path
   *   The path that contains the import data
   */
  protected function __construct($fedora_url, $fedora_user, $fedora_password, $import_path) {
    $this->fedoraInit($fedora_url, $fedora_user, $fedora_password);
    $this->importPath = $import_path;
  }
  /**
   * Assigns the absolute template path.
   *
   * Without ReflectionClass it seems unlikely we can obtain the full, absolute
   * path to the drush command source tree.
   */
  public function assignTemplatePath() {
    $reflector = new ReflectionClass(get_class($this));
    $fn = $reflector->getFileName();
    $this->templatepath=dirname($fn). '/../templates';
  }

  function assignXSLPath() {
    $reflector = new ReflectionClass(get_class($this));
    $fn = $reflector->getFileName();
    $this->XSLpath=dirname($fn). '/../xsl';
  }

  function fedoraInit($repoURL,$repoUser,$repoPass) {
    print "$repoURL,$repoUser,$repoPass";
    $this->connection = new RepositoryConnection($repoURL,$repoUser,$repoPass);
    $this->api = new FedoraApi($this->connection);
    $cache = new SimpleCache();
    $this->repository = new FedoraRepository($this->api, $cache);
    try {
      $randPID=uniqid().':'.uniqid();
      $this->api->m->ingest(array('pid' => $randPID));
      $this->api->m->purgeObject($randPID);
    } catch (Exception $e) {
      die("Cannot ingest or purge items from random pid $randPID (Check URL/credentials)\n");
    }
  }

  function buildIssue($marcOrgID, $parentCollectionPID, $nameSpace, $special_identifier) {
    $this->setupIssueSourceData();
    $this->validateIssueConfigData();
    $this->issue=new islandoraNewspaperImportIssue($this->api, SOURCE_MEDIA, $marcOrgID, ISSUE_CONTENT_MODEL_PID, $nameSpace, $parentCollectionPID, ISSUE_TITLE, ISSUE_LCCN, ISSUE_DATE, ISSUE_VOLUME, ISSUE_ISSUE, ISSUE_EDITION, MISSING_PAGES, ISSUE_LANGUAGE, $special_identifier, ISSUE_SUPPLEMENT_TITLE, ISSUE_ERRATA);
    $this->issue->createContent($this->imagesToImport, PAGE_CONTENT_MODEL_PID, $templatePath, $xslPath);
  }

  function ingestIssue() {
    $this->issue->ingest($this->repository);
  }

  function buildTitle($title_string, $collection_pid, $title_pid) {
    $this->collectionPID = $collection_pid;
    $this->titlePID = $title_pid;
    $this->titleTitle = $title_string;
    $this->assignTemplatePath();
    $this->assignXSLPath();

    // Generate RDF
    $titleRDF= new Smarty;
    $titleRDF->assign('title_pid', $this->titlePID);
    $titleRDF->assign('collection_pid', $this->collectionPID);
    $this->xml['RDF'] = new DOMDocument();
    $this->xml['RDF']->loadXML($titleRDF->fetch(join('/', array($this->templatepath, 'title_rdf.tpl.php'))));

    // Generate MODS
    $this->xml['MODS'] = new DOMDocument();
    $this->xml['MODS']->loadXML(file_get_contents(join('/', array($this->importPath,'MODS.xml'))));

    // Generate DC
    $transformXSL = new DOMDocument();
    $transformXSL->load(join('/', array($this->XSLpath, 'mods_to_dc.xsl')));
    $processor = new XSLTProcessor();
    $processor->importStylesheet($transformXSL);
    $this->xml['DC'] = new DOMDocument();
    $this->xml['DC']->loadXML($processor->transformToXML($this->xml['MODS']));
  }

  function ingestTitle() {
    $titleObjectToIngest = new NewFedoraObject($this->titlePID, $this->repository);
    $titleObjectToIngest->label = $this->titleTitle;

    // Add RDF
    $titleDSRDF = new NewFedoraDatastream('RELS-EXT', 'X', $titleObjectToIngest, $this->repository);
    $titleDSRDF->content = $this->xml['RDF']->saveXML();
    $titleDSRDF->mimetype = 'application/rdf+xml';
    $titleDSRDF->label = 'Fedora Object to Object Relationship Metadata.';
    $titleDSRDF->logMessage = 'RELS-EXT datastream created using Newspapers batch ingest script || SUCCESS';
    $titleObjectToIngest->ingestDatastream($titleDSRDF);

    // Add MODS
    $titleDSMODS = new NewFedoraDatastream('MODS', 'M', $titleObjectToIngest, $this->repository);
    $titleDSMODS->content = $this->xml['MODS']->saveXML();
    $titleDSMODS->mimetype = 'text/xml';
    $titleDSMODS->label = 'MODS Record';
    $titleDSMODS->checksum = TRUE;
    $titleDSMODS->checksumType = 'MD5';
    $titleDSMODS->logMessage = 'Title MODS datastream created using Newspapers batch ingest script || SUCCESS';
    $titleObjectToIngest->ingestDatastream($titleDSMODS);

    // Add DC
    $titleDSDC = new NewFedoraDatastream('DC', 'X', $titleObjectToIngest, $this->repository);
    $titleDSDC->content = $this->xml['DC']->saveXML();
    $titleDSDC->mimetype = 'text/xml';
    $titleDSDC->label = 'DC Record';
    $titleDSDC->logMessage = 'DC datastream created using Newspapers batch ingest script || SUCCESS';
    $titleObjectToIngest->ingestDatastream($titleDSDC);

    $this->repository->ingestObject($titleObjectToIngest);
    // TODO: Islandora newspapercmodel hook to fire?
  }

  function setupIssueSourceData() {
    $importPath = $this->importPath;
    if (!$importPath) {
      $this->import_path=drush_prompt(dt('Path to Import Directory'), NULL, TRUE);
    } else {
      $this->import_path=$importPath;
    }
    $this->imagesToImport=array();
    $file_list=glob(join('/',array($this->import_path,'*.[jJ][pP][gG]')));
    foreach ($file_list as $filePathToImport) {
      $this->imagesToImport[]=array(
                'pageno' => str_replace('.jpg','', array_pop(explode('_', basename($filePathToImport)))),
                'filepath' => $filePathToImport
                );
    }
    $this->validateSourcePath();
  }

  function validateIssueConfigData() {
    $this->validateIssueDate();
    $must_exist_data=array(
        'ISSUE_TITLE',
        'ISSUE_MEDIA',
        'ISSUE_VOLUME',
        'ISSUE_ISSUE',
        # 'ISSUE_EDITION',
        );
    foreach ($must_exist_data as $cur_data) if (!constant($cur_data) || constant($cur_data) == '') die ("$cur_data is not set in metadata.php\n");
  }

  function validateIssueDate(){
    if (!checkdate(date('m',ISSUE_DATE), date('d',ISSUE_DATE), date('Y',ISSUE_DATE))) {
      die("Date from metadata [{ISSUE_DATE}]is invalid\n");
    }
  }

  function validateSourcePath() {
    if(!@include(join('/', array($this->import_path,'metadata.php')))) die("Failed to load 'metadata.php' from {$this->import_path}\n");
    if (sizeof($this->imagesToImport) < 1 ) die("No *.jpg files exist in import path {$this->import_path}\n");
  }
}
