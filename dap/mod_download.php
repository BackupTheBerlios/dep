<?php

// File Download in index.php


?>

meine Dateien:
<table width="100%" border="0" cellspacing="0" cellpadding="3">
  <tr class="tblhead">
    <th width="93%">Name</th>
    <th width="1%">Gr&ouml;&szlig;e</th>
    <th>&nbsp;</th>
    <th width="1%">Datum</th>
    <th>&nbsp;</th>
    <th width="1%">verf&auml;llt</th>
    <th>&nbsp;</th>
    <th width="1%">verantwortlich</th>
  </tr>
<?php

$_zaehler = 1;
$dateien = $sql->query_all("SELECT f.*, r.verfallsdatum AS zugriffbis, u.name AS eigentuemername FROM files f LEFT JOIN user u ON f.eigentuemer=u.zugang LEFT JOIN rechte r ON f.id=r.fileid WHERE /* r.freischalten=0 AND */ r.verfallsdatum>now() AND u.verfallsdatum>now()".($isVerwalter?"":" AND r.zugang=".convstr($user)) . " GROUP BY f.id ORDER BY f.anzeigename"); // f.eigentuemer=".convstr($user)) 
foreach ($dateien as $datei)
{
	$_trClass = "tbl".(($_zaehler%2)?"First":"Second");
	// der Datei : echo formatDate($sql->mktime($datei['verfallsdatum'])); 
?>
  <tr class="<?php echo $_trClass; ?>" valign="top">
    <td><a target="_blank" href="?area=<?php echo $area; ?>&getFile=<?php echo $datei['name']; ?>"><?php echo $datei['anzeigename']; ?></a><br><span class="small"><?php echo htmlentities($datei['beschreibung']); ?></span></td>
    <td align="right" nowrap><span title="<?php echo _formatDateiGroesse($datei['groesse']); ?> Bytes"><?php echo formatDateiGroesse($datei['groesse']); ?></span></td>
    <td>&nbsp;</td>
    <td nowrap><?php echo formatDate($sql->mktime($datei['datum'])); ?></td>
    <td>&nbsp;</td>
    <td nowrap><?php echo formatDate($sql->mktime($datei['zugriffbis'])); ?></td>
    <td>&nbsp;</td>
    <td nowrap><?php echo $datei['eigentuemername']; ?></td>
  </tr>
<?php
	$_zaehler++;
}

?>
</table>
