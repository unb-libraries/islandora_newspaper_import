<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:fedora="info:fedora/fedora-system:def/relations-external#" xmlns:fedora-model="info:fedora/fedora-system:def/model#" xmlns:islandora="http://islandora.ca/ontology/relsext#">
<rdf:Description rdf:about="info:fedora/{$page_pid}">
        <fedora-model:hasModel xmlns="info:fedora/fedora-system:def/model#" rdf:resource="info:fedora/{$page_content_model_pid}"></fedora-model:hasModel>
        <fedora:isSequenceNumber xmlns="http://islandora.ca/ontology/relsext#">{$page_number_short}</fedora:isSequenceNumber>
        <fedora:isMemberOf xmlns="info:fedora/fedora-system:def/relations-external#" rdf:resource="info:fedora/{$parent_issue_pid}"></fedora:isMemberOf>
        <fedora:isSection xmlns="http://islandora.ca/ontology/relsext#">1</fedora:isSection>
        <fedora:isPageOf xmlns="http://islandora.ca/ontology/relsext#" rdf:resource="inf:fedora/{$parent_issue_pid}"></fedora:isPageOf>
        <fedora:isPageNumber xmlns="http://islandora.ca/ontology/relsext#">{$page_number_short}</fedora:isPageNumber>
</rdf:Description>
</rdf:RDF>
