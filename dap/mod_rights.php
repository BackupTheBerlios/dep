<?php

/**
 * Plattform für Dokumenaustausch
 *
 * Rechte von User auf Files
 */

// Parameter holen
$actionNewGrant      = $_REQUEST['actionNewGrant'];
$actionEditGrant     = $_REQUEST['actionEditGrant'];
$actionFileChange    = $_REQUEST['actionFileChange'];
$formChangeFile      = $_REQUEST['formChangeFile'];
$formUserName        = $_REQUEST['formUserName'];
$formFileName        = $_REQUEST['formFileName'];
$formFileDescription = $_REQUEST['formFileDescription'];
$formFileValidTill   = $_REQUEST['formFileValidTill'];
$formGrantDel        = $_REQUEST['formGrantDel'];

/**
 * Neues Zugriffsrecht auf File erstellen
 */
if ($actionNewGrant)
{
	// echo "<b>actionNewGrant</b> formUserName: $formUserName, formFileName: $formFileName<br>";
	$_rightExists = $sql->query_first("SELECT 1 FROM rechte WHERE zugang=".convstr($formUserName)." AND fileid=".intval($formFileName)." LIMIT 1");
	if (!$_rightExists)
		$sql->query("INSERT INTO rechte (zugang, fileid, tmpverfall, freischalten) VALUES (".convstr($formUserName).", ".intval($formFileName).", now() + INTERVAL 2 DAY, 1)");
	$formChangeFile = $formFileName;
}

/**
 * Zugriffsrecht auf File bearbeiten
 */
if (formSubmittedValue("actionEditGrant"))
{
	// var_dump($_REQUEST); echo "<br><bR><b>actionEditGrant</b>(".formSubmittedValue("actionEditGrant").") formUserName: $formUserName, formFileName: $formFileName, formFileValidTill $formFileValidTill ".getDateTimeForm("formFileValidTill").", formGrantDel $formGrantDel<br>";
	if ($formFileValidTill) $sql->query("UPDATE rechte SET tmpverfall=".convstr(getDateTimeForm("formFileValidTill")).", freischalten=1 WHERE id=".intval(formSubmittedValue("actionEditGrant")));
	$sql->query("UPDATE rechte SET loeschen=".intval($formGrantDel)." WHERE id=".intval(formSubmittedValue("actionEditGrant")));
	// $formChangeFile = $formFileName;
}

/**
 * Handle changefile
 */
if ($actionFileChange && $formChangeFile)
{
	$sql->query("UPDATE files SET anzeigename=".convstr($formFileName).", beschreibung=".convstr($formFileDescription).", verfallsdatum=".convstr(getDateTimeForm("formFileValidTill"))." WHERE id=".intval($formChangeFile)." AND eigentuemer=".convstr($user));
}


function makePersonenArray($array)
{
	$_tmpArray = array();
	$_lastOwner = $array[0]['displayorder'];
	foreach ($array as $item)
	{
		if ($_lastOwner <> $item['displayorder']) {
		    $_lastOwner = $item['displayorder'];
			$_tmpArray[] = "";
		}
		if ($item['achtung']) $item['name'] = '['.$item['name'].']';
		$_tmpArray[] = $item;
	}
	return $_tmpArray;
}

?>

Rechteverwaltung:<br>
<form method="post" action="<?php echo $PHP_SELF; ?>">
<input type="hidden" name="area" value="<?php echo $area; ?>">
<table width="100%" border="0" cellspacing="0" cellpadding="3">
  <tr class="tblhead">
    <th>neues Recht erstellen</th>
  </tr>
  <tr>
    <td><select name="formUserName" size="1"><?php 
	echo make_option_2dim(
		makePersonenArray($sql->query_all("SELECT zugang, name, (tmppass>'' OR tmpdel>0) AS achtung, eigentuemer=".convstr($user)." AS displayorder FROM user ORDER  BY displayorder DESC, name ASC")),
		"zugang","name"); 
?></select>
	  <select name="formFileName" size="1"><?php 
	echo make_option_2dim(
		$sql->query_all("SELECT id,anzeigename FROM files WHERE eigentuemer=".convstr($user)." ORDER BY anzeigename,datum,id"),
		"id","anzeigename"); 
?></select>
	  <input type="submit" name="actionNewGrant" value="Erstellen"></td>
  </tr>
</table>
</form>  


<br>
bestehende Zugriffsrechte:
<table width="100%" border="0" cellspacing="0" cellpadding="3">
  <tr class="tblhead">
    <th width="1%">&nbsp;</th>
    <th width="94%">Name</th>
    <th width="1%">Gr&ouml;&szlig;e</th>        
    <th>&nbsp;</th>
    <th width="1%">Datum</th>
    <th>&nbsp;</th>
    <th width="1%">verf&auml;llt</th>
  </tr>
<?php

