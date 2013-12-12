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
   * Assigns the absolute template and XSL path.
   *
   * Without ReflectionClass it seems unlikely we can obtain the full, absolute
   * path to the drush command source tree.
   */
  private function assignTemplatePath() {
    $reflector = new ReflectionClass(get_class($this));
    $file_name = $reflector->getFileName();
    $this->templatePath = dirname($file_name) . '/../templates';
    $this->xslPath = dirname($file_name) . '/../xsl';
  }
  /**
   * Initialize and assign the Fedora repository object property and API.
   * 
   * @param string $fedora_url
   *   The full URI (including port number) for the Fedora repository
   * @param string $fedora_user
   *   Username for authentication to the Fedora repository
   * @param string $fedora_password
   *   Password for authentication to the Fedora repository
   */
  private function fedoraInit($fedora_url, $fedora_user, $fedora_password) {
    $this->connection = new RepositoryConnection($fedora_url, $fedora_user, $fedora_password);
    $this->api = new FedoraApi($this->connection);
    $cache = new SimpleCache();
    $this->repository = new FedoraRepository($this->api, $cache);
    try {
      $random_pid = uniqid() . ':' . uniqid();
      $this->api->m->ingest(array('pid' => $random_pid));
      $this->api->m->purgeObject($random_pid);
    }
    catch (Exception $feodra_exception) {
      die("Cannot ingest or purge items from random pid $random_pid (Check URL/credentials)\n");
    }
  }
  /**
   * Constructs the IslandoraNewspaperIssue object and datastreams.
   * 
   * @param string $marcorg_id
   *   The MARC organization code of the ingesting institution
   * @param string $parent_pid
   *   The persistent identifier of the the parent object
   * @param string $issue_namespace
   *   The base namespace to use in constructing persistent identifiers
   * @param string $issue_special_identifier
   *   A special identification string appended to the persistent identifier
   */
  protected function buildIssue($marcorg_id, $parent_pid, $issue_namespace, $issue_special_identifier) {
    $this->setupIssueSourceData();
    $this->validateIssueConfigData();
    $this->issue = new IslandoraNewspaperImportIssue(
            $this->api,
            SOURCE_MEDIA,
            $marcorg_id,
            ISSUE_CONTENT_MODEL_PID,
            $issue_namespace,
            $parent_pid,
            ISSUE_TITLE,
            ISSUE_LCCN,
            ISSUE_DATE,
            ISSUE_VOLUME,
            ISSUE_ISSUE,
            ISSUE_EDITION,
            MISSING_PAGES,
            ISSUE_LANGUAGE,
            $issue_special_identifier,
            ISSUE_SUPPLEMENT_TITLE,
            ISSUE_ERRATA
            );
    $this->issue->createContent(
            $this->imagesToImport,
            PAGE_CONTENT_MODEL_PID,
            $this->templatePath,
            $this->xslPath
            );
  }
  /**
   * Ingests the newspaper issue object into the Fedora repository.
   */
  protected function ingestIssue() {
    $this->issue->ingest($this->repository);
  }
  /**
   * Builds the newspaper title Fedora object and datastreams.
   * 
   * @param string $title_string
   *   The string to use as the title's title.
   * @param string $parent_pid
   *   The persistent identifier of the parent object
   * @param string $title_pid
   *   The persistent identifier to assign to this title
   */
  protected function buildTitle($title_string, $parent_pid, $title_pid) {
    $this->collectionPID = $parent_pid;
    $this->titlePID = $title_pid;
    $this->titleTitle = $title_string;
    $this->assignTemplatePath();
    $this->assignXSLPath();

    $title_rdf_smarty = new Smarty();
    $title_rdf_smarty->assign('title_pid', $this->titlePID);
    $title_rdf_smarty->assign('collection_pid', $this->collectionPID);
    $this->xml['RDF'] = new DOMDocument();
    $this->xml['RDF']->loadXML($title_rdf_smarty->fetch(implode('/', array($this->templatePath, 'title_rdf.tpl.php'))));

    $this->xml['MODS'] = new DOMDocument();
    $this->xml['MODS']->loadXML(file_get_contents(implode('/', array($this->importPath, 'MODS.xml'))));

    $mods_dc_transform = new DOMDocument();
    $mods_dc_transform->load(implode('/', array($this->xslPath, 'mods_to_dc.xsl')));
    $processor = new XSLTProcessor();
    $processor->importStylesheet($mods_dc_transform);
    $this->xml['DC'] = new DOMDocument();
    $this->xml['DC']->loadXML($processor->transformToXML($this->xml['MODS']));
  }
  /**
   * Ingests the newspaper title object into the Fedora repository.
   */
  protected function ingestTitle() {
    $title_fedora_object = new NewFedoraObject($this->titlePID, $this->repository);
    $title_fedora_object->label = $this->titleTitle;

    $title_ds_rdf = new NewFedoraDatastream('RELS-EXT', 'X', $title_fedora_object, $this->repository);
    $title_ds_rdf->content = $this->xml['RDF']->saveXML();
    $title_ds_rdf->mimetype = 'application/rdf+xml';
    $title_ds_rdf->label = 'Fedora Object to Object Relationship Metadata.';
    $title_ds_rdf->logMessage = 'RELS-EXT datastream created using Newspapers batch ingest script || SUCCESS';
    $title_fedora_object->ingestDatastream($title_ds_rdf);

    $title_ds_mods = new NewFedoraDatastream('MODS', 'M', $title_fedora_object, $this->repository);
    $title_ds_mods->content = $this->xml['MODS']->saveXML();
    $title_ds_mods->mimetype = 'text/xml';
    $title_ds_mods->label = 'MODS Record';
    $title_ds_mods->checksum = TRUE;
    $title_ds_mods->checksumType = 'MD5';
    $title_ds_mods->logMessage = 'Title MODS datastream created using Newspapers batch ingest script || SUCCESS';
    $title_fedora_object->ingestDatastream($title_ds_mods);

    $title_ds_dc = new NewFedoraDatastream('DC', 'X', $title_fedora_object, $this->repository);
    $title_ds_dc->content = $this->xml['DC']->saveXML();
    $title_ds_dc->mimetype = 'text/xml';
    $title_ds_dc->label = 'DC Record';
    $title_ds_dc->logMessage = 'DC datastream created using Newspapers batch ingest script || SUCCESS';
    $title_fedora_object->ingestDatastream($title_ds_dc);

    $this->repository->ingestObject($title_fedora_object);
    // @todo: Is there an Islandora islandora:newspaperPageCModel post ingest
    // notification hook to fire? It seems to have been phased out from
    // previous versions.
  }
  /**
   * Traverses $this->importPath to determine which images to ingest as pages.
   */
  private function setupIssueSourceData() {
    $import_path = $this->importPath;
    if (!$import_path) {
      $this->import_path = drush_prompt(dt('Path to Import Directory'), NULL, TRUE);
    }
    else {
      $this->import_path = $import_path;
    }
    $this->imagesToImport = array();
    $file_list = glob(implode('/', array($this->import_path, '*.[jJ][pP][gG]')));
    foreach ($file_list as $filepath_of_image) {
      $this->imagesToImport[] = array(
        'pageno' => str_replace(
                '.jpg',
                '',
                array_pop(
                        explode(
                                '_',
                                basename($filepath_of_image)
                                )
                        )
        ),
        'filepath' => $filepath_of_image,
      );
    }
    $this->validateSourcePath();
  }
  /**
   * Validates required metadata elements from metadata.php file.
   */
  private function validateIssueConfigData() {
    $this->validateIssueDate();
    $must_exist_data = array(
      'ISSUE_TITLE',
      'ISSUE_MEDIA',
      'ISSUE_VOLUME',
      'ISSUE_ISSUE',
    );
    foreach ($must_exist_data as $cur_data) {
      if (!constant($cur_data) || constant($cur_data) == '') {
        die ("$cur_data is not set in metadata.php\n");
      }
    }
  }
  /**
   * Validates const ISSUE_DATE.
   */
  private function validateIssueDate() {
    if (!checkdate(date('m', ISSUE_DATE), date('d', ISSUE_DATE), date('Y', ISSUE_DATE))) {
      die("Date from metadata [{ISSUE_DATE}]is invalid\n");
    }
  }
  /**
   * Validates $this->import_path to ensure that metadata.php and images exist.
   */
  private function validateSourcePath() {
    if (!@include implode('/', array($this->import_path, 'metadata.php'))) {
      die("Failed to load 'metadata.php' from {$this->import_path}\n");
    }
    if (count($this->imagesToImport) < 1) {
      die("No *.jpg files exist in import path {$this->import_path}\n");
    }
  }
}
