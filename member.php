<?php

define("ROOT", $_SERVER['DOCUMENT_ROOT']."/");
require(ROOT."core/core.php");


switch ($_GET['act']) {


  case "register":
    $output['title'] = $lang['register'];
    if (array_key_exists("username", $_POST)) {
      if (array_key_exists("password", $_POST)) {
        if ($_SESSION['uid'] < 1) {
          // begin registration

          // if empty username
          if ($_POST['username'] == "") {
            // Invalid username with marks
            $output['alerttype'] = "alert-danger";
            $output['alert'] = $lang['blank-username'];
            break;
          }

          // check for marks in username
          $list = "!@#$%^&*{[(<>)]};'\" `~?/\\|=+";
          $list = str_split($list);
          $unamecheck = usernameCensor($_POST['username'], $list);
          if ($unamecheck) {
            // Invalid username with marks
            $output['alerttype'] = "alert-danger";
            $output['alert'] = $lang['invalid-username-1'].$unamecheck.$lang['invalid-username-3'];
            break;
          }

          // check for restricted usernames
          $r = DB::query("SELECT word FROM member_restrictname");
          $list = [];
          foreach ($r as $k => $v) {
            array_push($list, $v['word']);
          }
          $unamecheck = usernameCensor($_POST['username'], $list);
          if ($unamecheck) {
            // Invalid username with marks
            $output['alerttype'] = "alert-danger";
            $output['alert'] = $lang['invalid-username-2'].$unamecheck.$lang['invalid-username-3'];
            break;
          }

          // check for duplicate usernames
          $r = DB::query("SELECT uid FROM member WHERE username=%s", $_POST['username']);
          if (!empty($r)) {
            // Username already registered
            $output['alerttype'] = "alert-danger";
            $output['alert'] = $lang['username-dup'];
            break;
          }

          // generate password salt
          $salt = randomStr(6);

          // encrypt password
          $encryptedPassword = md5($_POST['password'].$salt);

          // register this new user
          DB::query("INSERT INTO member (username, email, password, salt, regdate) VALUES ( %s , %s , %s , %s, %s)", $_POST['username'], $_POST['email'], $encryptedPassword, $salt, time());

          // fetch the new user's uid
          $uid = DB::query("SELECT uid FROM member WHERE username=%s", $_POST['username'])[0]['uid'];

          // log this new user in
          $_SESSION['uid'] = $uid;

          // insert login history
          DB::query("INSERT INTO member_loginhistory (uid, logindate, success, ip) VALUES ( %i, %s, %s, %s)", $uid, time(), 1, $_SERVER['REMOTE_ADDR']);

          // clear failed login count
          DB::query("UPDATE member SET lastlogin= %s, failcount=0 WHERE uid= %i", time(), $uid);

          // fetch userinfo
          $member = getUserInfo();


          $output['alerttype'] = "alert-success";
          $output['alert'] = $settings['registered-welcome'];

          // redirect user to fill their profile
          redirect("member.php?act=modprofile");

        } else {
          // Already logged in, do not allow re-register
          $output['alerttype'] = "alert-success";
          $output['alert'] = $lang['logged-in'];
          $output['redirect'] = $lang['hist-back'];
          $redirect = "member.php";
          template("common_bang");
          break;
        }
      } else {
        // Some fields do not exist
        $output['alerttype'] = "alert-danger";
        $output['alert'] = $lang['empty-field'];
        break;
      }
    }
    break;







  case "login":
    $title = $lang['login'];
    if (array_key_exists("username", $_POST)) {
      if (array_key_exists("password", $_POST)) {
        if ($_SESSION['uid'] < 1) {

          // check ipfail count
          $r = DB::query("SELECT lasttrial, count FROM member_failedip WHERE ip=%s", $_SERVER['REMOTE_ADDR']);
          if (!empty($r)) {
            if ($r[0]['count']>10) {
              if (($r[0]['lasttrial']+3600*24) > time()) {
                // if temp ip ban still enforce
                $output['alerttype'] = "alert-danger";
                $output['alert'] = $lang['ip-ban-temp1'].(int)( (($r[0]['lasttrial']+3600*24) - time())/3600 ).$lang['ip-ban-temp2'];
                break;
              }
            }
          }

          // fetch userinfo
          $r = DB::query("SELECT uid, password, salt, failcount FROM member WHERE username= %s", $_POST['username']);
          if (empty($r)) {
            // User does not exist
            $output['alerttype'] = "alert-danger";
            $output['alert'] = $lang['username-dne'];
            break;
          }

          // calculate fail login penalty time
          $bantime = 10*pow(2, $r[0]['failcount']);

          // fetch last trial time
          $s = DB::query("SELECT logindate FROM member_loginhistory WHERE uid=%i ORDER BY logindate DESC", $r[0]['uid']);
          if (!empty($s)) {
            if (($s[0]['logindate']+$bantime)>time()) {
              // login failed penalty
              $output['alerttype'] = "alert-danger";
              $output['alert'] = $lang['fail-penalty1'].($s[0]['logindate']+$bantime-time())." (/".$bantime.")".$lang['fail-penalty2'];
              break;
            }
          }

          // if no penalty, check password
          $encryptedPassword = md5($_POST['password'].$r[0]['salt']);
          if ($encryptedPassword == $r[0]['password']) {
            // credentials correct
            // log this new user in
            $_SESSION['uid'] = $r[0]['uid'];
            // refresh userinfo
            $member = getUserInfo();

            // insert login history
            DB::query("INSERT INTO member_loginhistory (uid, logindate, success, ip) VALUES (%i, %s, %i, %s)", $_SESSION['uid'], time(), 1, $_SERVER['REMOTE_ADDR']);
            // clear loginfail count
            DB::query("UPDATE member SET lastlogin=%s, failcount=0 WHERE uid=%i", time(), $_SESSION['uid']);
            // clear ipfail count
            $t = DB::query("SELECT ip, lasttrial, count, attempted FROM member_failedip WHERE ip=%s", $_SERVER['REMOTE_ADDR']);
            if (empty($t)) {
              DB::query("INSERT INTO member_failedip (ip, lasttrial, count) VALUES (%s, %s, 0) ON DUPLICATE KEY UPDATE count=0, lasttrial=%s", $_SERVER['REMOTE_ADDR'], time(), time());
            }
            $output['alerttype'] = "alert-success";
            $output['alert'] = $lang['logged-in'];
            break;
          } else {
            // Incorrect credentials
            // insert login history
            DB::query("INSERT INTO member_loginhistory (uid, logindate, success, ip) VALUES (%i, %s, %i, %s)", $_SESSION['uid'], time(), 0, $_SERVER['REMOTE_ADDR']);
            // increase loginfail count
            DB::query("UPDATE member SET failcount=failcount+1 WHERE uid=%i", $_SESSION['uid']);
            // increase ipfail count
            $t = DB::query("SELECT ip, lasttrial, count, attempted FROM member_failedip WHERE ip=%s", $_SERVER['REMOTE_ADDR']);
            if (empty($t)) {
              DB::query("INSERT INTO member_failedip (ip, lasttrial, count) VALUES (%s, %s, 0) ON DUPLICATE KEY UPDATE count=0, lasttrial=%s", $_SERVER['REMOTE_ADDR'], time(), time());
            } else {
              $attempted = $t[0]['attempted'];
              if (strlen($attempted)!=0) {
                $attempted .= ", ";
              }
              $attempted .= $_POST['username'];
              DB::query("UPDATE member_failedip SET lasttrial=".time().", count=count+1, attempted=%s WHERE ip=%s", $attempted, $_SERVER['REMOTE_ADDR']);
            }
            $output['alerttype'] = "alert-danger";
            $output['alert'] = $lang['invalid-cred'];
            break;
          }
        } else {
          // Already logged in, do not allow re-register
          $output['alerttype'] = "alert-success";
          $output['alert'] = $lang['logged-in'];
          break;
        }
      } else {
        // Some fields do not exist
        $output['alerttype'] = "alert-danger";
        $output['alert'] = $lang['empty-field'];
        break;
      }
    }
    break;











  case "logout":
    $_SESSION['username'] = "";
    $_SESSION['uid'] = 0;
    $output['alerttype'] = "alert-success";
    $output['alert'] = $lang['logged-out'];
    break;





  default:
    if ($_SESSION['uid'] > 0) {
      // Fetch user info
      if (!isset($_GET['uid'])) {
        $_GET['uid'] = $_SESSION['uid'];
      }

      $userinfo = DB::query("SELECT *FROM member WHERE uid=%i", $_GET['uid'])[0];
      $userinfo['regdate'] = toUserTime($userinfo['regdate']);
      $userinfo['lastlogin'] = toUserTime($userinfo['lastlogin']);
      $output['userinfo'] = $userinfo;


      $userperm = DB::query("SELECT * FROM member_groups WHERE groupid=%i",  $output['userinfo']['groupid'])[0];
      $output['userperm'] = $userperm;




    }
}

$output['title'] = "Users";
template("member");
