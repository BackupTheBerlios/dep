<?php

// Parameter holen
$actionUserChange = $_REQUEST['actionUserChange'];
$actionUserNew    = $_REQUEST['actionUserNew'];
$formChangeZugang = $_REQUEST['formChangeZugang'];
$formUserZugang   = $_REQUEST['formUserZugang'];
$formUserName     = $_REQUEST['formUserName'];
$formUserGruppe   = $_REQUEST['formUserGruppe'];
$formUserPass     = $_REQUEST['formUserPass'];
$formUserAddress  = $_REQUEST['formUserAddress'];
$formUserDelete   = $_REQUEST['formUserDelete'];

/**
 * Handle changeuser
 */
if ($actionUserChange && $formChangeZugang)
{
	/* var_dump($GLOBALS);
	echo "formChangeZugang: $formChangeZugang<br>";
	echo "formUserName: $formUserName<br>";
	echo "formUserGruppe: $formUserGruppe<br>";
	echo "formUserPass: $formUserPass<br>";
	echo "formUserAddress: $formUserAddress<br>";
	*/
	$sql->query("UPDATE user SET ".($formUserPass?"tmppass=".convstr($formUserPass).", ":"")." name=".convstr($formUserName).", verfallsdatum=".convstr(getDateTimeForm("formUserValidTill")).", adresse=".convstr($formUserAddress).", tmpdel=".($formUserDelete?"1":"0")." WHERE zugang=".convstr($formChangeZugang));
	$sql->query("UPDATE gruppe SET gruppe=".convstr($formUserGruppe)." WHERE zugang=".convstr($formChangeZugang));
}


/**
 * Handle newuser
 */
if ($actionUserNew && $formUserZugang)
{
	if (checkZugangsname($formUserZugang))
	{
		$sql->query("INSERT INTO user (zugang, passwort, tmppass, eigentuemer, verfallsdatum) VALUES (".convstr($formUserZugang).", ".convstr(substr(getHashcode(time().$formUserZugang),0,24)).", ".convstr($formUserZugang).", ".convstr($user).", now() + INTERVAL 2 DAY)");
		$formUserGruppe = $formUserGruppe;
		if (!$formUserGruppe || !$isVerwalter) $formUserGruppe = 'leser';
		$sql->query("INSERT INTO gruppe (zugang, gruppe) VALUES (".convstr($formUserZugang).", ".convstr($formUserGruppe).")");
		// automatisch aufklappen
		$formChangeZugang = $formUserZugang;
	} // else var_dump(checkZugangsname($userZugang));
}

function transformRequestValueInName($value)
{
	$return = "";
	$len = strlen($value);
	for ($i=0; $i<$len; $i++)
	{
		$char = substr($value, $i, 1);
		if (strpos(" abcdefghijklmnopqrstuvwxyzäöüß", strtolower($char)))
			$return.=$char;
		else
			$return.="_";
	}
	
	return $return;
}

?>

neuen Benutzer erstellen:
<form method="post" action="<?php echo $PHP_SELF; ?>">
<table width="100%" border="0" cellspacing="0" cellpadding="3">
  <tr class="tblhead">
    <th>Benutzerdaten angeben</th>
  </tr>
  <tr>
    <td><input type="text" name="formUserZugang" maxlength="25"> &nbsp; <?php echo ($isVerwalter)?generateGroupSelect('formUserGruppe'):""; ?> &nbsp; <input type="submit" name="actionUserNew" value="Neuen Benutzer erstellen"></td>
  </tr>
</table>
<input type="hidden" name="area" value="<?php echo $area; ?>">
</form>  


<br>
erstellte Benutzer:
<form method="post" action="<?php echo $PHP_SELF; ?>">
<table width="100%" border="0" cellspacing="0" cellpadding="3">
  <tr class="tblhead">
    <th>&nbsp;</th>
    <th width="1%" nowrap>Zugang</th>
    <th>&nbsp;</th>
    <th width="1%">Status</th>
    <th>&nbsp;</th>
    <th width="92%">Name</th>
    <th>&nbsp;</th>
    <th width="1%" nowrap>Zugang bis</th>
  </tr>
