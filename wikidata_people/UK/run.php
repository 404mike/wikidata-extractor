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
        $response = @file_get_contents($url, false, $context);
        return json_decode($response, true);
    }
}

class LoopData {

    public function __construct()
    {
        for($i = 0; $i <= 1780; $i++) {
            $percent_remaing = ($i / 1780) * 100;
            $percent_remaing = round($percent_remaing, 2);
            echo "Loop $i out of 1780 ($percent_remaing%) - ";
            $this->query($i);
        }
    }

    private function query($offset, $retry=false)
    {  
        if(!$retry) {
            if($offset != 0) $offset = $offset . '00';
        }        

        echo "Trying offset $offset\n";
        if(file_exists("json/$offset.json")) return;

        $endpointUrl = 'https://query.wikidata.org/sparql';
        $sparqlQueryString = <<< 'SPARQL'
        PREFIX schema: <http://schema.org/>
        SELECT ?item ?itemLabel ?itemDescription ?article
        WHERE 
        {
        ?item wdt:P27 wd:Q145.
        SERVICE wikibase:label { bd:serviceParam wikibase:language "[AUTO_LANGUAGE],en". }
        OPTIONAL {
            ?article schema:about ?item .
            ?article schema:inLanguage "en" .
            ?article schema:isPartOf <https://en.wikipedia.org/> .
            }
        }
        LIMIT 100
        OFFSET {$OFFSET}
        SPARQL;

        
        // echo $sparqlQueryString;
        // echo "\n";
        // die();

        try {
            $this->executeScript($offset,$sparqlQueryString,$endpointUrl);
        } catch (\Throwable $th) {
            //throw $th;
            $sleep = 20;
            echo "Error getting $offset - waiting $sleep seconds\n";
            sleep($sleep);
            $this->query($offset,true);
        }

        // var_export($queryResult);

        // die();
    }

    private function executeScript($offset,$sparqlQueryString,$endpointUrl)
    {
        $sparqlQueryString = str_replace('{$OFFSET}',$offset,$sparqlQueryString);

        $queryDispatcher = new SPARQLQueryDispatcher($endpointUrl);
        $queryResult = $queryDispatcher->query($sparqlQueryString);

        file_put_contents("json/$offset.json",json_encode($queryResult,JSON_PRETTY_PRINT));
        echo "Successfully written $offset.json\n";
        sleep(2);
    }
}

(new LoopData());



