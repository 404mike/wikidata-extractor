<?php

class CreateEntitiesAssetFile {

  private $people = [];

  /**
   * Path of where to look for Wiki data
   */
  private $wikiDirPath = 'wikipedia';

  /**
   * Name of the CSV file that gets output
   */
  private $outputFileName = '../assets/all_wikipedia.csv';

  public function __construct()
  {
    $this->loopWikipediaFiles();

    $this->writeCsv();
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
    $this->modelPeopleFromWikiData($data);
  }

  /**
   * Model data for entities.csv
   */
  private function modelPeopleFromWikiData($wikiData)
  {
    $this->people[] = [
      str_replace('http://www.wikidata.org/entity/','',$wikiData['wiki_id']),
      $wikiData['itemLabel'],
      $wikiData['itemDescription']
    ];
  }
  
  /**
   * Write data to CSV
   */
  private function writeCsv()
  {
    $list = [];

    // loop through each person
    foreach($this->people as $k => $v) {
      $list[] = $v;
    }

    $fp = fopen($this->outputFileName, 'w');

    foreach ($list as $fields) {
      fputcsv($fp, $fields);
    }

    fclose($fp);
  }
}

(new CreateEntitiesAssetFile());