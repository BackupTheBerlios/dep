<?php

class Sql
{

    // Hostm, User, ...
    var $db_host;
    var $db_user;
    var $db_pass;
    var $database;

    // Query, Ergebnis
    var $connected;
    var $result;
    var $query;
    var $query_count;

    // Fehlerhandling
    var $debug;
    var $errmsg;
    var $error;
    var $errno;
    var $errlog;


    // konstruktor
    function Sql ($host, $user, $pass, $database, $errlog="")
    {
        $this->db_host  = $host;
        $this->db_user  = $user;
        $this->db_pass  = $pass;
        $this->database = $database;

        $this->errlog      = $errlog;
        $this->connected   = false;
        $this->query_count = 0;
        $this->debug       = false;
    }

    // fehlerhandling
    function error ($message)
    {
        $this->errmsg = $message;
        $this->errno  = mysql_errno();
        $this->error  = mysql_error();
        $errorline = "$this->errmsg $this->errno $this->error";
        if ($this->errno) {
            if ($this->errlog) {
                $fp = fopen ($this->errlog, "r");
                fwrite ($fp, "$errorline\n");
                fclose ($fp);
            } else {
                echo "$errorline<br>";
            }
        }
    }

    // datenbankverbindung beenden
    function disconnect ()
    {
        if ($this->connected)
        {
            @mysql_close($this->connected);
            $this->error("close");
            $this->connected = "";
        }
    }

    function setDebug ($debug)
    {
        $this->debug=$debug;
    }
    function getDebug ()
    {
        return $this->debug;
    }

    // mit datenbank verbinden
    function connect ()
    {
        if ($this->connected)
        {
            $this->disconnect();
        }

        $this->connected = @mysql_connect($this->db_host, $this->db_user, $this->db_pass);
        $this->error("connect");
        mysql_select_db($this->database) or die ($this->database." not used");
    }

    // führt query aus
    function query ($query,$debug=false)
    {
        if (!$this->connected)
        {
            $this->connect();
        }

        if ($debug || $this->debug)
        {
            echo "\n<p><b>SQL:</b> ".htmlentities($query)."</p>\n";
        }

        $this->query  = $query;
        $this->result = @mysql_query($query);
        $this->error("query: $query");

        $this->query_count++;
        return $this->result;
    }

    function insert_id ($result="")
    {
        return mysql_insert_id();
    }
    function num_rows ($result="")
    {
        if ($result) {
            return mysql_num_rows($result);
        } elseif ($this->result) {
            return mysql_num_rows($this->result);
        } else {
            return -1;
        }
    }

    // führt query aus und gibt ersten Datensatz zurück
    function query_first ($query, $debug=false)
    {
        $result = $this->query ($query, $debug);
        $dataset = mysql_fetch_array($result, MYSQL_ASSOC);
        mysql_free_result($this->result);
        return $dataset;
    }

    // führt query aus und gibt ersten Datensatz zurück
    function query_all ($query, $debug=false)
    {
        $result = $this->query ($query, $debug);
        $back = array();
        while ($datensatz = mysql_fetch_array($result, MYSQL_ASSOC))
        {
            $back[]=$datensatz;
        }
        mysql_free_result($this->result);

        return $back;
    }

    // holt result wobei key die id ist (für LUT)
    function query_all_index ($query, $index="id")
    {
        $result = $this->query ($query);
        $back = array();
        while ($datensatz = mysql_fetch_array($this->result))
        {
            $back[$datensatz[$index]] = $datensatz;
        }
        mysql_free_result($this->result);

        return $back;
    }

    // bildet einen timestamp
    function mktime ($sql_datum)
    {
        $sql_datum = str_replace(":"," ",$sql_datum);
        $sql_datum = str_replace("-"," ",$sql_datum);

        $datum_arr = explode(" ", $sql_datum);

        $yy  = $datum_arr[0];
        $mm  = $datum_arr[1];
        $dd  = $datum_arr[2];
        $hh  = $datum_arr[3];
        $min = $datum_arr[4];
        $sec = $datum_arr[5];
		
		if (intval($yy)==0 && intval($mm)==0 && intval($dd)==0 && 
			intval($hh)==0 && intval($min)==0 && intval($sec)==0) {
		    return 0;
		}
		
        $timestamp = mktime($hh,$min,$sec,$mm,$dd,$yy);
        return $timestamp;
    }

}

?>