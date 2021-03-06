<?php
// save_htmlsnippet.processor.php

if(IN_ETOMITE_SYSTEM != "true")
{
  die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the Etomite Manager instead of accessing this file directly.");
}

if($_SESSION['permissions']['save_snippet'] != 1 && $_REQUEST['a'] == 79)
{
  $e->setError(3);
  $e->dumpError();
}

switch ($_POST['mode'])
{
  case '78':
    //do stuff to save the new doc
    $snippet = mysqli_escape_string($etomiteDBConn, $_POST['post']);
    $name = mysqli_escape_string($etomiteDBConn, htmlentities($_POST['name']));
    $description = mysqli_escape_string($etomiteDBConn, htmlentities($_POST['description']));
    $locked = $_POST['locked'] == 'on' ? 1 : 0 ;
    if($name == "")
    {
      $name = "Untitled HTMLSsnippet";
    }
    $sql = "INSERT INTO $dbase.".$table_prefix."site_htmlsnippets(name, description, snippet, locked) VALUES('".$name."', '".$description."', '".$snippet."', '".$locked."');";
    $rs = mysqli_query($etomiteDBConn, $sql);
    if(!$rs)
    {
      echo "\$rs not set! New htmlsnippet not saved!";
    }
    else
    {
      // get the id
      if(!$newid = mysqli_insert_id($etomiteDBConn))
      {
        echo "Couldn't get last insert key!";
        exit;
      }
      // empty cache
      include_once("cache_sync.class.processor.php");
      $sync = new synccache();
      $sync->setCachepath("../assets/cache/");
      $sync->setReport(false);
      $sync->emptyCache(); // first empty the cache
      // finished emptying cache - redirect
      if($_POST['stay'] != '')
      {
        $header="Location: index.php?a=77&id=$newid&r=2";
        header($header);
      }
      else
      {
        $header = "Location: index.php?a=76&r=2";
        header($header);
      }
    }
  break;

  case '77':
    //do stuff to save the edited doc
    $snippet = mysqli_escape_string($etomiteDBConn, $_POST['post']);
    $name = mysqli_escape_string($etomiteDBConn, htmlentities($_POST['name']));
    $description = mysqli_escape_string($etomiteDBConn, htmlentities($_POST['description']));
    $locked = $_POST['locked'] == 'on' ? 1 : 0 ;
    if($name == "")
    {
      $name = "Untitled snippet";
    }
    $id = $_POST['id'];
    $sql = "UPDATE $dbase.".$table_prefix."site_htmlsnippets SET name='".$name."', description='".$description."', snippet='".$snippet."', locked='".$locked."' WHERE id='".$id."';";
    $rs = mysqli_query($etomiteDBConn, $sql);
    if(!$rs)
    {
      echo "\$rs not set! Edited htmlsnippet not saved!";
    }
    else
    {
      // empty cache
      include_once("cache_sync.class.processor.php");
      $sync = new synccache();
      $sync->setCachepath("../assets/cache/");
      $sync->setReport(false);
      $sync->emptyCache(); // first empty the cache
      // finished emptying cache - redirect
      if($_POST['stay'] != '')
      {
        $header="Location: index.php?a=77&id=$id&r=2";
        header($header);
      }
      else
      {
        $header="Location: index.php?a=76&r=2";
        header($header);
      }
    }
  break;

  default: echo "You supposed to be here now?";
}
?>
