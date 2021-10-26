<?php

$json = file_get_contents('emerson.json');
$handle = fopen("emerson.json", "r");
if ($handle) {
  while (($line = fgets($handle)) !== false) {
    $data = json_decode($line,true);
    // print_r($data);
    unset($data['options']);
    echo json_encode($data) . "\n";
  }
  fclose($handle);
}