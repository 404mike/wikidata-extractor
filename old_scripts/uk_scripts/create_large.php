<?php

class CreateLarge {

  private $people = [];

  public function __construct()
  {
    $this->loopDir();

    $this->writeJSon();
  }

  private function loopDir()
  {
    $files = glob('singles/*.{json}', GLOB_BRACE);
    foreach($files as $file) {
      $this->openJsonFile($file);
    }
  }

  private function openJsonFile($file)
  {
    $json = file_get_contents($file);
    $data = json_decode($json,true);

    if(empty($data['results']['bindings']['itemLabel'])) return;
    
    $this->people[] = $data['results']['bindings'];
  }

  private function writeJson()
  {
    $arr = []; 
    $arr = [
      'results' => [
        'bindings' => $this->people
      ]
    ];
    $newJ = file_put_contents("json/large.json",json_encode($arr,JSON_PRETTY_PRINT));
  }
}

(new CreateLarge());