<?php

class SPARQLQueryDispatcher
{
    private $endpointUrl;

    public function __construct(string $endpointUrl)
    {
        $this->endpointUrl = $endpointUrl;
    }

    public function query(string $sparqlQuery): array
    {

        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => [
                    'Accept: application/sparql-results+json',
                    'User-Agent: WDQS-example PHP/' . PHP_VERSION, // TODO adjust this; see https://w.wiki/CX6
                ],
            ],
        ];
        $context = stream_context_create($opts);

        $url = $this->endpointUrl . '?query=' . urlencode($sparqlQuery);
        $response = file_get_contents($url, false, $context);
        return json_decode($response, true);
    }
}

$endpointUrl = 'https://query.wikidata.org/sparql';
$sparqlQueryString = <<< 'SPARQL'
PREFIX  schema: <http://schema.org/>
PREFIX  bd:   <http://www.bigdata.com/rdf#>
PREFIX  wdt:  <http://www.wikidata.org/prop/direct/>
PREFIX  wikibase: <http://wikiba.se/ontology#>

SELECT DISTINCT  ?item ?itemLabel ?itemDescription (SAMPLE(?DR) AS ?DRSample) (SAMPLE(?article) AS ?articleSample)
WHERE
  { ?article  schema:about       ?item ;
              schema:inLanguage  "en" ;
              schema:isPartOf    <https://en.wikipedia.org/>
    FILTER ( ?item = <http://www.wikidata.org/entity/Q725671> )
    OPTIONAL
      { ?item  wdt:P569  ?DR }
    OPTIONAL
      { ?item  wdt:P570  ?RIP }
    OPTIONAL
      { ?item  wdt:P18  ?image }
    SERVICE wikibase:label
      { bd:serviceParam
                  wikibase:language  "en"
      }
  }
GROUP BY ?item ?itemLabel ?itemDescription
SPARQL;

$queryDispatcher = new SPARQLQueryDispatcher($endpointUrl);
$queryResult = $queryDispatcher->query($sparqlQueryString);

var_export($queryResult);
