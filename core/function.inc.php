<?php


/**
 * @param  array
 * @param  boolean, true = return as string, false = echo
 * @return return string or echo
 */
function printv($arr, $ret=false) {
  $buf = "";
  if (is_array($arr)) {
    $buf .= "<pre>";
    ob_start();
    print_r($arr);
    // var_export($arr);
    $buf .= ob_get_clean();
    $buf .= "</pre>";
    if ($ret) {
      return $buf;
    } else {
      echo $buf;
    }
  } else {
    echo $arr;
  }
}



// render the HTML template
// **** this function WILL TERMINATE the PHP EXECUTION ****
/**
 * @param  template files
 * @return EXIT
 */
function template(...$files) {
  global $_SESSION;
  global $_GET;
  global $_POST;
  global $config;
  global $redirect;
  global $action;

  $_endtime = microtime(true);
  $_runtime = $_endtime - $GLOBALS['startTime'];
  $_runtime = sprintf('%0.5f', $_runtime);

  include_once(ROOT."templates/".$config['template']."/header.htm");
  foreach ($files as $k => $v) include_once(ROOT."templates/".$config['template']."/".$v.".htm");
  include_once(ROOT."templates/".$config['template']."/footer.htm");
  exit();
}



// redirect the user to another URL
// **** this function WILL TERMINATE the PHP EXECUTION ****
/**
 * @param  how many seconds before redirection
 * @param  URL
 * @return EXIT
 */
function redirect($sec, $url) {
  global $redirect;
  $redirect = "<meta http-equiv='refresh' content='".$sec."; URL=".$url."'>";
  template();
}



// Generate a random string
// used for password salt
/**
 * @param  length of random string
 * @return random string
 */
function randomStr($length) {
    $keyspace = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    $str = '';
    $max = strlen($keyspace) - 1;
    for ($i = 0; $i < $length; ++$i) {
        $str .= $keyspace[random_int(0, $max)];
    }
    return $str;
}



// convert timestamp to human readable time
// default time format is ISO
/**
 * @param  UNIX timestamp
 * @param  NULL if use default format; otherwise provide a format string 
 * @return human readable time in string
 */
function toUserTime($time, $format=NULL) {
  global $config;
  if (!$format) {
    // $format = $config['datetime']['iso'];
    $format = $config['datetime']['format'];
  }
  $dt = new DateTime();
  $dt->setTimestamp($time);
  $dt->setTimezone(new DateTimeZone($config['datetime']['timezone']));
  $is = $dt->format($format);
  return $is;
}



// SQL, comma separated LIKE condition constructor
// risk of SQL injection here !!!
// find better solutions !!!
function makeLikeCond($fieldname, $condition, $delim, $allowConcatenate=false) {
  $conditionString =  $fieldname." LIKE '".$condition.$delim."%' OR ".          // as first value
                $fieldname." LIKE '%".$delim.$condition.$delim."%' OR ".  // as middle value
                $fieldname." LIKE '%".$delim.$condition."' OR ".          // as last value
                $fieldname." LIKE '".$condition."'";                      // as only value
  if ($allowConcatenate) {
    $conditionString .= " OR ".$fieldname." LIKE '%".$condition."%'";                  // as part of a word, also next to a punctuation like "testing."
  }
  return $conditionString;
}


/**
 * @param  string alert text
 * @param  constant alert type (see /core/constant.php)
 * @return none. alert pushed into $GLOBALS['output']['alert']
 */
function alert($text, $type=BLUE) {
  array_push($GLOBALS['output']['alert'], [$text, $type]);
}


/**
 * @param  string error text
 * @param  HTML error code
 * @return EXIT
 */
function error($text, $htmlcode=NULL) {
  if ($htmlcode!=NULL) {
    header("HTTP/1.1 ".$htmlcode);
  }
  $GLOBALS['output']['title'] = "Error";
  alert($text, "alert-danger");
  template("error");
}


/**
 * @param  $result[] from class OR 0/1 as success
 * @param  string or array as message
 * @return EXIT with json_encode
 */
function api_write($result, $message=NULL) {
  if (is_array($result)) {
    exit(json_encode($result));
  }

  if (is_numeric($result)) {
    if ($message == NULL) $message = "";
    exit(json_encode( ["success" => $result, "message" => $message] ));
  }

  exit("ERROR: api_write param error");
}
