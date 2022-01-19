<?php

class Colors
{
    private $foreground_colors = array();
    private $background_colors = array();
    
    public function __construct()
    {
        // Set up shell colors
        $this->foreground_colors['black']        = '0;30';
        $this->foreground_colors['dark_gray']    = '1;30';
        $this->foreground_colors['blue']         = '0;34';
        $this->foreground_colors['light_blue']   = '1;34';
        $this->foreground_colors['green']        = '0;32';
        $this->foreground_colors['light_green']  = '1;32';
        $this->foreground_colors['cyan']         = '0;36';
        $this->foreground_colors['light_cyan']   = '1;36';
        $this->foreground_colors['red']          = '0;31';
        $this->foreground_colors['light_red']    = '1;31';
        $this->foreground_colors['purple']       = '0;35';
        $this->foreground_colors['light_purple'] = '1;35';
        $this->foreground_colors['brown']        = '0;33';
        $this->foreground_colors['yellow']       = '1;33';
        $this->foreground_colors['light_gray']   = '0;37';
        $this->foreground_colors['white']        = '1;37';
        
        $this->background_colors['black']      = '40';
        $this->background_colors['red']        = '41';
        $this->background_colors['green']      = '42';
        $this->background_colors['yellow']     = '43';
        $this->background_colors['blue']       = '44';
        $this->background_colors['magenta']    = '45';
        $this->background_colors['cyan']       = '46';
        $this->background_colors['light_gray'] = '47';
    }
    
    // Returns colored string
    public function getColoredString($string, $foreground_color = null, $background_color = null)
    {
        $colored_string = "";
        
        // Check if given foreground color found
        if (isset($this->foreground_colors[$foreground_color])) {
            $colored_string .= "\033[" . $this->foreground_colors[$foreground_color] . "m";
        }
        // Check if given background color found
        if (isset($this->background_colors[$background_color])) {
            $colored_string .= "\033[" . $this->background_colors[$background_color] . "m";
        }
        
        // Add string and end coloring
        $colored_string .= $string . "\033[0m";
        
        return $colored_string;
    }
    
    // Returns all foreground color names
    public function getForegroundColors()
    {
        return array_keys($this->foreground_colors);
    }
    
    // Returns all background color names
    public function getBackgroundColors()
    {
        return array_keys($this->background_colors);
    }
}

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

    private $colors;

    public function __construct()
    {
        $this->colors = new Colors();

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
            $sleep = 30;
            // $message = "Error getting $offset - waiting $sleep seconds\n";
            
            echo $this->colors->getColoredString("Error", "light_gray", "red") . " getting $offset - waiting $sleep seconds\n";;
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
        // $message = "Successfully written $offset.json\n";
        echo $this->colors->getColoredString("Successfully", null, "cyan") . " written $offset.json\n";;
        sleep(2);
    }
}

(new LoopData());



