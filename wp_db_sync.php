<meta http-equiv="content-type" content="text/html; charset=utf-8">
<?php
  
  function logol($text, $show=false) {
       $dat=date('y.m.d H:i:s');
       $fnev="log/wpsync.log";
       $fp = fopen($fnev, 'a');
       $text=$dat." - ".$text;
       $return=" \r\n";
       fwrite($fp, $text.$return);
       fclose($fp);
       
       if ($show) echo "<pre>$text<br><pre>";
  }
  
  $count_image=0;
  $count_posts=0;
  $count_postmeta=0; 
  
  include "wp_upload_sync.php";
  //die('stop');
  $mysqli = new mysqli('localhost','x004175_db','LIkh3T0QVt','x004175_db'); 
  if (!$mysqli) {
   logol('Could not connect to MySQL: ' . mysqli_error() ,true);  
	 die(); 
  } 
  logol('MySQL connection OK.');
  
  /* lekérjük a wphu_posts táblából azokat a rekordokat, ahol a post_type='attachment' és a post_mime_type='image/jpeg' */
  $sql="select * from wphu_posts where post_type='attachment' and post_mime_type in ('image/jpeg','image/png') /*LIMIT 1*/";
  if ($result=$mysqli->query($sql)) {
    while($huobj = $result->fetch_object()){
       /* megnézzük, hogy ez szerepel-e már a wpen_posts táblában */
        //$obj->ID
        $post_name =  $huobj->post_name;
        /* */
        $sql="select count(1) rcount from wpen_posts where post_type='attachment' and post_mime_type in ('image/jpeg','image/png') and post_name='$post_name'";
        if ($en=$mysqli->query($sql)) {
          while($enobj = $en->fetch_object()){
            if ($enobj->rcount==0) {
              /* még nem szerepel ez a post_name a wpen_post-ban */
              $mezok=get_object_vars($huobj);
              $fields="";
              $values="";
              foreach ($mezok as $mezo => $ertek) {
                if ($mezo!='ID') {
                  $ertek=$mysqli->real_escape_string($ertek);
                  if ($fields!='') $fields.=', ';
                  $fields.=$mezo;

                  if ($values!='') $values.=', ';
                  $values.="'$ertek'";
                }
                else $postid_hu=$ertek;
              }
              $sql="insert into wpen_posts ($fields) values ($values);";
              if ($iresult=$mysqli->query($sql)) {
                 $postid_en=$mysqli->insert_id;
                 if ($postid_en) {
                    /* ha sikerült beszúrni a wpen_posts táblába, akkor a wpen_postmeta táblába is be kell szúrni */
                    logol("$post_name: insert wpen_posts ok.");
                    $count_posts++;
                    /* lekérdezzük a wphu_postmetából a megfelelő sorokat */
                    $sql="select * from wphu_postmeta where post_id='$postid_hu'";
                    if ($metahu=$mysqli->query($sql)) {
/**************/
                      while($mhuobj = $metahu->fetch_object()){
                          $mezok=get_object_vars($mhuobj);
                          $fields="";
                          $values="";
                          foreach ($mezok as $mezo => $ertek) {
                            if ($mezo!='meta_id') { //metaid autoincrement
                              if ($mezo=='post_id') $ertek=$postid_en;    //postid az előbb beszúrt id lesz
                              else $ertek=$mysqli->real_escape_string($ertek);

                              if ($fields!='') $fields.=', ';
                              $fields.=$mezo;
            
                              if ($values!='') $values.=', ';
                              $values.="'$ertek'";
                            }
                            
                          }
                          $sql="insert into wpen_postmeta ($fields) values ($values);";
                          if($mysqli->query($sql)) {
                            logol("$post_name: insert wpen_postmeta ok.");
                            $count_postmeta++;
                          }
                          else {
                            logol("$post_name: insert wpen_postmeta error.",true);
                          }
                          
                      }
                        
/**************/
                    }
                 }
                 else logol("$post_name:insert wpen_posts error.",true);
              }
            }
            
          }
          
        }
 
    }
       
  }         
  $mysqli->close();
  logol("Kész.",true);
  logol("$count_image db kép feltöltve",true);
  logol("$count_posts db post frissítve",true);
  logol("$count_postmeta db postameta frissítve.");
?> 