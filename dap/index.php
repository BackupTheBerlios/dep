<?php

/**
 * Plattform für Dokumenaustausch
 *
 * Ermittlung des Users und Includierung des Frontends
 */


//phpinfo();
$cfgEndung = ".php"; // pathinfo($_ENV["SCRIPT_NAME"])["extension"]
$cfgUploaddir = "upload/";
$cfgDownloaddir = "download/";
$PHP_SELF = basename($PHP_SELF);
set_magic_quotes_runtime(0);

include('functions'.$cfgEndung);
include('class.mysql'.$cfgEndung);
$sql = new Sql('localhost','root','','bmftausch');


/**
 * User und Gruppe ermitteln
 */
$user = 'verwalter';
$user = 'redakteur';
$user = 'leser';
// $user = $_SERVER["PHP_AUTH_USER"];

// DB-Abfrage
$db_user = $sql->query_first("SELECT * FROM user WHERE zugang=".convstr($user)." LIMIT 1"); // var_dump($db_user);
$db_group = $sql->query_first("SELECT gruppe FROM gruppe WHERE zugang=".convstr($user)." ORDER BY gruppe DESC LIMIT 1"); // var_dump($db_group);

// Gruppe
$group = '';
if (isset($db_group) && is_array($db_group) && isset($db_group['gruppe']))
{
	$group = $db_group['gruppe'];
}
$isVerwalter = (strcmp($group,'verwalter')==0);

/**
 * Sicht inkludieren
 */
if ($group)
{
	include ('actions'.$cfgEndung);
	include ('grp_'.$group.$cfgEndung);
}
else
{
	include ('error_login'.$cfgEndung);
}

?>