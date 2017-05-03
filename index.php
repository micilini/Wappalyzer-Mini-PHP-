<?php

include ('Wappalyzer.php');

$wpp = new Wappalyzer();

$allTecnologies = $wpp->returnTecnologiesFromWebsite('https://google.com');

var_dump($allTecnologies);
