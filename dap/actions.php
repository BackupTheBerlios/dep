<?php

/**
 * Plattform für Dokumenaustausch
 *
 * Bearbeitung Actions
 */


/**
 * Handle fileupload
 */
if ($_REQUEST['getFile'])
{
	$file = $sql->query_first("SELECT * FROM files WHERE name=".convstr($_REQUEST['getFile']));
	// $attachment = (strstr($HTTP_USER_AGENT, "MSIE")) ? "" : " attachment"; // IE 5.5 fix. 
	header("Cache-control: private", true); // another fix for IE 
	header("Content-type: application/octet-stream", true); 
	header("Content-disposition: attachment; filename=".$file['anzeigename']."", true);
	header("Content-transfer-encoding: binary", true); 
	header("Content-length: ".$file['groesse'], true);
	// echo "huhu";
	readfile($cfgDownloaddir.$_REQUEST['getFile']);
	// $fd = fopen($cfgDownloaddir.$_REQUEST['getFile'],"rb");  fpassthru($fd); fclose($fd);
	die();
}

?>