$_zaehler = 1;
$dateien = $sql->query_all("SELECT *, verfallsdatum<now() AS verfallen FROM files WHERE eigentuemer=".convstr($user)." ORDER BY anzeigename,datum,id");
foreach ($dateien as $datei)
{
	$_trClass = "tbl".(($_zaehler%2)?"First":"Second");
	if ($datei['verfallen']) $_trClass.='Del';
	if (formSubmittedValue('actionChange')==$datei['id'] || ($formChangeFile==$datei['id'] && !formSubmittedValue('actionChange')))
	{
?>
  <form method="post" action="<?php echo $PHP_SELF; ?>">
  <input type="hidden" name="area" value="<?php echo $area; ?>">
  <input type="hidden" name="formChangeFile" value="<?php echo $datei['id']; ?>">
  <tr class="<?php echo $_trClass; ?>" valign="top">
    <td align="center"><a href="?area=<?php echo $area; ?>&actionChange_<?php echo $datei['id']; ?>=t"><img src="images/aufklappen.gif" title="aufklappen" border="0"></a></td>
    <td><input style="width:99%" type="text" name="formFileName" title="Name" value="<?php echo $datei['anzeigename']; ?>"><br>
      <input style="width:99%" type="text" name="formFileDescription" title="Beschreibung" value="<?php echo $datei['beschreibung']; ?>"><br>
      <input type="submit" name="actionFileChange" value="&Auml;nderungen &uuml;bernehmen"></td>
    <td align="right" nowrap><span title="<?php echo _formatDateiGroesse($datei['groesse']); ?> Bytes"><?php echo formatDateiGroesse($datei['groesse']); ?></span></td>
    <td>&nbsp;</td>
    <td align="right" nowrap><?php echo formatDate($sql->mktime($datei['datum'])); ?></td>
    <td>&nbsp;</td>
    <td align="right" left><?php echo makeDateTimeForm("formFileValidTill", formatDate($sql->mktime($datei['verfallsdatum']))); ?></td>
  </tr>
  </form>
<?php 
		// Rechte am File anzeigen
		$_tmpRechte = $sql->query_all("SELECT r.*, u.name as username FROM rechte r LEFT JOIN user u ON r.zugang=u.zugang WHERE r.fileid=".intval($datei['id'])." ORDER BY r.verfallsdatum");
		// var_dump($_tmpRechte);
		if (is_array($_tmpRechte) && count($_tmpRechte)>0)
		{
?>
  <tr>
    <td>&nbsp;</td>
    <td colspan="4" style="border: 1px solid <?php echo $areas[$area]['color']; ?>;">
      <table width="100%" border="0" cellspacing="0" cellpadding="3">
        <tr style="background-color: <?php echo $areas[$area]['color']; ?>;">
          <th class="small">&nbsp;</th>
          <th class="small" width="92%">Name</th>
          <th class="small">&nbsp;</th>
          <th class="small" nowrap>verf&auml;llt am</th>
          <th class="small">&nbsp;</th>
          <th class="small" nowrap>Neuer Verfall</th>
          <th class="small">&nbsp;</th>
          <th class="small">L&ouml;schen</th>
        </tr>
<?php
			foreach($_tmpRechte as $_zeile => $_tmpRecht)
			{
				$_trClassRight = "tbl".((($_zeile+1)%2)?"First":"Second");
				if ($_tmpRecht['loeschen']) {
					$_trClassRight.="Del";
				}else if($_tmpRecht['freischalten']){
					$_trClassRight.="New";	
				}				
?>
        <form method="post" action="<?php echo $PHP_SELF; ?>">
        <input type="hidden" name="area" value="<?php echo $area; ?>">
        <input type="hidden" name="formFileName" value="<?php echo $datei['id']; ?>">
        <input type="hidden" name="formUserName" value="<?php echo $_tmpRecht['zugang']; ?>" />
        <tr class="<?php echo $_trClassRight; ?>">
          <td class="small"><input type="image" name="actionEditGrant<?php echo $_tmpRecht['id']; ?>" title="aktualisieren" src="images/but_enter.gif"></td>
          <td class="small"><?php echo $_tmpRecht['username']; ?></td>
          <td class="small">&nbsp;</td>
          <td class="small" nowrap><?php echo formatDate($sql->mktime($_tmpRecht['verfallsdatum'])); ?></td>
          <td class="small">&nbsp;</td>
          <td class="small" nowrap><input type="checkbox" name="formFileValidTill" class="small" value="<?php echo $_tmpRecht['id']; ?>"<?php echo ($formFileValidTill==$_tmpRecht['id'])?' checked="checked"':''; ?>/>&nbsp;<?php echo makeDateTimeForm("formFileValidTill", formatDate($sql->mktime($_tmpRecht['tmpverfall'])),false,"small"); ?></td>
          <td class="small">&nbsp;</td>
          <td class="small" align="center"><input type="checkbox" name="formGrantDel" class="small" value="1"<?php echo $_tmpRecht['loeschen']?' checked="checked"':''; ?>/></td>
        </tr>
        </form>
<?php
			} // alle Rechte
?>
      </table> 
    </td>
    <td colspan="2">&nbsp;</td>
  </tr>
<?php
		} // Rechte am File?
	} // ausgewählt?
	else
	{
?>
  <tr class="<?php echo $_trClass; ?>" valign="top">
    <td align="center"><a href="?area=<?php echo $area; ?>&actionChange<?php echo $datei['id']; ?>=t"><img src="images/aufklappen.gif" title="aufklappen" border="0"></a></td>
    <td><?php echo $datei['anzeigename']; ?><br><span class="small"><?php echo htmlentities($datei['beschreibung']); ?></span></td>
    <td align="right" nowrap><span title="<?php echo _formatDateiGroesse($datei['groesse']); ?> Bytes"><?php echo formatDateiGroesse($datei['groesse']); ?></span></td>
    <td>&nbsp;</td>
    <td align="right" nowrap><?php echo formatDate($sql->mktime($datei['datum'])); ?></td>
    <td>&nbsp;</td>
    <td align="right" nowrap><?php echo formatDate($sql->mktime($datei['verfallsdatum'])); ?></td>
  </tr>
<?php
	}

	$_zaehler++;
}

?>
</table>
