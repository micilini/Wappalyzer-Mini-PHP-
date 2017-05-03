<?php

class Wappalyzer{

private $jsonLocation = 'apps.json';

public
		$debug              = false,
		$curlUserAgent      = 'Mozilla/5.0 (X11; Linux x86_64; rv:15.0) Gecko/20100101 Firefox/15.0.1',
		$curlFollowLocation = true,
		$curlTimeout        = 5,
		$curlMaxRedirects   = 3;

private $allScripts = array();

private $allTags = array();

public function returnTecnologiesFromWebsite($url){

   //#1: Get HTML contents
   $htmlResults = (array)$this->curl($url);

   //#2: Open json file that contain all the applications identifiers
   $allApps = $this->openApps();
   
   //#3: Load the html contents with dom document
   $this->loadHTMLContentWithDomDocument($htmlResults);
   
   //#4: Check for tecnologies in website (HTML)
   return $this->checkForTecnologiesInHTML($allApps, $htmlResults);
   
}	
	
private function openApps(){
	
$string = file_get_contents($this->jsonLocation);
return json_decode($string, true);
	
}
		
private function curl($url){
		if ( $this->debug ) {
			echo 'cURL request: ' . $url . "\n";
		}
		$ch = curl_init($url);
		curl_setopt_array($ch, array(
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_HEADER         => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => $this->curlFollowLocation,
			CURLOPT_MAXREDIRS      => $this->curlMaxRedirects,
			CURLOPT_TIMEOUT        => $this->curlTimeout,
			CURLOPT_USERAGENT      => $this->curlUserAgent
			));
		$response = curl_exec($ch);
		if ( curl_errno($ch) !== 0 ) {
			throw new WappalyzerException('cURL error: ' . curl_error($ch));
		}
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if ( $httpCode != 200 ) {
			throw new WappalyzerException('cURL request returned HTTP code ' . $httpCode);
		}
		$result = new stdClass();
		$result->url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
		$result->host = parse_url($result->url, PHP_URL_HOST);
		$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$result->html = substr($response, $headerSize);
		$result->html = mb_check_encoding($result->html, 'UTF-8') ? $result->html : utf8_encode($result->html);
		$headers = trim(substr($response, 0, $headerSize));
		$headers = preg_split('/^\s*$/m', $headers);
		$headers = end($headers);
		$lines = array_slice(explode("\n", $headers), 1);
		foreach ( $lines as $line ) {
			if ( strpos(trim($line), ': ') !== false ) {
				list($key, $value) = explode(': ', trim($line, "\r"));
				$result->headers[strtolower($key)] = $value;
			}
		}
		return $result;
	}
	
private function loadHTMLContentWithDomDocument($htmlResults){

$doc = new DOMDocument();

libxml_use_internal_errors(true);
$doc->loadHTML(strtolower($htmlResults['html']));
libxml_use_internal_errors(false);

$nodeScripts = $doc->getElementsByTagName('script');
$allScripts = array();
$count = 0;
foreach ($nodeScripts  as $script) {
	$allScripts[$count] = array('src' => $script->getAttribute('src'), 'type' => $script->getAttribute('type'), 'content' => $script->nodeValue);
	$count++;
}

$nodeTags = $doc->getElementsByTagName('meta');
$allTags = array();
$count = 0;
foreach ($nodeTags  as $tags) {
	$allTags[$count] = array('name' => $tags->getAttribute('name'), 'content' => $tags->getAttribute('content'));
	$count++;
}

$this->allScripts = $allScripts;
$this->allTags = $allTags;

}	

private function checkForTecnologiesInHTML($allApps, $htmlResults){

$allHeaderTecnologies = array();

//Check Tecnologies
foreach ($allApps['apps'] as $key => $value){
	
	//#1: Check Headers
	
	if(isset($value['headers'])){
		
		foreach ($value['headers'] as $keyH => $valueH){
			
			$keyHeader = strtolower($keyH);
			$valueHeader = strtolower($valueH);
			
			if(isset($htmlResults['headers'][$keyHeader])){
				
				//Quando não é regex: 
				if (strpos(strtolower($htmlResults['headers'][$keyHeader]), $valueHeader) !== false) {
                $allHeaderTecnologies[$key] = array($value, 'valueInside' => $htmlResults['headers'][$keyHeader]);
                }
                
				//Quando é regex:
				
				$pieces = array($valueHeader);
				
				if (strpos($valueHeader, '\;') !== false) {
                $pieces = explode("\;", $valueHeader);
                }
				
				$valueHeader2 = str_replace('/', '\/', $pieces[0]);
				
				//(With Preg_quote)
				if (preg_match("/".preg_quote($valueHeader2, '/')."/", strtolower($htmlResults['headers'][$keyHeader]), $matches)) {
                $allHeaderTecnologies[$key] = array($value, 'valueInside' => $htmlResults['headers'][$keyHeader]);
                }
				
				//(Without Preg_quote)
				if (@preg_match("/".$valueHeader2."/", strtolower($htmlResults['headers'][$keyHeader]), $matches)) {
                $allHeaderTecnologies[$key] = array($value, 'valueInside' => $htmlResults['headers'][$keyHeader]);
                }
				
				
			}
			
		}
		
	}
	
	//#2: Check HTML
	
	if(isset($value['html'])){
		
		if(is_array($value['html'])){

		foreach ($value['html'] as $valueH){
			
			    $value2['html'] = strtolower($valueH);
				
				$value2['html'] = str_replace('\/', '/', $value2['html']);
				$value2['html'] = str_replace('/', '\/', $value2['html']);
                
				//Quando é regex:
				
				$pieces = array($value2['html']);
				
				if (strpos($value2['html'], '\;') !== false) {
                $pieces = explode("\;", $value2['html']);
                }
				
				$value2['html'] = $pieces[0];
				
				//Quando não é regex: 
				if (strpos(strtolower($htmlResults['html']), $value2['html']) !== false) {
                $allHeaderTecnologies[$key] = array($value, 'valueInside' => $htmlResults['headers'][$keyHeader]);
                }
				
				//(With Preg_quote)
				if (preg_match("/".preg_quote($value2['html'], '/')."/", strtolower($htmlResults['html']), $matches)) {
                $allHeaderTecnologies[$key] = array($value, 'valueInside' => $matches[0]);
                }
				
				//(Without Preg_quote)
				if (@preg_match("/".$value2['html']."/", strtolower($htmlResults['html']), $matches)) {
                $allHeaderTecnologies[$key] = array($value, 'valueInside' => $matches[0]);
                }
			
		}
		
		}else{
				
				$value2['html'] = strtolower($value['html']);
				
				$value2['html'] = str_replace('\/', '/', $value2['html']);
				$value2['html'] = str_replace('/', '\/', $value2['html']);
                
				//Quando é regex:
				
				$pieces = array($value2['html']);
				
				if (strpos($value2['html'], '\;') !== false) {
                $pieces = explode("\;", $value2['html']);
                }
				
				$value2['html'] = $pieces[0];
				
				//Quando não é regex: 
				if (strpos(strtolower($htmlResults['html']), $value2['html']) !== false) {
                $allHeaderTecnologies[$key] = array($value, 'valueInside' => $htmlResults['headers'][$keyHeader]);
                }
				
				//(With Preg_quote)
				if (preg_match("/".preg_quote($value2['html'], '/')."/", strtolower($htmlResults['html']), $matches)) {
                $allHeaderTecnologies[$key] = array($value, 'valueInside' => $matches[0]);
                }
				
				//(Without Preg_quote)
				if (@preg_match("/".$value2['html']."/", strtolower($htmlResults['html']), $matches)) {
                $allHeaderTecnologies[$key] = array($value, 'valueInside' => $matches[0]);
                }
				
		}
		
	}
	
	//#3: Check Scripts
	
	if(isset($value['script'])){
	
	if(is_array($value['script'])){
		
		foreach ($value['script'] as $valueH){
			
			foreach ($this->allScripts as $valueH2){
			
			    $value2['script'] = strtolower($valueH);
				
				//Delete '^' Frist string
				if($value2['script'][0] == "^"){
					$value2['script'] = substr($value2['script'], 1);
				}
				
				$value2['script'] = str_replace('\/', '/', $value2['script']);
				$value2['script'] = str_replace('/', '\/', $value2['script']);
				
		        //Quando não é regex: 
				if (strpos(strtolower($valueH2['src']), $value2['script']) !== false || strpos(strtolower($valueH2['content']), $value2['script']) !== false ) {
                $allHeaderTecnologies[$key] = array($value, 'valueInside' => '');
                }
                
				//Quando é regex:
				
				$pieces = array($value2['script']);
				
				if (strpos($value2['script'], '\;') !== false) {
                $pieces = explode("\;", $value2['script']);
                }
				
				//(With Preg_quote)
				if (preg_match("/".preg_quote($value2['script'], '/')."/", strtolower($valueH2['src']), $matches) || preg_match("/".preg_quote($value2['script'], '/')."/", strtolower($valueH2['content']), $matches)) {
                $allHeaderTecnologies[$key] = array($value, 'valueInside' => '');
                }
				
				//(Without Preg_quote)
				if (@preg_match("/".$value2['script']."/", strtolower($valueH2['src']), $matches) || @preg_match("/".$value2['script']."/", strtolower($valueH2['content']), $matches)) {
                $allHeaderTecnologies[$key] = array($value, 'valueInside' => '');
                }
			
			}
			
		}
		
	}else{
	 
	        foreach ($this->allScripts as $valueH2){
			
                $value2['script'] = strtolower($value['script']);
				
				//Delete '^' Frist string
				if($value2['script'][0] == "^"){
					$value2['script'] = substr($value2['script'], 1);
				}
				
				$value2['script'] = str_replace('\/', '/', $value2['script']);
				$value2['script'] = str_replace('/', '\/', $value2['script']);
				
				$pieces = array($value2['script']);
				
				if (strpos($value2['script'], '\;') !== false) {
                $pieces = explode("\;", $value2['script']);
                }
				
				$value2['script'] = $pieces[0];
		  
		        //Quando não é regex: 
				if (strpos(strtolower($valueH2['src']), $value2['script']) !== false || strpos(strtolower($valueH2['content']), $value2['script']) !== false) {
                $allHeaderTecnologies[$key] = array($value, 'valueInside' => '');
                }
                
				//Quando é regex:
				
				//(With Preg_quote)
				if (preg_match("/".preg_quote($value2['script'], '/')."/", strtolower($valueH2['src']), $matches) || preg_match("/".preg_quote($value2['script'], '/')."/", strtolower($valueH2['content']), $matches)) {
                $allHeaderTecnologies[$key] = array($value, 'valueInside' => '');
                }
				
				//(Without Preg_quote)
				if (@preg_match("/".$value2['script']."/", strtolower($valueH2['src']), $matches) || @preg_match("/".$value2['script']."/", strtolower($valueH2['content']), $matches)) {
                $allHeaderTecnologies[$key] = array($value, 'valueInside' => '');
                }
				
            }
		
	}
	
	}
	
	//#4: Check Metas
	
	if(isset($value['meta'])){
		
		foreach ($value['meta'] as $keyM => $valueM){
			
			$keyMeta = strtolower($keyM);
			$valueMeta = strtolower($valueM);
			
			foreach ($this->allTags as $keyTag => $valueTag){
				
				if(strtolower($valueTag['name']) == $keyMeta){
					
			    $valueMeta = str_replace('\/', '/', $valueMeta);
				$valueMeta = str_replace('/', '\/', $valueMeta);
                
				//Quando é regex:
				
				$pieces = array($valueMeta);
				
				if (strpos($valueMeta, '\;') !== false) {
                $pieces = explode("\;", $valueMeta);
                }
				
				$valueMeta2 = $pieces[0];
				
				//Quando não é regex: 
				if (strpos(strtolower($valueTag['content']), $valueMeta2) !== false) {
                $allHeaderTecnologies[$key] = array($value, 'valueInside' => '');
                }
				
				//(With Preg_quote)
				if (preg_match("/".preg_quote($valueMeta2, '/')."/", strtolower($valueTag['content']), $matches)) {
                $allHeaderTecnologies[$key] = array($value, 'valueInside' => '');
                }
				
				//(Without Preg_quote)
				if (@preg_match("/".$valueMeta2."/", strtolower($valueTag['content']), $matches)) {
                $allHeaderTecnologies[$key] = array($value, 'valueInside' => '');
                }
					
				}
				
			}
			
		}
		
	}
	
	//#5: Check Env
	
	if(isset($value['env'])){
	
	if(is_array($value['env'])){
		
		foreach ($value['env'] as $valueH){
			
			foreach ($this->allScripts as $valueH2){
			
			    $value2['env'] = strtolower($valueH);
				
				//Delete '^' and '$'
				//$value2['env'] = str_replace('^', '', $value2['env']);
				//$value2['env'] = str_replace('$', '', $value2['env']);
				
				$value2['env'] = str_replace('\/', '/', $value2['env']);
				$value2['env'] = str_replace('/', '\/', $value2['env']);
				
		        //Quando não é regex: 
				if (strpos(strtolower($valueH2['src']), $value2['env']) !== false || strpos(strtolower($valueH2['content']), $value2['env']) !== false ) {
                $allHeaderTecnologies[$key] = array($value, 'valueInside' => '');
                }
                
				//Quando é regex:
				
				$pieces = array($value2['env']);
				
				if (strpos($value2['env'], '\;') !== false) {
                $pieces = explode("\;", $value2['env']);
                }
				
				//(With Preg_quote)
				if (preg_match("/".preg_quote($value2['env'], '/')."/", strtolower($valueH2['src']), $matches) || preg_match("/".preg_quote($value2['env'], '/')."/", strtolower($valueH2['content']), $matches)) {
                $allHeaderTecnologies[$key] = array($value, 'valueInside' => '');
                }
				
				//(Without Preg_quote)
				if (@preg_match("/".$value2['env']."/", strtolower($valueH2['src']), $matches) || @preg_match("/".$value2['env']."/", strtolower($valueH2['content']), $matches)) {
                $allHeaderTecnologies[$key] = array($value, 'valueInside' => '');
                }
			
			}
			
		}
		
	}else{
	 
	        foreach ($this->allScripts as $valueH2){
			
                $value2['env'] = strtolower($value['env']);
				
				//Delete '^' and '$'
				//$value2['env'] = str_replace('^', '', $value2['env']);
				//$value2['env'] = str_replace('$', '', $value2['env']);
				
				$value2['env'] = str_replace('\/', '/', $value2['env']);
				$value2['env'] = str_replace('/', '\/', $value2['env']);
				
				$pieces = array($value2['env']);
				
				if (strpos($value2['env'], '\;') !== false) {
                $pieces = explode("\;", $value2['env']);
                }
				
				$value2['env'] = $pieces[0];
		  
		        //Quando não é regex: 
				if (strpos(strtolower($valueH2['src']), $value2['env']) !== false || strpos(strtolower($valueH2['content']), $value2['env']) !== false) {
                $allHeaderTecnologies[$key] = array($value, 'valueInside' => '');
                }
                
				//Quando é regex:
				
				//(With Preg_quote)
				if (preg_match("/".preg_quote($value2['env'], '/')."/", strtolower($valueH2['src']), $matches) || preg_match("/".preg_quote($value2['env'], '/')."/", strtolower($valueH2['content']), $matches)) {
                $allHeaderTecnologies[$key] = array($value, 'valueInside' => '');
                }
				
				//(Without Preg_quote)
				if (@preg_match("/".$value2['env']."/", strtolower($valueH2['src']), $matches) || @preg_match("/".$value2['env']."/", strtolower($valueH2['content']), $matches)) {
                $allHeaderTecnologies[$key] = array($value, 'valueInside' => '');
                }
				
            }
		
	}
	
	}
	
}

//Check Implies
foreach($allHeaderTecnologies as $key => $value){
			
		if (array_key_exists("implies", $value[0])) {
			
			if(is_array($value[0]["implies"])){
				
				foreach($value[0]["implies"] as $implies){
					
					if (!array_key_exists($implies, $allHeaderTecnologies)) {
					    $implier = $implies;
					    $allHeaderTecnologies[$implier] = array($allApps['apps'][$implier], 'valueInside' => '');
				    }
					
				}
				
			}else{
				
				if (!array_key_exists($value[0]["implies"], $allHeaderTecnologies)) {
					$implier = $value[0]["implies"];
					$allHeaderTecnologies[$implier] = array($allApps['apps'][$implier], 'valueInside' => '');
				}
				
			}
			
		}
		
}

return $allHeaderTecnologies;

}

}