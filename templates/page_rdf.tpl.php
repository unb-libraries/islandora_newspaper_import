<rdf:RDF xmlns:fedora="info:fedora/fedora-system:def/relations-external#" xmlns:fedora-model="info:fedora/fedora-system:def/model#" xmlns:islandora="http://islandora.ca/ontology/relsext#" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">
  <rdf:Description rdf:about="info:fedora/{$page_pid}">
    <fedora-model:hasModel rdf:resource="info:fedora/{$page_content_model_pid}"></fedora-model:hasModel>
    <islandora:isPageOf rdf:resource="info:fedora/{$parent_issue_pid}"></islandora:isPageOf>
    <islandora:isSequenceNumber>{$sequence_number}</islandora:isSequenceNumber>
    <islandora:isPageNumber>{$page_number_short}</islandora:isPageNumber>
    <islandora:isSection>{$section_number}</islandora:isSection>
    <fedora:isMemberOf rdf:resource="info:fedora/{$parent_issue_pid}"></fedora:isMemberOf>
    <islandora:hasLanguage>{$page_language}</islandora:hasLanguage>
  </rdf:Description>
</rdf:RDF>