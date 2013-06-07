
<rdf:RDF xmlns:fedora="info:fedora/fedora-system:def/relations-external#" xmlns:fedora-model="info:fedora/fedora-system:def/model#" xmlns:islandora="http://islandora.ca/ontology/relsext#" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">
  <rdf:Description rdf:about="info:fedora/{$issue_pid}">
    <fedora-model:hasModel rdf:resource="info:fedora/islandora:newspaperIssueCModel"></fedora-model:hasModel>
    <fedora:isMemberOf rdf:resource="info:fedora/{$newspaper_pid}"></fedora:isMemberOf>
    <islandora:isSequenceNumber>1</islandora:isSequenceNumber>
    <islandora:dateIssued>{$date_issued}</islandora:dateIssued>
  </rdf:Description>
</rdf:RDF>
