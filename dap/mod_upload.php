<?php

// Variablen ermitteln
$actionGetFile = $_REQUEST['actionGetFile'];
$numFiles      = $_REQUEST['numFiles'];

// erstellt einen HashFIlenamen
// Achtung: da zeitabhängig nur einmal für Datei aufrufen
function makeHashFilename($filename)
{
	global $user;
	$return = getHashcode($user.time().$filename);
	$pos = strpos($filename,".");
	if ($pos>0) {
	    $return = substr($filename,0,$pos+1) . $return . substr($filename,$pos);
	} else {
	    $return = $filename . $return;
	}	
	return $return;
}


/**
 * Handle fileupload
 */
foreach ($_FILES as $cfgUploadfile)
{
	// if (false): Datei nicht automatisch dem aktuellen Nutzer zuordnen
	if (false && move_uploaded_file($cfgUploadfile['tmp_name'], $cfgUploaddir.$cfgUploadfile['name'])) 
	{
		echo "Die Datei wurde erfolgreich hochgeladen.";
	} 
 
	// Datei aktuell angemeldeten Benutzer zuordnen
	$_datei = $cfgUploadfile['name'];
	$_hash = makeHashFilename($_datei);
	if (move_uploaded_file($cfgUploadfile['tmp_name'], $cfgDownloaddir.$_hash)) 
	{
		$sql->query("INSERT INTO files (eigentuemer, name, datum, verfallsdatum, anzeigename, groesse) VALUES (".convstr($user).", ".convstr($_hash).", now(), now() + INTERVAL 14 DAY, ".convstr($_datei).", ".$cfgUploadfile['size'].")");
		echo "Die Datei wurde erfolgreich hochgeladen.";
	} 
	elseif ($cfgUploadfile['name'])
	{
		echo "Probleme beim Dateiupload. Debugging Information:\n";
		var_dump($_FILES);
	}

}


/**
 * Handle fileown
 */
if ($actionGetFile && $numFiles)
{
	for ($_i=1; $_i<=$numFiles; $_i++)
	{
		$_datei = $_REQUEST['nd'.$_i];
		// echo $_i.' '.$_datei.' <br>';
		if ($_datei && file_exists($cfgUploaddir.$_datei))
		{
			$_hash = makeHashFilename($_datei);
			// echo 'move '.$_datei.' to '.getHashcode($user.time().$_datei).'<br>';
			$sql->query("INSERT INTO files (eigentuemer, name, datum, verfallsdatum, anzeigename, groesse) VALUES (".convstr($user).", ".convstr($_hash).", '".date("Y-m-d H:i:s", filemtime($cfgUploaddir.$_datei))."', now() + INTERVAL 14 DAY, ".convstr($_datei).", ".filesize($cfgUploaddir.$_datei).")");
			rename($cfgUploaddir.$_datei, $cfgDownloaddir.$_hash);
		}
	}
}

?>

Datei hochladen:<br>
<form enctype="multipart/form-data" method="post" action="<?php echo $PHP_SELF; ?>">
<input type="hidden" name="area" value="<?php echo $area; ?>">
<input type="hidden" name="MAX_FILE_SIZE" value="700000000">
<table width="100%" border="0" cellspacing="0" cellpadding="3">
  <tr class="tblhead">
    <th>Datei auswählen</th>
  </tr>
  <tr>
    <td><input type="file" name="userfile"> &nbsp; <input type="submit" value="Datei hochladen"></td>
  </tr>
</table>
</form>  


<br>
noch nicht zugeordnete Dateien:
<form method="post" action="<?php echo $PHP_SELF; ?>">
<table width="100%" border="0" cellspacing="0" cellpadding="3">
  <tr class="tblhead">
    <th width="1%">&nbsp;</th>
    <th width="97%">Name</th>
    <th width="1%">Gr&ouml;&szlig;e</th>        
    <th>&nbsp;</th>
    <th width="1%">Datum</th>
  </tr>
<?php

$_zaehler = 1;
$dir_entries = dir($cfgUploaddir);
// echo "Handle: ".$d->handle."<br>\n";
// echo "Path: ".$d->path."<br>\n";
while (false!==($entry=$dir_entries->read())) {
	if (is_dir($cfgUploaddir."/".$entry)) continue;
	$_trClass = "tbl".(($_zaehler%2)?"First":"Second");
?>
  <tr class="<?php echo $_trClass; ?>">
    <td align="center"><input type="checkbox" name="nd<?php echo $_zaehler; ?>" id="nd<?php echo $_zaehler; ?>" value="<?php echo $entry; ?>"></td>
    <td><label for="nd<?php echo $_zaehler; ?>"><?php echo $entry; ?></label></td>
    <td align="right" nowrap><span title="<?php $_size = filesize($cfgUploaddir."/".$entry); echo _formatDateiGroesse($_size); ?> Bytes"><?php echo formatDateiGroesse($_size); ?></span></td>
    <td>&nbsp;</td>
    <td align="right" nowrap><?php echo formatDate(filemtime($cfgUploaddir."/".$entry)); ?></td>
  </tr>
<?php
	$_zaehler++;
}
$dir_entries->close();

?>
</table>
<input type="hidden" name="area" value="<?php echo $area; ?>">
<input type="hidden" name="numFiles" value="<?php echo $_zaehler-1; ?>">
<input type="submit" name="actionGetFile" value="selektierte Dateien &uuml;bernehmen" />
</form>


<br>
meine Dateien:
<table width="100%" border="0" cellspacing="0" cellpadding="3">
  <tr class="tblhead">
    <th width="1%">&nbsp;</th>
    <th width="97%">Name</th>
    <th width="1%">Gr&ouml;&szlig;e</th>
    <th>&nbsp;</th>
    <th width="1%">Datum</th>
    <th>&nbsp;</th>
    <th width="1%">verf&auml;llt</th>
  </tr>
<?php

$_zaehler = 1;
$dateien = $sql->query_all("SELECT * FROM files WHERE eigentuemer=".convstr($user));
foreach ($dateien as $datei)
{
	$_trClass = "tbl".(($_zaehler%2)?"First":"Second");
?>
  <tr class="<?php echo $_trClass; ?>">
    <td align="center">&nbsp;</td>
    <td><a href="?area=<?php echo $area; ?>&getFile=<?php echo $datei['name']; ?>"><?php echo $datei['anzeigename']; ?></a></td>
    <td align="right" nowrap><span title="<?php echo _formatDateiGroesse($datei['groesse']); ?> Bytes"><?php echo formatDateiGroesse($datei['groesse']); ?></span></td>
    <td>&nbsp;</td>
    <td align="right" nowrap><?php echo formatDate($sql->mktime($datei['datum'])); ?></td>
    <td>&nbsp;</td>
    <td align="right" nowrap><?php echo formatDate($sql->mktime($datei['verfallsdatum'])); ?></td>
  </tr>
<?php
	$_zaehler++;
}

?>
</table>
