<?php

  function curPageName() {
    return substr($_SERVER["SCRIPT_NAME"],strrpos($_SERVER["SCRIPT_NAME"],"/")+1);
  }
  $url = curPageName();
  if(substr($url,-4) == 'html'){
    $url = substr($url,0,-5);
    $get_string = "html_redirect=$url";
  }
  else{
    $url = substr($url,0,-4);
    $get_string = "php_redirect=$url";
  }
require_once("./include/membersite_config.php");

if(!$fgmembersite->CheckLogin())
{
    include("login.php?$get_string");
}
else{
  session_start();
  if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $name_of_user = isset($_SESSION['name_of_user'])?$_SESSION['name_of_user']:$_SESSION['username'];
  }
?>
  <div id="welcome_user">
    <p>Welcome, <a href="http://localhost/user/<?php echo $user_id;?>"><?php echo $name_of_user;?>!</a>
  </div>
 <?php}?>

