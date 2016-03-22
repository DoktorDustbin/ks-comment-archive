<?php
class MyDB extends SQLite3 {
    function __construct() { $this->open('/var/db/mm/mmcomments.sq3'); }
}
$db = new MyDB();

function chrome_fetch($url, $referer = '') {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  $httphead = array(
    'Pragma: no-cache',
    'Accept-Language: en-US,en;q=0.8',
    'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/48.0.2564.116 Safari/537.36',
    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
    'Cache-Control: no-cache',
    'Connection: keep-alive'
  );
  if ($referer != '') $httphead[] = 'Referer: ' . $referer;
  curl_setopt($ch, CURLOPT_HTTPHEADER, $httphead);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $resp = curl_exec($ch);
  curl_close($ch);
  return $resp;
}

  $url = 'https://www.kickstarter.com/projects/opalsquad/mediocre-monster-the-life-of-an-rpg-monster/comments';
  $url = 'http://mm.doktorjones.com/dummy.html';

  $data = chrome_fetch($url);

  $start = '<!-- / hide comments on backer-only updates -->';
  $end   = '<a class="btn btn--light-blue btn--block mt2 older_comments" href="/projects/opalsquad/mediocre-monster-the-life-of-an-rpg-monster/comments?cursor=';
  $spos  = strpos($data, $start);
  $len   = strpos($data, $end, $spos) - $spos;
  $data  = substr($data, $spos, $len);

  $match = '~<li class="NS_comments[^>]+>(.*?)</li>~s';

  preg_match_all($match, $data, $entries);

  header('Content-Type: text/plain');

  $output = array();

  foreach ($entries[0] as $entry) {
    $start = 'id="comment-';
    $end   = '"';
    $spos  = strpos($entry, $start) + strlen($start);
    $len   = strpos($entry, $end, $spos) - $spos;
    $cid   = substr($entry, $spos, $len);
 
    $content = preg_replace('~ href="[^"]+"~', '', $entry);
    $content = preg_replace('~(<data .*?) data-value="(?:&quot;)?(\d{4}-\d\d-\d\d)T(\d\d:\d\d:\d\d-\d\d:\d\d)(?:&quot;)?"(.*?>)[^<]+~', '$1$4##%$2T$3%##', $content);
    preg_match('~##%(.*?)%##~', $content, $matches);
    if (count($matches) < 1) {
      preg_match('~<data[^>]+>(.*?)</data>~', $content, $matches);
      $stamp = strtotime($matches[1]) - date('Z');
      $content = preg_replace('~(<data[^>]>).*?(</data>)~', '$1##STAMP##$2', $content);
    } else {
      $stamp = strtotime($matches[1]) - date('Z');
      $content = preg_replace('~##%(.*?)%##~', '##STAMP##', $content);
    }

    $start = '<div class="avatar left">';
    $end   = '</div>';
    $spos  = strpos($content, $start) + strlen($start);
    $len   = strpos($content, $end, $spos) - $spos;

    preg_match('~<img.*? src="(.*?)"[^>]*>~', substr($content, $spos, $len), $matches);
    $uimg    = str_replace('&amp;','&',$matches[1]);
    if (!preg_match('~/avatars/(\d+)/([\d\w\.\-\_]+(\.png|\.jpg))\?~', $uimg, $matches)) {
      $imgname = './userimg/missing_user_avatar.png';
    } else {
      $imgname = './userimg/' . $matches[1] . '_' . $matches[2];
    }

    if (!file_exists($imgname)) {
      echo "Fetching ${uimg}...\n";
      file_put_contents($imgname, chrome_fetch($uimg,$url));
    }
    $content = preg_replace('~(<div class="avatar left".*?)<img .*?>(.*?</div>)~s', '$1##AVATAR##$2', $content);

    $content = $db->escapeString($content);
    if  ($stmt = $db->prepare("INSERT OR IGNORE INTO comments (id,stamp,avatar,content)" .
                              " VALUES ('${cid}','${stamp}','${imgname}','${content}')")) {
      $result = $stmt->execute();
      echo "${cid}\n";
    } else {
      echo "ERROR PROCESSING:\n${content}\n";
    }
  }

  $db->exec("DELETE FROM last");
  $ts = time() - date('Z');
  if ($stmt = $db->prepare("INSERT OR REPLACE INTO last (stamp) VALUES ('${ts}')")) {
    $result = $stmt->execute();
  }
//echo json_encode(html_to_obj($data), JSON_PRETTY_PRINT);
?>
