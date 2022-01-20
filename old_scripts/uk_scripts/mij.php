<?php

class Check {

  private $people = [];

  public function __construct()
  {
    $this->loopDirWiki();
  }

  private function loopFile()
  {
    $json = file_get_contents('all_uk_single.json');
    $data = json_decode($json,true);

    $missing = 0;
    foreach($data as $k => $v) {
      $file = $v['item'];
      $name = str_replace('http://www.wikidata.org/entity/','',$file);

      if(!in_array($name,$this->people)) {
        echo "$name not found\n";
      }
    }
  }


  private function loopDirWiki()
  {
    $files = glob('json/*.{json}', GLOB_BRACE);
    foreach($files as $file) {

      $json = file_get_contents($file);
      $data = json_decode($json,true);
  
      if(!isset($data['results']['bindings'])) {
  
        echo $file;
        print_r($data);
        die();
      }
      
      foreach($data['results']['bindings'] as $k => $v) {
        $w = ($v['item']['value']);
        $q = str_replace('http://www.wikidata.org/entity/','',$w);
        $this->wikipeople[] = $q;       
      }
      

    }


  }

    // private function getWikiData($q)
    // {
    //   if(file_exists("singles/$q.json")) return;
  
    //   $url = "https://www.wikidata.org/w/api.php?action=wbgetentities&ids=$q&format=json";
    //   $json = file_get_contents($url);
    //   $data = json_decode($json,true);
      
    //   $item = 'http://www.wikidata.org/entity/' . $q;
    //   $itemLabel = $data['entities'][$q]['labels']['en']['value'];
      
    //   if(isset($data['entities'][$q]['descriptions']['en']['value'])) {
    //     $itemDescription = $data['entities'][$q]['descriptions']['en']['value'];
    //   }else{
    //     $itemDescription = '';
    //   }
      
  
    //   if(isset($data['entities'][$q]['sitelinks']['enwiki']['title'])) {
    //     $article = $this->makeWikipediaUrl($data['entities'][$q]['sitelinks']['enwiki']['title']);
    //   }else{
    //     $article = '';
    //   }
      
    //   $data = [
    //     'item' => $item,
    //     'itemLabel' => $itemLabel,
    //     'itemDescription' => $itemDescription,
    //     'article' => $article
    //   ];
  
  
    //   $arr = []; 
    //   $arr = [
    //     'results' => [
    //       'bindings' => $data
    //     ]
    //   ];
  
    //   $this->writeFile($arr,$q);
    // }
  
    // private function makeWikipediaUrl($label)
    // {
    //   $label = str_replace(' ','_',$label);
    //   return 'https://en.wikipedia.org/wiki/'.$label;
    // }
  
    // private function writeFile($arr, $q)
    // {
    //   // $json = json_encode($arr,JSON_PRETTY_PRINT);
    //   echo "Writing file with $q\n";
    //   file_put_contents('singles/'.$q.'.json',json_encode($arr,JSON_PRETTY_PRINT));
    //   sleep(1);
    // }
}

(new Check());