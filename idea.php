<?php session_start();

include('mysql_connect.php');

//array_value_recursive will extract all of the values associated with a given $key in a multidimensional array and output them into an array of values or arrays.
function array_value_recursive($key, array $arr){
    $val = array();
    array_walk_recursive($arr, function($v, $k) use($key, &$val){
        if($k == $key) array_push($val, $v);
    });
    return count($val) > 1 ? $val : array_pop($val);
}

$idea_id = $_GET['idea_id'];
if(isset($_SESSION['user_id']))
  $user_id = $_SESSION['user_id'];
else
  $user_id = null;

$idea_sql = "SELECT i.id, i.creation_date, i.date_updated, i.author_id, i.title, i.short_desc, i.detailed_desc, i.purpose, i.manager_id, i.likes, i.views, i.pic, i.has_location, i.video, u.username, u.first_name, u.last_name
            JOIN users as u on u.id = i.manager_id
            WHERE id = ?";

try{
    $conn = new PDO($dsn, $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $q = $conn->prepare($idea_sql);
    $q->execute(array($idea_id));
    $idea_data = $q->fetchAll();
} catch(PDOException $e) {
    echo 'ERROR: ' . $e->getMessage();
}

//Update the view count for the page.
$view_count = $idea_data['i.views']+1;
try{
  $add_view_sql = "INSERT INTO ideas (views) VALUES (?)";
  $q = $conn->prepare($add_view_sql);
  $q->execute(array($view_count));
} catch(PDOException $e) {
  echo 'ERROR: '. $e->getMessage();
}

//Get all location data for idea.
if($idea_data['i.has_location']){
  try{
    $sql_loc = "SELECT ci.name, co.name, r.name, s.name, l.id FROM idea_locations as il
    JOIN locations as l ON l.id = il.location_id
    JOIN cities as ci on ci.id = l.city_id
    JOIN regions as r on r.id = l.region_id
    JOIN countries as co on co.id = l.country_id
    JOIN states as s on s.id = l.state_id
    WHERE il.idea_id = ?";
  $q = $conn->prepare($sql_loc);
  $q->execute(array($idea_id);
  $idea_locs = $q->fetchAll();

  } catch(PDOException $e) {
    echo 'ERROR: ' .$e->getMessage();
  }
}

//Get all picture urls.
try{
  $sql_pics = "SELECT url FROM idea_pics WHERE idea_id = ?";
  $q = $conn->prepare($sql_pics);
  $q->execute(array($idea_id));
  $idea_pic_urls = $q->fetchAll();
} catch(PDOException $e) {
  echo 'ERROR: ' .$e->getMessage();
}

//Get all of the idea's subscriber usernames and ids.
try{
  $sql_subscribers = "SELECT is.user_id, u.username
                      FROM idea_subscribers as is
                      JOIN users as u on u.id = is.user_id
                      WHERE is.idea_id = ?";
  $q = $conn->prepare($sql_subscribers);
  $q->execute(array($idea_id));
  $idea_subscribers = $q->fetchAll();
} catch(PDOException $e) {
  echo 'ERROR: ' .$e->getMessage();
}

//Get all tags for the idea.
try {
  $sql_tags = "SELECT t.name, t.id FROM idea_tags as it
               JOIN tags as t ON t.id = it.tag_id
               WHERE it.idea_id = ?";
  $q = $conn->prepare($sql_tags);
  $q->execute(array($idea_id);
  $tags = $q->fetchAll();
  foreach($tags as $tag){
    $tag_string .= "{$tag['t.name']}, ";
    $tag_links .= "<a href='http://localhost/tag/{$tag['t.id']}'>{$tag['t.name']}</a>, ";
  }
  $tag_string = substr($tag_string,0,-2);
  $tag_links = substr($tag_links,0,-2);

} catch(PDOException $e) {
  echo 'ERROR: ' . $e->getMessage();
}
?>

<html>

<head><meta charset="utf-8"><meta name ="author" content = "Blaine"><meta name = "revised" content = "2012/12/6"><meta name = "description" content = "Idea page"><meta name = "keywords" content = "<?php echo $tag_string;?>">
        <link rel="stylesheet" type="text/css" href="css/mango.css">
        <title><?php echo $idea_data['title'];?></title>

</head>
<body>

<?php include('header.php');
   $edit_bool = ($idea_data['i.manager_id'] == $user_id) ? true: false;
?>

  <div id="idea_top" >
    <span id="like_count" > <?php echo $idea_data['i.likes'];?></span>
    <h1 id="idea_title" contenteditable="<?php echo $edit_bool;?>">
      <?php echo $idea_data['i.title'];?>
    </h1>
    <span id="idea_tag_list">
      <?php if($edit_bool)
              echo $tag_string;
            else
              echo $tag_links; ?></span>
    <p id="idea_manager"> <a href="http://localhost/user/<?php echo $idea_data['u.id']; ?>"><?php echo $idea_data['u.name']; ?></a>
    </p>
    <p id="idea_short_desc" contenteditable="<?php echo $edit_bool;?>"> <?php echo $idea_data['i.short_desc'];?></p>
    <p id="idea_location"> Location:
      <?php
      if($idea_data['i.has_location']){
        $i = 0;
        while(!empty($idea_loc[$i]['co.name'])){
          echo "<a href='http://localhost/locations/{$idea_loc[$i]['l.id']}'>";
          $idea_loc_id[] = $idea_loc[$i]['l.id'];
          if(!is_null($idea_loc[$i]['ci.name'])){
            if(!is_null($idea_loc[$i]['s.name'])){
              $idea_loc_name[] = "{$idea_loc[$i]['ci.name']}, {$idea_loc[$i]['s.name']}";
              echo $idea_loc_name[$i];
            }
            else{
              $idea_loc_name[] = "{$idea_loc[$i]['ci.name']}, {$idea_loc[$i]['co.name']}";
              echo $idea_loc_name[$i];
            }
          } elseif(!is_null($idea_loc[$i]['s.name'])){
            $idea_loc_name[] =  "{$idea_loc[$i]['s.name']}, {$idea_loc[$i]['co.name']}";
            echo $idea_loc_name[$i];
          }
          elseif(!is_null($idea_loc[$i]['r.name'])){
            $idea_loc_name[] = "{$idea_loc[$i]['r.name']}, {$idea_loc[$i]['co.name']}";
            echo $idea_loc_name[$i];
          }
          else{
            $idea_loc_name[] = $idea_loc[$i]['co.name'];
            echo $idea_loc_name[$i];
          }
          echo "</a><br/>";
          $i++;
        }
      }
      else{
        $idea_loc_name[0] = null;
        $idea_loc_id[0] = null;
        echo "<a href='http://localhost/locations/none'>Anywhere</a>";
      }
      ?>
    </p>
    <span id="tag_list"><?php echo $tag_links;?></span>
  </div>


  <div id="idea_video">
    <?php if(!is_null($idea_data['video'])){
      echo '<video width="420" height="340" controls="controls" >
              <source src="'. $idea_data["video"] . '.ogg" type="video/ogg">
              <source src="'. $idea_data["video"] . '.mp4" type="video/mp4">
              <source src="'. $idea_data["video"] . '.webm" type="video/webm">
              <object data="'. $idea_data["video"] . '.mp4" width="320" height="240">
               <embed width="420" height="340" src="' . $idea_data["video"] . '.swf">
              </object>
            </video>';
      }?>
  </div>
  <div id="idea_content" >
    <h4 contenteditable="<?php echo $edit_bool;?>"><?php echo $idea_data['i.heading_1']; ?></h4><p id="idea_description" contenteditable="<?php echo $edit_bool;?>"><?php echo $idea_data['i.detailed_desc']; ?> </p>
    <?php if(!is_null($idea_data['i.purpose']))
    {?>
    <h4 contenteditable="<?php echo $edit_bool;?>"><?php echo $idea_data['i.heading_2']; ?></h4><p id="idea_purpose" contenteditable="<?php echo $edit_bool;?>"><?php echo $idea_data['i.purpose']; ?></p>
    <?php }?>
  </div>
  <div id="idea_author_box"> <?php include('user_display_box.php?id='.$idea_data["u.id"]);
    if($edit_bool)
      echo '<form name="idea_user_edit_form" action="useredit.php" method="post"><input type="hidden" name="idea_manager_id" value="'.$idea_data["i.manager_id"].'"><input type="submit" value="Edit profile"></form>';
?> </div>
  <div id="idea_subscriber_box"> <?php include('subscriberdisplay.php?subs_idea_id='.$idea_id.'&subs_idea_title='.$idea_data['i.title']); ?> </div>

<form name="idea_edit_form" action="ideaformedit.php" method="post">
  <input type="hidden" name="title_edit">
  <input type="hidden" name="tag_edit">
  <textarea name="short_desc_edit" cols="20" rows="20" style="display:none;visibility:none"></textarea>
  <input name="heading_1_edit" type="hidden">
  <textarea name="detailed_desc_edit" cols="20" rows="20" style="display:none;visibility:none"></textarea>
  <input name="heading_2_edit" type="hidden">
  <textarea name="purpose_edit" cols="20" rows="20" style="display:none;visibility:none"></textarea>
  <input type="hidden" name="idea_edit_id" value="<?php echo $idea_id;?>">
  <input type="submit" value="Save changes">
</form>
<script>
  <!--
  var editable = document.querySelectorAll('[contenteditable=true]');

  for (var i=0, len = editable.length; i<len; i++){
    editable[i].setAttribute('data-orig',editable[i].innerHTML);

    editable[i].onblur = function(){
        if (this.innerHTML == this.getAttribute('data-orig')) {
            // no change
        }
        else {
            // change has happened, store new value
            this.setAttribute('data-orig',this.innerHTML);
            var hidden_form = document.getElementById("idea_edit_form");
            hidden_form.elements[i].value = this.innerHTML;
        }
    };
  }
  -->
</script>
