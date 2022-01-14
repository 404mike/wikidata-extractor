<?php

$json = file_get_contents('query.json');
$data = json_decode($json,true);
foreach($data as $k => $v)
{
  print_r($v);
  die();
}