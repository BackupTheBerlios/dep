<?php

if (strcmp($group,'verwalter')<>0) 
{
	include('error_access'.$cfgEndung);
	return;
}

/**
 * Handle Workflow Usergrants
 */
if ($_REQUEST['actionUser']) {
	$_userOk = $_REQUEST['userOk'];
	$_userNo = $_REQUEST['userNo'];
	if (is_array($_userOk)) foreach ($_userOk as $_key => $_val)
	{
		// echo "freischalten $_key => $_val <br>";
		$_user = $sql->query_first("SELECT tmppass, tmpdel FROM user WHERE zugang=".convstr($_key)." LIMIT 1");
		// var_dump($_user);
		if ($_user['tmpdel']) {
		    // echo "delete";
			$sql->query("DELETE FROM user WHERE zugang=".convstr($_key)." LIMIT 1");
			$sql->query("DELETE FROM gruppe WHERE zugang=".convstr($_key)."");
			$sql->query("DELETE FROM rechte WHERE zugang=".convstr($_key)." LIMIT 1");
		}else{
		    // echo "freischalten";
			$sql->query("UPDATE user SET passwort=tmppass, tmppass='', tmpdel=0 WHERE zugang=".convstr($_key)." LIMIT 1");
		}
	}
	if (is_array($_userNo)) foreach ($_userNo as $_key => $_val)
	{
		// echo "löschen $_key => $_val <br>";
		$_user = $sql->query_first("SELECT tmppass, tmpdel FROM user WHERE zugang=".convstr($_key)." LIMIT 1");
		// var_dump($_user);
		if ($_user['tmpdel']) {
		    // echo "delete";
			$sql->query("UPDATE user SET tmpdel=0 WHERE zugang=".convstr($_key)." LIMIT 1");
		}else{
		    // echo "freischalten";
			$sql->query("DELETE FROM user WHERE zugang=".convstr($_key)." LIMIT 1");
			$sql->query("DELETE FROM gruppe WHERE zugang=".convstr($_key)."");
			$sql->query("DELETE FROM rechte WHERE zugang=".convstr($_key)." LIMIT 1");
		}
	}
}


/**
 * Handle Workflow Filegrants
 */
if ($_REQUEST['actionFile']) {
	$_fileOk = $_REQUEST['fileOk'];
	$_fileNo = $_REQUEST['fileNo'];
	if (is_array($_fileOk)) foreach ($_fileOk as $_key => $_val)
	{
		// echo "freischalten $_key => $_val <br>";
		$_recht = $sql->query_first("SELECT r.loeschen, r.tmpverfall AS tmpverfall, f.id AS f_id, f.verfallsdatum<r.tmpverfall AS file_datum_updaten FROM rechte r LEFT JOIN files f ON r.fileid=f.id WHERE r.id=".intval($_key)." LIMIT 1");
		// var_dump($_recht);

		if ($_recht['loeschen']) {
		    // echo "delete";
			$sql->query("DELETE FROM rechte WHERE id=".intval($_key)." LIMIT 1");
		}else{
		    // echo "freischalten";
			$sql->query("UPDATE rechte SET verfallsdatum=tmpverfall, tmpverfall='0000-00-00', freischalten=0 WHERE id=".intval($_key)." LIMIT 1");
			if ($_recht['file_datum_updaten']) {
				echo "<br>";
				$sql->query("UPDATE files SET verfallsdatum=".convstr($_recht['tmpverfall'])." WHERE id=".intval($_recht['f_id'])." LIMIT 1");
			}
		}
	}
	if (is_array($_fileNo)) foreach ($_fileNo as $_key => $_val)
	{
		// echo "löschen $_key => $_val <br>";
		$_recht = $sql->query_first("SELECT * FROM rechte WHERE id=".intval($_key)." LIMIT 1");
		// var_dump($_recht);

		if ($_recht['loeschen']) {
		    // echo "delete";
			$sql->query("UPDATE rechte SET loeschen=0 WHERE id=".intval($_key)." LIMIT 1");
		}else{
		    // echo "freischalten";
			$sql->query("DELETE FROM rechte WHERE id=".intval($_key)." LIMIT 1");
		}
	}
}


/**
 * Handle File Delete
 */
function deleteFile($fileid)
{
	global $sql;
	$_file = $sql->query_first("SELECT * FROM files WHERE id=".intval($fileid)." LIMIT 1");
	echo "Datei '$_file[anzeigename]' löschen...";
	$sql->query("DELETE FROM files WHERE id=".intval($fileid)." LIMIT 1");
	$sql->query("DELETE FROM rechte WHERE fileid=".intval($fileid)."");
	if (!unlink($GLOBALS['cfgDownloaddir'].$_file['name'])) {
		echo " !!! Datei konnte im Filesystem nicht entfernt werden !!!";
	}
	echo "<br>\n";
}
if ($_REQUEST['actionDelete']) {
	if ($_REQUEST['delOld'] || $_REQUEST['delAll'])
	{
		$dateien = $sql->query_all("SELECT max(r.tmpverfall)<now() AS verfallen, f.id, f.name, f.anzeigename, f.groesse, f.datum, f.verfallsdatum, r.tmpverfall as zugriffverfaellt FROM files f LEFT JOIN rechte r ON r.fileid=f.id WHERE f.verfallsdatum<now() GROUP BY f.id");
		foreach ($dateien as $datei) {
			if ($_REQUEST['delAll'] || ($_REQUEST['delOld'] && $datei['verfallen'])) {
				deleteFile($datei['id']);
			} else {
				echo "Datei '$datei[anzeigename]' übersprungen...<br>\n";
			}
		}
	}
	else
	{
		$_delOk = $_REQUEST['delOk'];
		if (is_array($_delOk)) {
			foreach ($_delOk as $_key => $_val) {
				deleteFile($_key);
			}
		}
	}
	echo "<br><br>\n";
}


