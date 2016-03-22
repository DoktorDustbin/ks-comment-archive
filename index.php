
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Mediocre Monster Kickstarter Comments</title>
  <link rel="stylesheet" type="text/css" href="ksstyles.css">
  <style type="text/css">
.mb2 {
  margin-bottom: 10px;
}
.py2 {
  padding-top: 5px;
  padding-bottom: 5px;
}
  </style>
</head>
<body>
<div class="col col-8">
<div class="pl2">
<h3>Mediocre Monster Kickstarter Comments</h3>
<?php

class MyDB extends SQLite3 {
    function __construct() { $this->open('/var/db/mm/mmcomments.sq3'); }
}
$db = new MyDB();

$results = $db->query("SELECT stamp FROM last");
$row = $results->fetchArray();
echo "<div>Last updated ";
echo strftime('%F @ %R UTC', $row[0]);
echo "</div>";

?>
<!-- / hide comments on backer-only updates -->
<ol class="list-comments click">
<li class="page">
<ol class="comments">

<?php

$results = $db->query("SELECT content,avatar,stamp FROM comments ORDER BY id DESC");
while ($row = $results->fetchArray()) {
  $comment = $row['content'];
  $comment = str_replace('##STAMP##', strftime('%F @ %R UTC', $row['stamp']), $comment);
  $comment = str_replace('##AVATAR##', "<img src=\"${row['avatar']}\" width=\"80\" height=\"80\" class=\"circle\">", $comment);
  echo $comment;
  echo "\n";
}

?>

</ol>
</li>
</ol>

<div style="height:20px;">&nbsp;</div>
</div>
</div>
</body>
</html>