<?php

$_zaehler = 1;
$users = $sql->query_all("SELECT u.*, g.gruppe FROM user u LEFT JOIN gruppe g ON u.zugang=g.zugang".($isVerwalter?"":" WHERE u.eigentuemer=".convstr($user))." GROUP BY u.zugang ORDER BY g.gruppe DESC, u.zugang");
foreach ($users as $_user)
{
	$_trClass = "tbl".(($_zaehler%2)?"First":"Second");
	if ($_user['tmpdel']) {
		$_trClass.="Del";
	}else if($_user['tmppass']){
		$_trClass.="New";	
	}
	
	echo "\n\n<!-- actionChange: ".formSubmittedValue('actionChange').", zugang: ".$_user['zugang']." (".transformRequestValueInName($_user['zugang'])."), formChangeZugang $formChangeZugang -->\n\n";
	if (formSubmittedValue('actionChange')==transformRequestValueInName($_user['zugang']) || ($formChangeZugang==$_user['zugang'] && !formSubmittedValue('actionChange')))
	{
?>
  <input type="hidden" name="formChangeZugang" value="<?php echo $_user['zugang']; ?>">
  <tr class="<?php echo $_trClass; ?>">
    <td align="center"><input type="image" name="actionChange_<?php echo $_user['zugang']; ?>" src="images/zuklappen.gif"></td>
    <td><b><?php echo $_user['zugang']; ?></b></td>
    <td>&nbsp;</td>
    <td><?php 
    
    if ($isVerwalter)
    {
    	echo generateGroupSelect('formUserGruppe', $_user['gruppe']);
    }
    else
    {
    	echo ucfirst($_user['gruppe']);
    }

?></td>
    <td>&nbsp;</td>
    <td nowrap><input type="text" name="formUserName" maxlength="255" value="<?php echo $_user['name']; ?>" style="width:99%"></td>
    <td>&nbsp;</td>
    <td nowrap rowspan="2"><?php echo makeDateTimeForm("formUserValidTill", formatDate($sql->mktime($_user['verfallsdatum']))); ?></td>
  </tr>
  <tr class="<?php echo $_trClass; ?>">
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td align="right">Passwort:</td>
    <td>&nbsp;</td>
    <td colspan="1" nowrap><input type="text" name="formUserPass" maxlength="255" value="" style="width:99%"></td>
    <td>&nbsp;</td>
  </tr>
  <tr class="<?php echo $_trClass; ?>">
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td align="right">Adresse:</td>
    <td>&nbsp;</td>
    <td colspan="3" nowrap><textarea name="formUserAddress" rows="5" style="width:99%"><?php echo $_user['adresse']; ?></textarea></td>
  </tr>
  <tr class="<?php echo $_trClass; ?>">
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td align="right"><input type="checkbox" name="formUserDelete" id="formUserDelete" value="1"<?php echo ($_user['tmpdel'])?' checked="checked"':''; ?>></td>
    <td>&nbsp;</td>
    <td colspan="3" nowrap><label for="formUserDelete">Benutzer l&ouml;schen</label></td>
  </tr>
  <tr class="<?php echo $_trClass; ?>">
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td colspan="3" nowrap><input type="submit" name="actionUserChange" value="&Auml;nderungen &uuml;bernehmen"></td>
  </tr>
<?php
	}
	else
	{
?>
  <tr class="<?php echo $_trClass; ?>">
    <td align="center"><input type="image" name="actionChange<?php echo $_user['zugang']; ?>" src="images/aufklappen.gif"></td>
    <td><?php echo $_user['zugang']; ?></td>
    <td>&nbsp;</td>
    <td nowrap><?php echo ucfirst($_user['gruppe']); ?></td>
    <td>&nbsp;</td>
    <td nowrap><?php echo $_user['name']; ?></td>
    <td>&nbsp;</td>
    <td nowrap><?php echo formatDate($sql->mktime($_user['verfallsdatum'])); ?></td>
  </tr>
<?php
	}

	$_zaehler++;
}

?>
</table>
<input type="hidden" name="area" value="<?php echo $area; ?>">
</form>
