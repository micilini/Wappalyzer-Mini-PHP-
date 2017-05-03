<?php

include ('Wappalyzer.php');

$wpp = new Wappalyzer();

$allTecnologies = $wpp->returnTecnologiesFromWebsite('https://duckyo.com');

var_dump($allTecnologies);