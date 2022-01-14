<?php

$json = file_get_contents("wikipedia/Q902546.json");

$data = json_decode($json,true);

print_r($data);