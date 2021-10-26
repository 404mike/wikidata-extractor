<?php
$str = "The discovery of co-enzymes by Sir Arthur Harden FRS and his colleagues was recognised by the co-award to him of the Nobel Prize for chemistry in 1929. The institute played a major part in defining the role of vitamins in post-war nutritional deficiency diseases that were widespread in Europe and elsewhere. Emmy Klieneberger-Nobel pioneered the study of mycoplasma and in 1935 discovered and cultured unusual strains of bacteria that lacked a cell wall, naming them L-form bacteria after the institute where she worked. The first director, Sir Charles Martin, appointed in 1903, Emmy Klieneberger-Nobel retired in 1930.";

$person = "Emmy Klieneberger-Nobel";

preg_match_all('/'.$person.'/', $str, $match, PREG_OFFSET_CAPTURE);

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

print_r($res);