<?xml version="1.0" encoding="UTF-8"?>
<mods xmlns="http://www.loc.gov/mods/v3" xmlns:mods="http://www.loc.gov/mods/v3" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
<titleInfo>
<title>Page {$page_number_short}</title>
</titleInfo>
    <part>
        <extent unit="pages">
            <start>{$sequence_number}</start>
        </extent>
        <detail type="page number">
            <number>{$page_number_short}</number>
        </detail>
    </part>
    <relatedItem type="original">
        <physicalDescription>
            <form type="{$source_media}"/>
        </physicalDescription>
        <location>
            <physicalLocation authority="marcorg" displayLabel="Source Repository">{$marcorg_id}</physicalLocation>
        </location>
    </relatedItem>
    <identifier type="PID">{$pid}</identifier>
    <note type="agencyResponsibleForReproduction" displayLabel="Institution Responsible for Digitization">{$marcorg_id}</note>
</mods>