?>

Zug&auml;nge freischalten:
<form method="post" action="<?php echo $PHP_SELF; ?>">
<table width="100%" border="0" cellspacing="0" cellpadding="3">
  <tr class="tblhead">
    <th>&nbsp;</th>
    <th>Zugang</th>
    <th>&nbsp;</th>
    <th width="89%">Name</th>
    <th>&nbsp;</th>
    <th>Gruppe</th>
    <th>&nbsp;</th>
    <th>verf&auml;llt</th>
    <th>&nbsp;</th>
    <th nowrap>erstellt von</th>
  </tr>
<?php

$_zaehler = 1;
$liste = $sql->query_all("SELECT u.*, g.gruppe, eigentuemer.name AS eigentuemername FROM user u LEFT  JOIN gruppe g ON u.zugang = g.zugang LEFT  JOIN user eigentuemer ON u.eigentuemer = eigentuemer.zugang WHERE u.tmppass>'' OR u.tmpdel=1"); // WHERE eigentuemer=".convstr($user));
foreach ($liste as $_user)
{
	$_titleOk = "Benutzer freischalten";
	$_titleNo = "Benutzer löschen";
	
	$_trClass = "tbl".(($_zaehler%2)?"First":"Second");
	if ($_user['tmpdel']) {
		$_trClass.="Del";
		$_titleOk = "Benutzer löschen";
		$_titleNo = "Löschung ablehnen";
	}else if($_user['tmppass']){
		// $_trClass.="New";	
	}
?>
  <tr class="<?php echo $_trClass; ?>">
    <td nowrap><input type="image" name="userOk[<?php echo $_user['zugang']; ?>]" src="images/but_ok.gif" title="<?php echo $_titleOk; ?>"> <input type="image" name="userNo[<?php echo $_user['zugang']; ?>]" src="images/but_cancel.gif" title="<?php echo $_titleNo; ?>"></td>
    <td nowrap><?php echo $_user['zugang']; ?></td>
    <td>&nbsp;</td>
    <td nowrap><?php echo $_user['name']; ?><br><span class="small"><?php echo htmlentities($_user['adresse']); ?></span></td>
    <td>&nbsp;</td>
    <td nowrap><?php echo ucfirst($_user['gruppe']); ?></td>
    <td>&nbsp;</td>
    <td nowrap><?php echo formatDate($sql->mktime($_user['verfallsdatum'])); ?></td>
    <td>&nbsp;</td>
    <td nowrap><?php echo $_user['eigentuemername']; ?></td>
  </tr>
<?php
	$_zaehler++;
}

?>
<input type="hidden" name="actionUser" value="1">
<input type="hidden" name="area" value="<?php echo $area; ?>">
</table>
</form>


<br>
Dateien freischalten:
<form method="post" action="<?php echo $PHP_SELF; ?>">
<table width="100%" border="0" cellspacing="0" cellpadding="3">
  <tr class="tblhead">
    <th>&nbsp;</th>
    <th width="97%">Name</th>
    <th width="1%">Gr&ouml;&szlig;e</th>        
    <th>&nbsp;</th>
    <th width="1%">Datum</th>
    <th>&nbsp;</th>
    <th width="1%">verf&auml;llt</th>
  </tr>
<?php

