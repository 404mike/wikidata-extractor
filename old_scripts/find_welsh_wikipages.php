<?php

class FindWelshWiki {

  public function __construct()
  {
    $this->getWelshWikidata();
  }

  private function getWelshWikidata()
  {
    $json = file_get_contents('wikidata_people_wales.json');
    $data = json_decode($json,true);

    foreach ($data as $key => $value) {
      $qid = str_replace('http://www.wikidata.org/entity/','',$value['item']);
      $this->copyWikiPages($qid);
    }
  }

  private function copyWikiPages($qid)
  {
    $json = file_get_contents("wikipedia/$qid.json");
    // echo $json;
    // die();
    if(!copy("wikipedia/$qid.json","wales_wiki_pages/$qid.json")) {
      die("can't move file\n");
    }
  }

}

(new FindWelshWiki());