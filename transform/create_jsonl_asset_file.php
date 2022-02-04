<?php
ini_set("memory_limit", "-1");
set_time_limit(0);

class CreateJsonlAssetFile {

  private $jsonl = [];
  private $peopleWithNoData = 0;
  private $unusableText = 0;
  private $usableData = 0;
  private $resultsFile = 'uk_data.json';

  /**
   * Path of where to look for Wiki data
   */
  private $wikiDirPath = 'wikipedia';


  public function __construct()
  {
    $this->loopWikipediaFiles();

    // echo "output data\n";
    // $this->outputData();

    // $this->saveExecData();
  }

  /**
   * Loop through each of the JSON files in ./wikipedia
   * Create assets for each type of data
   */
  private function loopWikipediaFiles()
  {
    echo "getting all files\n";
    // if(is_dir('../' . $this->wikiDirPath)) echo "Dir correct\n";
    // else echo "no Dir \n";

    if($handle = opendir('../' . $this->wikiDirPath)) {
      // echo "Handle?\n";
      while(false !== ($entry = readdir($handle))) {
        // echo "What $entry\n";
        $ext = strtolower(end(explode('.',$entry)));
        if($ext == 'json') {
          echo "$entry\n";
        }
      }
    }
    // $files = glob('../'.$this->wikiDirPath.'/*.{json}', GLOB_BRACE);
    // print_R($files);
    // foreach($files as $file) {
    //   echo "opening $file\n";
    //   $this->readWikiPage($file);
    // }
  }

  /**
   * Read in a single Wikipedia json file
   * Parse out data for each asset type
   */
  private function readWikiPage($file)
  {
    $json = file_get_contents($file);
    $data = json_decode($json,true);

    if(empty($data['wikipedia_page']) && empty($data['itemDescription'])) {      
      $this->peopleWithNoData++;
      return;
    }

    $person = $data['itemLabel'];
    $qid = str_replace('http://www.wikidata.org/entity/','',$data['wiki_id']);

    if(!isset($data['wikipedia_page']['main'])) return;

    $this->parseMainWikipediaArticle($data['wikipedia_page']['main'], $person, $qid);

    $this->parseLinkedWikipediaArctiles($data['wikipedia_page']['link_mentions'], $person, $qid);
  }

  private function parseMainWikipediaArticle($str, $person, $qid)
  {
    // clean string format
    $str = utf8_decode($str);

    // clean name in string
    $str = $this->cleanNameInString($str, $person);
    
    // get name position
    $pos = $this->findPersonInText($str, $person);
    
    if(empty($pos)) return;

    $this->createJsonStr($str, $pos, $qid, $person);
  }

  private function parseLinkedWikipediaArctiles($wikipages, $person, $qid)
  {
    foreach ($wikipages as $key => $value) {
      $str = $value['response'][0];
      // clean string format
      $str = utf8_decode($str);
      if(strlen($str) < 40) {
        $this->unusableText++;
        continue;
      }

      // clean name in string
      $str = $this->cleanNameInString($str, $person);
      
      // get name position
      $pos = $this->findPersonInText($str, $person);

      if(empty($pos)) {
        $this->unusableText++;
        return;
      }

      $this->createJsonStr($str, $pos, $qid, $person);

      $this->usableData++;
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
  
  /**
   * Clean name in string
   * if the name we're looking for has extra characters, add a space
   * eg: 4Bob Jones - 4 Bob Jones
   */
  private function cleanNameInString($str, $name)
  {
    // regex to get name
    preg_match_all('/(.?)' . $name . '(.?)/', $str, $output_array);

    // get start part of regex
    $beginStrChar = $output_array[1][0];
    // get end part of regex
    $endStrChar = $output_array[2][0];

    // loop all the names found in the string
    foreach($output_array[0] as $nameInstanceKey => $nameInstanceVal) {

      // reference for name
      $issue = $output_array[0][0];
      $newFormat = $issue;

      // if no space at begining or empty
      if(!ctype_space($beginStrChar) && !empty($beginStrChar)) {
        // add a space after first char
        $newFormat = substr_replace($issue, ' ', 1, 0);
      }

      // if no space at end or empty
      if(!ctype_space($endStrChar) && !empty($endStrChar)) {
        // add a space before last char
        $newFormat = substr_replace($newFormat, ' ', -1, 0);
      }

      // replace the string
      $str = str_replace($issue, $newFormat, $str);
    }

    return $str;
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
      if(!empty($v))
        echo "$v\n";
    }
  }

  private function saveExecData()
  {
    $arr = [
      'usableData' => $this->usableData,
      'peopleWithNoData' => $this->peopleWithNoData,
      'unusableText' => $this->unusableText
    ];

    file_put_contents('../assets/'. $this->resultsFile, json_encode($arr,JSON_PRETTY_PRINT));
  }
}

(new CreateJsonlAssetFile());