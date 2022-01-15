<?php
$json = file_get_contents('json/106100.json');

$data = json_decode($json,true);

$arr = []; 
$arr = [
  'results' => [
    'bindings' => $data
  ]
];
$newJ = json_encode($arr,JSON_PRETTY_PRINT);


echo $newJ;
