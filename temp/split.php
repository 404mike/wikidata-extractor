<?php

$str = '';

$handle = fopen("data", "r");
if ($handle) {
  while (($line = fgets($handle)) !== false) {
    $str = trim($line);
  }

  fclose($handle);
}

$arr = explode(',',$str);

foreach ($arr as $k => $v) {
  $v = str_replace('\'','',$v);
  list($qid,$count) = explode(':',$v);
  
  $qid = trim($qid);
  $count = trim($count);

  $percentage = 90;
  if($count > 1) {
    $newCount = ($percentage / 100) * $count;
    echo "$qid:$newCount\n";
  }

}

$percentage = 50;
$totalWidth = 350;



echo $new_width;