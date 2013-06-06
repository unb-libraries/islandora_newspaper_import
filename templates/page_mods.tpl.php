<?xml version="1.0" encoding="UTF-8"?>
<mods xmlns="http://www.loc.gov/mods/v3" xmlns:mods="http://www.loc.gov/mods/v3" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
<titleInfo>
<title>Page $page_number_short</title>
</titleInfo>
    <part>
        <extent unit="pages">
            <start>$sequence_number</start>
        </extent>
        <detail type="page number">
            <number>$page_number_short</number>
        </detail>
    </part>
    <relatedItem type="original">
        <physicalDescription>
            <form type="microfilm"/>
        </physicalDescription>
        <identifier type="reel number">$reel_number</identifier>
        <identifier type="reel sequence number">$sequence_number</identifier>
        <location>
        <!-- the physicalLocation element should be a variable PCU is UPEI -->
            <physicalLocation authority="marcorg" displayLabel="Source Repository">PCU</physicalLocation>
        </location>
    </relatedItem>
    <identifier type="PID">$pid</identifier>
    <!-- the note type="agencyResponsibleForReproduction" element should be a variable PCU is UPEI -->
    <note type="agencyResponsibleForReproduction" displayLabel="Institution Responsible for Digitization">PCU</note>
</mods>
