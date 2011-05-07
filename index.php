<?php
ini_set("display_errors", 1);
error_reporting(E_ALL ^ E_NOTICE);
session_start();

### AUTO CONFIG ##############################
$sSiteRoot = dirname(__FILE__)."/";
##############################################

##############################################
include($sSiteRoot."app/config/config.php");

// Set timezone
putenv("TZ=".$aConfig["options"]["timezone"]);
date_default_timezone_set($aConfig["options"]["timezone"]);

ini_set("include_path", ini_get("include_path").":".$sSiteRoot."app/views/");

### URL VARIABLES ############################
// Remove _GET parameters from url
$sURL = array_shift(explode("?", $_SERVER["REQUEST_URI"]));
##############################################

### URL VARIABLES ############################
// Remove _GET parameters from url
$sURL = array_shift(explode("?", $_SERVER["REQUEST_URI"]));

// Force ending slash
if(substr($sURL, -1) != "/" && substr($sURL,-4,1) != "." && substr($sURL,-3,1) != ".")
{
	// Save _GET parameters
	if(!empty($_SERVER["QUERY_STRING"]))
		$sQueryString .= "?".$_SERVER["QUERY_STRING"];
	
	// Permanently redirect page
	header("HTTP/1.1 301 Moved Permanently");
	header("Location: ".$sURL."/".$sQueryString);
	exit;
}

// Break URL into peices
$aURL = explode("/", $sURL);
array_shift($aURL); // Remove first array item, always empty
array_pop($aURL); // Remove last array item, always empty

$sController = strtolower(preg_replace("/([^a-z0-9_-]+)/i", "", $aURL[0]));
$sAction = strtolower(preg_replace("/([^a-z0-9_-]+)/i", "", $aURL[1]));

if(empty($sController)) {
	$sController = "app";
}

if(empty($sAction)) {
	$sAction = "index";
}
##############################################

### PREPARE URL PATTERN ######################
require($sSiteRoot."app/config/routes.php");

// Split patterns into chunks to not choke the server
$aPatternGroups = array_chunk($aURLPatterns, 80, TRUE);

// Run just created pattern chunks
foreach($aPatternGroups as $aGroupChunk) {
	$aPatterns = array();
	$aKeys = array();
	
	/* Prepare patterns for matching */
	$i = 0;
	foreach($aGroupChunk as $sIndex => $sValue) {
		$aKeys[$i] = $sIndex;
		$sIndex = preg_replace("/<([a-z]+):(.+?)>/i", "($2)", $sIndex);
		$aPatterns[] = "(?P<url".$i.">^".$sIndex."$)";
		$i++;
	}

	/* Run pattern chunk */
	preg_match("/".str_replace("/","\/",implode("|",$aPatterns))."/i", $sURL, $aMatches);

	/* See if one of the patterns stuck */
	foreach(array_reverse($aMatches) as $sIndex => $sValue) {
		if(!is_numeric($sIndex) && !empty($sValue)) {
			// Pattern is found
			$sPattern = str_replace("url",null,$sIndex);
			$sPattern = $aKeys[$sPattern];
			break;
		}
	}
	
	// If pattern is found, don't try anymore chunks
	if(!empty($sPattern)) {
		break;
	}
}
##############################################

### DATABASE #################################
if($aConfig["database"]["connect"] == true) {
	include($sSiteRoot."app/core/database.php");
	$oDatabase = new db(
		$aConfig["database"]["username"],
		$aConfig["database"]["password"],
		$aConfig["database"]["database"],
		$aConfig["database"]["host"]
	);
} else {
	$oDatabase = null;
}
##############################################

require($sSiteRoot."app/core/controller.php");

if(count($aURLPatterns[$sPattern]) > 0 && is_file($sSiteRoot."app/controllers/".$aURLPatterns[$sPattern]["controller"].".php")) {
	$aURLPattern = $aURLPatterns[$sPattern];
	$sController = $aURLPattern["controller"];
	$sAction = $aURLPattern["action"];
	
	include($sSiteRoot."app/controllers/".$sController.".php");
	
	if(class_exists($sController)) {
		if(method_exists($sController, $sAction)) {
			// Pull dynamic variables from url
			$sPatternRGXP = preg_replace("/<([a-z]+):(.+?)>/i", "(?P<$1>$2)", $sPattern);
			preg_match("/".str_replace("/", "\/", $sPatternRGXP)."/i", $sURL, $aParamMatches);
			
			// Put dynamic variables into usable array
			$urlParams = array();
			foreach($aParamMatches as $sKey => $sValue) {
				if(!is_numeric($sKey) && !empty($sValue)) {
					$urlParams[$sKey] = $sValue;
				}
			}
			
			if(is_array($aURLPattern["param"])) {
				// Combine dynamic and manual url variables to be loaded into the Controller
				$aURLVars = array_merge($urlParams, $aURLPattern["param"]);
			} else {
				$aURLVars = $urlParams;
			}
			
			$oController = new $sController($sController);
			$oController->$sAction();
		} else {
			$oApp = new Controller;
			$oApp->loadView("error/404.php");
		}
	} else {
		$oApp = new Controller;
		$oApp->loadView("error/404.php");
	}
} elseif(is_file($sSiteRoot."app/controllers/".$sController.".php")) {
	include($sSiteRoot."app/controllers/".$sController.".php");
	
	if(class_exists($sController)) {
		if(method_exists($sController, $sAction)) {
			$oController = new $sController($sController, $aURL);
			$oController->$sAction($aURL);
		} else {
			$oApp = new Controller;
			$oApp->error("404");
		}
	} else {
		$oApp = new Controller;
		$oApp->error("404");
	}
} else {
	$oApp = new Controller;
	$oApp->error("404");
}