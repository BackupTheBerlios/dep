<?php 

/**
 * Areas definieren
 */
//$areas['workflow'] = array('title'=>'Workflow',          'color'=>'#336600');
//$areas['upload']   = array('title'=>'Uploads verwalten', 'color'=>'#663300');
//$areas['user']     = array('title'=>'Leser verwalten',   'color'=>'#660033');
//$areas['rights']   = array('title'=>'Dateien verwalten', 'color'=>'#330066');
$areas['download'] = array('title'=>'Download',          'color'=>'#660066');

// Default Area aktivieren
$area = $_REQUEST['area'];
if (!isset($area) || !isset($areas[$area])) {
  $area = array_keys($areas);
  $area = $area[0];
}

// Kopf ausgeben
html_head('<style>.tblhead {background-color: '.$areas[$area]['color'].'}</style>');

?>

<table border="0" cellspacing="0" cellpadding="0">
<tr>
<td><b>&nbsp; Leser: <?php echo $db_user['name']; ?> &nbsp;</b></td>

<?php

// alle Areas
foreach($areas as $_key => $_val)
{

?>
  <td>&nbsp;</td><td style="padding:3px;background-color:<?php echo ($area==$_key)?$areas[$area]['color']:"gray"; ?>" align="center">&nbsp; <a href="?area=<?php echo $_key; ?>" class="menu"><?php echo $_val['title']; ?></a> &nbsp;</td>
<?php

} // alle Areas

?>

</tr>
</table>
<table width="100%" border="0" cellspacing="0" cellpadding="1"><tr><td style="height:3px;background-color:<?php echo $areas[$area]['color']; ?>"></td></tr></table>
<br>

<?php

// aktuelle Area anzeigen
if (isset($area) && isset($areas[$area]))
{
	include ('mod_'.$area.$cfgEndung);
}


html_foot();

?>