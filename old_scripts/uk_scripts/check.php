<?php

class MergeJson {

  private $people = [];

  public function __construct()
  {
    $this->loopDir();

    $this->loopPeople();
  }

  private function loopDir()
  {
    $files = glob('temp/*.{json}', GLOB_BRACE);
    foreach($files as $file) {
      $this->openJsonFile($file);
    }
  }

  private function openJsonFile($file)
  {
    $json = file_get_contents($file);
    $data = json_decode($json,true);

    foreach($data['results']['bindings'] as $k => $v) {
      
      $qid = $v['item']['value'];
      $qid = str_replace('http://www.wikidata.org/entity/','',$qid);

      $this->people[] = $qid;
    }
  }

  private function loopPeople()
  {
    $numPeople = count($this->people);

    $loop = 1;
    foreach($this->people as $k => $qid) {
      $remaining = number_format(($loop/$numPeople) * 100,2);
      echo "Trying to get person $loop out of $numPeople - $remaining%\n";
  
      $this->getWikiData($qid);
      $loop++;
    }
  }

  private function getWikiData($q)
  {
    if(file_exists("singles/$q.json")) return;

    $url = "https://www.wikidata.org/w/api.php?action=wbgetentities&ids=$q&format=json";
    $json = file_get_contents($url);
    $data = json_decode($json,true);
    
    $item = 'http://www.wikidata.org/entity/' . $q;
    $itemLabel = $data['entities'][$q]['labels']['en']['value'];
    
    if(isset($data['entities'][$q]['descriptions']['en']['value'])) {
      $itemDescription = $data['entities'][$q]['descriptions']['en']['value'];
    }else{
      $itemDescription = '';
    }
    

    if(isset($data['entities'][$q]['sitelinks']['enwiki']['title'])) {
      $article = $this->makeWikipediaUrl($data['entities'][$q]['sitelinks']['enwiki']['title']);
    }else{
      $article = '';
    }
    
    $data = [
      'item' => $item,
      'itemLabel' => $itemLabel,
      'itemDescription' => $itemDescription,
      'article' => $article
    ];


    $arr = []; 
    $arr = [
      'results' => [
        'bindings' => $data
      ]
    ];

    $this->writeFile($arr,$q);
  }

  private function makeWikipediaUrl($label)
  {
    $label = str_replace(' ','_',$label);
    return 'https://en.wikipedia.org/wiki/'.$label;
  }

  private function writeFile($arr, $q)
  {
    // $json = json_encode($arr,JSON_PRETTY_PRINT);
    echo "Writing file with $q\n";
    file_put_contents('singles/'.$q.'.json',json_encode($arr,JSON_PRETTY_PRINT));
    sleep(1);
  }
}

(new MergeJson());