$_zaehler = 1;
$dateien = $sql->query_all("SELECT r.id AS r_id, r.verfallsdatum AS r_verfallsdatum, r.freischalten AS r_freischalten, r.loeschen AS r_loeschen, r.tmpverfall AS r_tmpverfall, f.*, u.name AS lesername, eigentuemer.name AS eigentuemername FROM rechte r LEFT JOIN files f ON f.id=r.fileid LEFT JOIN user u ON r.zugang=u.zugang LEFT JOIN user eigentuemer ON f.eigentuemer=eigentuemer.zugang WHERE r.freischalten=1 OR r.loeschen=1"); // WHERE eigentuemer=".convstr($user));
foreach ($dateien as $datei)
{
	$_titleOk = "Änderung Zugriffsdauer akzeptieren";
	$_titleNo = "Änderung Zugriffsdauer ablehnen";
	$_trClass = "tbl".(($_zaehler%2)?"First":"Second");
	if ($datei['r_loeschen']) {
		$_trClass.="Del";
		$_titleOk = "Zugriffsrecht entziehen";
		$_titleNo = "Zugriffsrecht belassen";
	}else if($datei['r_verfallsdatum']=='0000-00-00 00:00:00'){
		$_trClass.="New";	
		$_titleOk = "Neuen Zugang akzeptieren";
		$_titleNo = "Neuen Zugang ablehnen";
	}
?>
  <tr class="<?php echo $_trClass; ?>">
    <td nowrap><input type="image" name="fileOk[<?php echo $datei['r_id']; ?>]" src="images/but_ok.gif" title="<?php echo $_titleOk; ?>"> <input type="image" name="fileNo[<?php echo $datei['r_id']; ?>]" src="images/but_cancel.gif" title="<?php echo $_titleNo; ?>"></td>
    <td><a href="?area=<?php echo $area; ?>&getFile=<?php echo $datei['name']; ?>" title="<?php echo htmlentities($datei['beschreibung']); ?>"><?php echo $datei['anzeigename']; ?></a></td>
    <td align="right" nowrap><span title="<?php echo _formatDateiGroesse($datei['groesse']); ?> Bytes"><?php echo formatDateiGroesse($datei['groesse']); ?></span></td>
    <td>&nbsp;</td>
    <td nowrap><?php echo formatDate($sql->mktime($datei['datum'])); ?></td>
    <td>&nbsp;</td>
    <td nowrap><?php echo formatDate($sql->mktime($datei['verfallsdatum'])); ?></td>
  </tr>
  <tr class="<?php echo $_trClass; ?>">
    <td nowrap>&nbsp;</td>
    <td colspan="6" class="small">Verwalter <b><?php echo $datei['eigentuemername'] ?></b> setzt f&uuml;r den  Benutzer <b><?php echo $datei['lesername'] ?></b> das Zugriffsrecht von <?php echo formatDate($sql->mktime($datei['r_verfallsdatum'])); ?> auf <?php echo formatDate($sql->mktime($datei['r_tmpverfall'])); ?></td>
  </tr>
<?php
	$_zaehler++;
}

?>
<input type="hidden" name="actionFile" value="1">
<input type="hidden" name="area" value="<?php echo $area; ?>">
</table>
</form>



<br>
abgelaufene Dateien:
<form method="post" action="<?php echo $PHP_SELF; ?>">
<table width="100%" border="0" cellspacing="0" cellpadding="3">
  <tr class="tblhead">
    <th>&nbsp;</th>
    <th width="97%">Name</th>
    <th width="1%">Gr&ouml;&szlig;e</th>        
    <th>&nbsp;</th>
    <th width="1%">Datum</th>
    <th>&nbsp;</th>
    <th width="1%" nowrap>verfallen am</th>
  </tr>
<?php

$_zaehler = 1;
$dateien = $sql->query_all("SELECT max(r.tmpverfall)<now() AS verfallen, f.id, f.name, f.anzeigename, f.groesse, f.datum, f.verfallsdatum, r.tmpverfall as zugriffverfaellt FROM files f LEFT JOIN rechte r ON r.fileid=f.id WHERE f.verfallsdatum<now() GROUP BY f.id");
foreach ($dateien as $datei)
{
	$_titleOk = "Datei löschen";
	$_titleNo = "Datei erhalten";
	$_trClass = "tbl".(($_zaehler%2)?"First":"Second");
	if (!$datei['verfallen']) {
		$_trClass.="Del";
	}
?>
  <tr class="<?php echo $_trClass; ?>">
    <td nowrap><input type="image" name="delOk[<?php echo $datei['id']; ?>]" src="images/but_ok.gif" title="<?php echo $_titleOk; ?>"> <input type="image" name="delNo[<?php echo $datei['id']; ?>]" src="images/but_cancel.gif" title="<?php echo $_titleNo; ?>"></td>
    <td><a href="?area=<?php echo $area; ?>&getFile=<?php echo $datei['name']; ?>" title="<?php echo htmlentities($datei['beschreibung']); ?>"><?php echo $datei['anzeigename']; ?></a></td>
    <td align="right" nowrap><span title="<?php echo _formatDateiGroesse($datei['groesse']); ?> Bytes"><?php echo formatDateiGroesse($datei['groesse']); ?></span></td>
    <td>&nbsp;</td>
    <td nowrap><?php echo formatDate($sql->mktime($datei['datum'])); ?></td>
    <td>&nbsp;</td>
    <td nowrap><?php echo formatDate($sql->mktime($datei['verfallsdatum'])); ?></td>
  </tr>
<?php
	$_zaehler++;
}

?>
  <tr>
    <td colspan="7"><input type="submit" name="delOld" value="nicht mehr verwendete Dateien l&ouml;schen" title="l&ouml;scht nur Dateien, auf die kein Workflow angelegt wurde"> <input type="submit" name="delAll" value="alle abgelaufenden Dateien l&ouml;schen" title="l&ouml;scht alle angezeigten Dateien"></td>
  </tr>
</table>
<input type="hidden" name="actionDelete" value="1">
<input type="hidden" name="area" value="<?php echo $area; ?>">
</form>
