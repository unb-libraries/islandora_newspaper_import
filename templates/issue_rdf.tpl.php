<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:fedora="info:fedora/fedora-system:def/relations-external#" xmlns:fedora-model="info:fedora/fedora-system:def/model#" xmlns:islandora="http://islandora.ca/ontology/relsext#">
  <rdf:Description rdf:about="info:fedora/{$issue_pid}">
    <fedora-model:hasModel rdf:resource="info:fedora/{$issue_content_model_pid}"></fedora-model:hasModel>
    <fedora:isMemberOfCollection rdf:resource="info:fedora/{$parent_collection_pid}"></fedora:isMemberOfCollection>
  </rdf:Description>
</rdf:RDF>
