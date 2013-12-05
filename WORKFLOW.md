UNB Libraries Newspaper Digitization Workflow
==============
_Jacob Sanford (jsanford@unb.ca), Decmber 2013_

This document makes the assumption that a 'newspaper' can be accurately defined by 3 specific object types:

+   **Titles** : The newspaper entity itself, such as "The Telegraph Journal"
+   **Issues** : A complete issue of the **title**. An **issue** supplement (Magazine, TV Guide)is also considered a separate **issue**.
+   **Pages**  : Components of an **issue**

To avoid confusion, all references to each of these object types will be **represented in bold**.

Media Capture
-------
The media is captured.

Media Preparation
-------
All new **titles** (including corporate entity or name changes) should exist within their own unique folder of the storage disk. The data from each **title** should be arranged as follows:

+    A main **title** Folder containing: 
    +    a single _MODS.xml_ file describing the **title**
    +    A subtree containing multiple folders, each with images from a single **issue**. Each **issue** folder must contain:
        +    a single _metadata.php_ file, which asserts metadata for that **issue**. An example _metadata.php_ [is available here](https://github.com/unb-libraries/islandora_newspaper_import/blob/7.x/sample-data/metadata.php.example) [^issue-metadata-date]
        +    Images representing **pages** for that **issue**, one image per **page**.
            +    Images must be in JPG format[^fn-islandora-tiff]
            +    The filename of the images must be suffixed by an underscore followed by the **page** number of the **issue** they represent (XXXXX_001.jpg for page 1, XXXXX_002.jpg for page 2, etc.).
            +    In the case of **pages** from the physical item itself, be sure to omit those **pages** from the file naming numbering sequence (XXXXX_007.jpg, XXXXX_008.jpg, XXXXX_010.jpg). The missing **pages** should be noted in the _MISSING_PAGES_ free-text constant defined in _metadata.php_.
 
            
Additional Metadata Considerations
-------
+    After some discussion, a decision was made that some errors printed in the physical **issues** (Incorrectly printed volume, date, etc.) should be corrected in the _metadata.php_ file. Notes to this effect must be made in the _ISSUE_ERRATA_ constant of the _metadata.php_.


Choosing a PID/Namespace
-------
A PID is a unique, persistent identifier for a Fedora digital object. All newspaper **titles** need a base identification string (_BASESTRING_) that will be used as a base for choosing **issue** and **page** page PID values. We have defined a set of standards in defining this base identifier:

+   Use a single string that represents the **title** uniquely. Some examples:
    +    stjohneveningtimes
    +    carletonnorthnews
+   Do not use spaces, underscores, special characters or caps.

Once you have chosen the base string, it then follows that namespaces are :

+   **title** : newspapers:_BASESTRING_
+   **issue** : _BASESTRING_:YYYY-MM-DD
    +    In the case of multiple **issues** within a single day (Or supplements such as Magazines, TV Guides), append the date values with an appropriate identifier (i.e. 1994-03-12-tv-guide) to produce a unique PID for that **issue**.
+   **page** : _BASESTRING_:YYYY-MM-DD-4DIGITPAGENUMBER


Ingest Objects Into Fedora
-------
Ensure that the IP of the hardware you are importing from is authorized to the fedora API-M in the _deny-apim-if-not-localhost.xml_ in the fedora XACML policies (./data/fedora-xacml-policies/repository-policies/default/deny-apim-if-not-localhost.xml).

Ensure that [the Fedora Stomp listener and content model listeners package in python](https://github.com/Islandora/islandora_microservices) is running with our [our SQS sending plugin](https://github.com/unb-libraries/unb_libraries_newspapers).[^fedora-stomp-listener]

You can then ingest the content into Fedora / Islandora via our drush extension for Islandora ([https://github.com/unb-libraries/islandora_newspaper_import](https://github.com/unb-libraries/islandora_newspaper_import)). The [islandora_ingest_newspaper_title](https://github.com/unb-libraries/islandora_newspaper_import/blob/7.x/islandora_newspaper_ingest.drush.inc#L43-L58) is a multistep one:

+   If specified, import the metadata from a _MODS.xml_ file in the import root to create the **title** object in Fedora of type _newspaperCModel_.
+   Using a passed **title** PID (or one generated in the previous step), begin importing issues from the import tree. There are several important points:
    + All images for an issue must reside in a single directory/branch of the tree.
    + Each issue directory must contain a file named _metadata.php_, which provides metadata for that issue based on the sample model given in metadata.php.example.
    + All images must be in that directory JPG format, and have an extension of JPG[^fn-islandora-tiff]. 
    + The base name of the images must be suffixed by an underscore and the page number of the issue they represent (XXXXX_001.jpg for page 1, XXXXX_002.jpg for page 2, etc.)
    + In the case of **pages** from the physical item itself, be sure to omit those **pages** from the file naming numbering sequence (XXXXX_007.jpg, XXXXX_008.jpg, XXXXX_010.jpg). The missing **pages** should be noted in the _MISSING_PAGES_ free-text variable defined in _metadata.php_.
    + Ensure that all checkboxes are unchecked in the "CREATE PAGE DERIVATIVES LOCALLY" section in the Islandora setup (_admin/islandora/newspaper_). Islandora should not generate any of the derivaties, since microservices will generate these.

Note that a helper script _importSourceTree.py_ is included in the [islandora_newspaper_import](https://github.com/unb-libraries/islandora_newspaper_import) drush command repo. It..  

Generate Datastreams with Microservices
-------
Ensure that the IP of the hardware you are generating Datastreams from is authorized to the fedora API-M in the _deny-apim-if-not-localhost.xml_ in the fedora XACML policies (./data/fedora-xacml-policies/repository-policies/default/deny-apim-if-not-localhost.xml).

Once the objects have been ingested into the repository, generation of required Datastreams (TN, JPG, OCR, HOCR, JP2, PDF) can occur. This is done via our [islandora-newspaper-microservices python package](https://github.com/unb-libraries/islandora-newspaper-microservices)

Microservice workers using the package can be automatically deployed via our [IPXE Boot Image](https://github.com/unb-libraries/ipxe-unb-libraries) to bare metal hardware. This will install _Ubuntu 12.04 LTS_ and provision the machine with _RAZOR_ and _CHEF_ with the required packages for encoding micro services. Please ensure that the Razor Server, Chef server and tftp daemons on _TYRANT_ are running. This step will take approximately 20 minutes.

Once booted, login with root/password and issue the following commands:

    apt-get install git
    cd /etc
    git clone https://github.com/unb-libraries/chef-deployment.git
    cd chef
    ./bootStrapChef.sh
    ./initWorker.sh
    
The daemon will start, with workers polling the SQS instance and encoding the jobs recieved from the Stomp Listener, ingesting the new datastreams into the Fedora repository.

The length of the queue (and thus the microservice encoding performance) can be monitored via [http://executor.hil.unb.ca/encoding_queue/](http://executor.hil.unb.ca/encoding_queue/).

Auditing the Ingest
-------
The ingest process is not failsafe and requires post-ingest QA. The ([https://github.com/unb-libraries/islandora_newspaper_import](https://github.com/unb-libraries/islandora_newspaper_import)) package provides two commands for this :

+ [_islandora_audit_newspaper_pages_](https://github.com/unb-libraries/islandora_newspaper_import/blob/7.x/islandora_newspaper_ingest.drush.inc#L60-L67) : provides a method to audit newspaper page objects for a specified datastream(s). It returns a list of failed PIDs that can be sent via STDIN to a script such as [addDatastreamSQSQueue.py](https://github.com/unb-libraries/aws-misc-tools/blob/master/addDatastreamSQSQueue.py). 
+ [_islandora_purge_newspaper_page_datastreams_](https://github.com/unb-libraries/islandora_newspaper_import/blob/7.x/islandora_newspaper_ingest.drush.inc#L69-L77) : provides a method to delete specific datastreams from one (or all) PIDs of content type _newspaperCModel_.


[^fn-islandora-tiff]: This is contrary to the default _TIFF_ requirement for primary objects of pages in the [islandora_solution_pack_newspaper](https://github.com/Islandora/islandora_solution_pack_newspaper), which requires _TIFF_ files as the primary object for a newspaper page. Due to disk space concerns on the Fedora server, we have a [fork](https://github.com/unb-libraries/islandora_solution_pack_newspaper) of this repository which, with the understanding that [microservices will pre-convert the object to TIFF before performing OCR](https://github.com/unb-libraries/islandora-newspaper-microservices/blob/master/lib/OCRSurrogate.py#L20-L32), allows _JPG_ and has changed the labels accordingly.
[^fedora-stomp-listener]: The STOMP listener connects to the Fedora server on port _61613_ and listens for ingest notices. When those of a matching content type (_newspaperPageCModel_) are recieved, it [forwards a JSON-encoded message](https://github.com/unb-libraries/unb_libraries_newspapers/blob/master/unb_libraries_newspapers/__init__.py#L28-L34) to the SQS queue specified in the plugin _unb_libraries_newspapers.cfg_ file. Again, you should ensure that the firewall on the Fedora server has allowed traffic on port _61613_ from the server you are running the listener on.
[^issue-metadata-date]: There was an erroneous spec initially developed that specified the ISSUE_DATE line in _metadata.php_ should be formatted DD, MM, YYYY. The PHP date() function requires input of MM, DD, YYYY. [a fix was developed for this in _importSourceTree.py_](https://github.com/unb-libraries/islandora_newspaper_import/blob/7.x/importSourceTree.py#L99-L101) and users should continue to incorrectly specify the date until the backlog has been ingested. At that point, the fix can be removed from _importSourceTree.py_.
