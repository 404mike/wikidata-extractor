<?php
ini_set("memory_limit", "-1");
set_time_limit(0);

class CreateJsonlAssetFile {

  private $jsonl = [];

  /**
   * Path of where to look for Wiki data
   */
  private $wikiDirPath = 'wikipedia';


  public function __construct()
  {
    $this->loopWikipediaFiles();

    $this->outputData();
  }

  /**
   * Loop through each of the JSON files in ./wikipedia
   * Create assets for each type of data
   */
  private function loopWikipediaFiles()
  {
    $files = glob('../'.$this->wikiDirPath.'/*.{json}', GLOB_BRACE);
    foreach($files as $file) {
      $this->readWikiPage($file);
    }
  }

  /**
   * Read in a single Wikipedia json file
   * Parse out data for each asset type
   */
  private function readWikiPage($file)
  {
    $json = file_get_contents($file);
    $data = json_decode($json,true);

    $person = $data['itemLabel'];
    $qid = str_replace('http://www.wikidata.org/entity/','',$data['wiki_id']);

    if(!isset($data['wikipedia_page']['main'])) return;

    $this->parseMainWikipediaArticle($data['wikipedia_page']['main'], $person, $qid);

    $this->parseLinkedWikipediaArctiles($data['wikipedia_page']['link_mentions'], $person, $qid);
  }

  private function parseMainWikipediaArticle($str, $person, $qid)
  {
    $pos = $this->findPersonInText($str, $person);
    
    if(empty($pos)) return;

    $this->createJsonStr($str, $pos, $qid, $person);
  }

  private function parseLinkedWikipediaArctiles($wikipages, $person, $qid)
  {
    foreach ($wikipages as $key => $value) {
      $str = $value['response'][0];
      if(strlen($str) < 40) continue;
      
      $pos = $this->findPersonInText($str, $person);

      if(empty($pos)) return;

      $this->createJsonStr($str, $pos, $qid, $person);
    }
    // die();
  }

  private function createJsonStr($str, $pos, $qid, $person)
  {
    $data = [
      'text'        => $str,
      '_input_hash' => $this->randomNumber(10),
      '_task_hash'  => $this->randomNumber(10),
      'spans'       => [],
      'meta'        => ['score' => 1],
      'options'     => [],
      '_session_id' => 'null',
      '_view_id'    => 'choice',
      'accept'      => [$qid],
      'answer'      => 'accept'
    ];

    foreach($pos as $k => $v) {
      $data['spans'][] = [
        "start"      => $v['start'],
        "end"        => $v['end'],
        "text"       => $person,
        "rank"       => 0,
        "label"      => "PER",
        "score"      => 1,
        "source"     => "en_core_web_lg",
        "input_hash" => $this->randomNumber(10)
      ];
    }
    
    $json = json_encode($data);

    $this->jsonl[] = $json;
  }

  private function findPersonInText($str, $person)
  {
    preg_match_all('/'.$person.'/', $str, $match, PREG_OFFSET_CAPTURE);

    // TODO: what about variations, such as first name only
    $res = [];

    foreach($match[0] AS $k => $m )
    {
      $index = $m[1];
      $substring = $m[0];

      $res[] = [
        'start' => $index,
        'end'   => ($index + strlen($substring))
      ];
    }

    // if(empty($res)){
    //   echo count($res);
    //   echo $str;
    //   echo "\n\n" . ($person);

    //   echo "\n";
    //   die();
    // }
    return $res;
  }
  

  private function randomNumber($length) {
    $result = '';

    for($i = 0; $i < $length; $i++) {
        $result .= mt_rand(0, 9);
    }

    return $result;
  }


  private function outputData()
  {
    foreach($this->jsonl as $k => $v){
      echo "$v\n";
    }
  }
}

(new CreateJsonlAssetFile());