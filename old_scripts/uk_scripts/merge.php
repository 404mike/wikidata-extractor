<?php

ini_set("memory_limit", "-1");
set_time_limit(0);

class MergeJson {

  private $people = [];

  public function __construct()
  {
    $this->loopDir();

    $this->writeFile();
  }

  private function loopDir()
  {
    $files = glob('json/*.{json}', GLOB_BRACE);
    foreach($files as $file) {
      $this->openJsonFile($file);
    }
  }

  private function openJsonFile($file)
  {
    $json = file_get_contents($file);
    $data = json_decode($json,true);

    if(!isset($data['results']['bindings'])) {

      echo $file;
      print_r($data);
      die();
    }
    
    foreach($data['results']['bindings'] as $k => $v) {
      // print_r($v);die();
      if(!isset($v['itemLabel']['value'])) continue;
      
      $this->people[] = [
        "item" => $v['item']['value'],
        "itemLabel" => $v['itemLabel']['value'],
        "itemDescription" => isset($v['itemDescription']['value']) ? $v['itemDescription']['value'] : "",
        "article" => isset($v['article']['value']) ? $v['article']['value'] : ""
      ];
    }
  }

  private function writeFile()
  {
    echo "Writing file with " . number_format(count($this->people)) . " people\n";
    file_put_contents('../wikidata_people_uk.json',json_encode($this->people,JSON_PRETTY_PRINT));
  }
}

(new MergeJson());