<?php

/**
 * Plattform für Dokumenaustausch
 *
 * Ermittlung des Users und Includierung des Frontends
 */


/**
 * convstr() wandelt  Hochkommas und Apostroph je nach serverkonfiguration
 *
 * @param $str
 * @return
 */
function convstr ($str) {
	$str = trim($str);
	if (get_magic_quotes_gpc()) {
		$str = "'".$str."'";
	} else {
		$str = "'".addslashes($str)."'";
	}
	return $str;
}


/**
 *
 */
function formatDate($timestamp)
{
	if ($timestamp==0) return "(nicht gesetzt)";
	return date ("d.m.y H:i:s", $timestamp);
}
// gibt den Wert zurück
function formSubmitted($bezeichner)
{
	return ($_REQUEST[$bezeichner] || $_REQUEST[$bezeichner.'_x']);
}

/**
 * Für Formulare
 */
function make_array ($start=1, $end=10, $format="%02d")
{
	$array = array();
	for ($i=$start; $i<=$end; $i++)
	{
		if ($format) {
			$array[] = sprintf ($format, $i);
		} else {
			$array[] = $i;
		}
	}
	return $array;
}
function make_option ($array, $vorgabe="")
{
	if (!is_array($array)) return;

	while(list(,$val) = @each($array)){
		$back.= "<option";
		if ($vorgabe && $vorgabe==$val) {
		    $back.= " selected";
		}
		$back.= ">$val</option>";
	} // while

	return $back;
}
function make_option_2dim ($array, $key, $val, $vorgabe="")
{
	if (!is_array($array)) return;

	foreach($array as $item){
		$back.= '<option value="'.$item[$key].'"';
		if ($vorgabe && $vorgabe==$item[$key]) $back.= " selected";
		$back.= '>'.htmlentities($item[$val]).'</option>';
	}

	return $back;
}
 
/**
 * Erstellt Formular für DateTime "tt.mm.yy hh:mm:ss"
 */
function makeDateTimeForm($formName, $datetime, $umbruch=true, $class="")
{
	$datetime = str_replace("."," ",$datetime);
	$datetime = str_replace(":"," ",$datetime);
	// var_dump($datetime); $datetime = explode(" ", $datetime); var_dump($datetime);
	list($dd,$mm,$yy,$hh,$ii,$ss) = explode(" ", $datetime);
	// echo "tt,mm,yy,hh,ii,ss = $tt,$mm,$yy,$hh,$ii,$ss";
	$selectStr = '<select '; if ($class) $selectStr.='class="'.$class.'" ';
	$return = $selectStr.'title="Datum" name="'.$formName.'_d">'.make_option(make_array(1,31), $dd).'</select>.';
	$return.= $selectStr.'title="Datum" name="'.$formName.'_m">'.make_option(make_array(1,12), $mm).'</select>.';
	$return.= $selectStr.'title="Datum" name="'.$formName.'_y">'.make_option(make_array(3,10), $yy).'</select>';
	if ($umbruch) {
		$return.= '<br>';
	}else{
		$return.= '&nbsp;';
	}
	$return.= $selectStr.'title="Uhrzeit" name="'.$formName.'_h">'.make_option(make_array(0,23), $hh).'</select>:';
	$return.= $selectStr.'title="Uhrzeit" name="'.$formName.'_i">'.make_option(make_array(0,59), $ii).'</select>:';
	$return.= $selectStr.'title="Uhrzeit" name="'.$formName.'_s">'.make_option(make_array(0,59), $ss).'</select>';	
	return $return;
}
/**
 * ermittelt aus Formularfeldern SQL-DateTime
 */
function getDateTimeForm($formName)
{
	$return = '20'.$_REQUEST[$formName.'_y'];
	$return.= '-'.$_REQUEST[$formName.'_m'];
	$return.= '-'.$_REQUEST[$formName.'_d'];
	$return.= ' '.$_REQUEST[$formName.'_h'];
	$return.= ':'.$_REQUEST[$formName.'_i'];
	$return.= ':'.$_REQUEST[$formName.'_s'];
	return $return;
}


/**
 * gibt eincodierten Wert (in Key) mit
 * (z.B. actionZugang123 mit $bezeichner="actionZugang" 
 *  gibt "123" zurück)
 */
function formSubmittedValue($bezeichner)
{
	if (!$bezeichner || !is_array($_REQUEST)) return false;

	$bezLen = strlen($bezeichner);
	foreach ($_REQUEST as $request => $_tmp)
	{
		if (substr($request,0,$bezLen)==$bezeichner) {
		    $value = substr($request,$bezLen);
			if (substr($value,-2)=="_x" || substr($value,-2)=="_y") 
				$value = substr($value,0,-2);
			return $value;
		}
	}

	return false;
}

function generateGroupSelect($formName, $default="")
{
	$groups = array('leser','redakteur','verwalter');
	$return = '<select name="'.$formName.'">';
	foreach($groups as $val)
	{
		$return.= '<option value="'.$val.'"'.(($val==$default)?' selected':'').'>'.ucfirst($val).'</option>';
	}
	$return.= '</select>';
	return $return;
}


/**
 *
 */
function getHashcode($filename)
{
	return md5($filename);
}

function checkZugangsname($zugang)
{
	if (!$zugang) return false;
	for ($i=0; $i<strlen($zugang); $i++)
	{
		if (strpos("  abcdefghijklmnopqrstuvwxyz0123456789-_/!§$%&/()=?,.;:+~", $char)>0) return false;
	}
	return true;
}

function _formatDateiGroesse($size, $stellen=0)
{
	return number_format($size,$stellen,",",".");
}
function formatDateiGroesse($size)
{
	if ($size<1234)
	{
		$return = _formatDateiGroesse($size)." Bytes";
	}
	elseif($size<12341234)
	{
		$return = _formatDateiGroesse($size/1024,1)." kB";
	}
	elseif($size<123412341234)
	{
		$return = _formatDateiGroesse($size/1024/1024,1)." MB";
	}
	else
	{
		$return = _formatDateiGroesse($size/1024/1024/1024,1)." GB";
	}
	
	return $return;
}


/**
 * html start / end
 */
function html_head($headinsert="")
{
?>
<html>
<head>
  <title>Plattforum für Dokumentenaustausch</title>
  <link rel="stylesheet" type="text/css" href="style.css">
<?php 
echo $headinsert;
?>
</head>

<body>
<?php
}

function html_foot()
{
?>
</body>
</html>
<?php
}


?>