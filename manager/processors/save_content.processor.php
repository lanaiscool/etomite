<?php
// save_content.processor.php
// Modified in Etomite 0.6.1-RTM by Ralph because the editors now handle desired image URL changes
// Last Modified: 2007-11-15 [0615] by Ralph for alternate createdon functionality for
// Modified 2008-04-25 [v1.0] by Ralph to use new system date|time formatting features
// * All dates are now passed in unix timestamp format

if(IN_ETOMITE_SYSTEM != "true")
{
  die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the Etomite Manager instead of accessing this file directly.");
}

if($_SESSION['permissions']['save_document'] != 1 && $_REQUEST['a'] == 5)
{
  $e->setError(3);
  $e->dumpError();
}

$id = $_POST['id'];
$content = addslashes($_POST['ta']);
$pagetitle = addslashes($_POST['pagetitle']);
$description = addslashes($_POST['description']);
$alias = addslashes($_POST['alias']);
$isfolder = $_POST['isfolder'];
$richtext = $_POST['richtext'];
$published = $_POST['published'];
$parent = $_POST['parent']!='' ? $_POST['parent'] : 0;
$template = $_POST['template'];
$menuindex = $_POST['menuindex'];
if(empty($menuindex)) $menuindex = 0;
$searchable = $_POST['searchable'];
$cacheable = $_POST['cacheable'];
$syncsite = $_POST['syncsite'];
$createdon = $_POST['createdon'];
$pub_date = $_POST['pub_date'];
$unpub_date = $_POST['unpub_date'];
$document_groups = $_POST['document_groups'];
$type = $_POST['type'];
$keywords = $_POST['keywords'];
$contentType = $_POST['contentType'];
$longtitle = addslashes($_POST['setitle']);
$authenticate = $_POST['authenticate'];
$showinmenu = $_POST['showinmenu'];

// if no page title was provided, use default
if(trim($pagetitle == ""))
{
  if($type == "reference")
  {
    $pagetitle = $_lang['untitled_weblink'];
  }
  else
  {
    $pagetitle = $_lang['untitled_document'];
  }
}

// fetch the current time
$currentdate = time();

// if no creation date was provided, use $currentdate
if($createdon == "")
{
  $createdon = $currentdate - fmod($currentdate,60);
}

// make sure $pub_date is numeric
if($pub_date == "")
{
  $pub_date = "0";
}
else
{
  $pub_date = $pub_date - fmod($pub_date,60);
  // if the document should be published, no $pub_date is needed
  if($pub_date < $currentdate)
  {
    $published = 1;
    $pub_date = 0;
  }
  // if the document should not be published, set as un-published
  elseif($pub_date > $currentdate)
  {
    $published = 0;
  }
}

// make sure $unpub_date is numeric
if($unpub_date == "")
{
  $unpub_date = "0";
}
else
{
  $unpub_date = $unpub_date - fmod($unpub_date,60);
  // if the document should be un-published, no $unpub_date is needed
  if($unpub_date < $currentdate)
  {
    $published = 0;
    $unpub_date = 0;
  }
}

$actionToTake = "new";
if($_POST['mode'] == '73' || $_POST['mode'] == '27')
{
  $actionToTake = "edit";
}

// get the document, but only if it already exists (d'oh!)
if($actionToTake != "new")
{
  $sql = "SELECT * FROM $dbase.".$table_prefix."site_content WHERE $dbase.".$table_prefix."site_content.id = $id;";
  $rs = mysqli_query($etomiteDBConn, $sql);
  $limit = mysqli_num_rows($rs);
  if($limit > 1)
  {
    $e->setError(6);
    $e->dumpError();
  }
  if($limit < 1)
  {
    $e->setError(7);
    $e->dumpError();
  }
  $existingDocument = mysqli_fetch_assoc($rs);
}

// check to see if the user is allowed to save the document in the place he wants to save it in
if($use_udperms == 1)
{
  if($existingDocument['parent'] != $parent)
  {
    include_once("user_documents_permissions.class.php");
    $udperms = new udperms();
    $udperms->user = $_SESSION['internalKey'];
    $udperms->document = $parent ;
    $udperms->role = $_SESSION['role'];

    if(!$udperms->checkPermissions())
    {
      include("../includes/header.inc.php");
?>

<br />
<br />

<div class="sectionHeader">
  <img src='media/images/misc/dot.gif' alt="." />&nbsp;<?php echo $_lang['access_permissions']; ?>
</div>

<div class="sectionBody">
  <p><?php echo $_lang['access_permission_parent_denied']; ?></p>
</div>

<?php
      include("../includes/footer.inc.php");
      exit;
    }
  }
}

switch($actionToTake)
{
  case 'new':
    $sql = "INSERT INTO $dbase.".$table_prefix."site_content(content, pagetitle, longtitle, type, description, alias, isfolder, richtext, published, parent, template, menuindex, searchable, cacheable, createdby, createdon, editedby, editedon, pub_date, unpub_date, contentType, authenticate, showinmenu)
        VALUES('".$content."', '".$pagetitle."', '".$longtitle."', '".$type."', '".$description."', '".$alias."', '".$isfolder."', '".$richtext."', '".$published."', '".$parent."', '".$template."', '".$menuindex."', '".$searchable."', '".$cacheable."', ".$_SESSION['internalKey'].", ".time().", ".$_SESSION['internalKey'].", ".$createdon.", $pub_date, $unpub_date, '$contentType', '$authenticate', '$showinmenu')";

    $rs = mysqli_query($etomiteDBConn, $sql);
    if(!$rs)
    {
      echo "An error occured while attempting to save the new document.";
    }

    if(!$key = mysqli_insert_id($etomiteDBConn))
    {
      echo "Couldn't get last insert key!";
    }

    /*******************************************************************************/
    // put the document in the document_groups it should be in
    // first, check that up_perms are switched on!
    if($use_udperms == 1)
    {
      if(is_array($document_groups))
      {
        foreach($document_groups as $dgkey=>$value)
        {
          if($value == "on")
          {
            $sql = "INSERT INTO $dbase.".$table_prefix."document_groups(document_group, document) values(".stripslashes($dgkey).", $key)";
            $rs = mysqli_query($etomiteDBConn, $sql);
            if(!$rs)
            {
              echo "An error occured while attempting to add the document to a document_group.";
              exit;
            }
          }
        }
      }
    }
    // end of document_groups stuff!
    /*******************************************************************************/

    /*******************************************************************************/
    if($parent != 0)
    {
      $sql = "UPDATE $dbase.".$table_prefix."site_content SET isfolder=1 WHERE id=".$_REQUEST['parent'].";";
      $rs = mysqli_query($etomiteDBConn, $sql);
      if(!$rs)
      {
        echo "An error occured while attempting to change the document's parent to a folder.";
      }
    }
    // end of the parent stuff
    /*******************************************************************************/

    // keywords ----------------------
    // remove old keywords first, shouldn't be necessary when creating a new document!
    $sql = "DELETE FROM $dbase.".$table_prefix."keyword_xref WHERE content_id = $key";
    $rs = mysqli_query($etomiteDBConn, $sql);
    for($i=0; $i < count($keywords); $i++)
    {
      $kwid = $keywords[$i];
      $sql = "INSERT INTO $dbase.".$table_prefix."keyword_xref (content_id, keyword_id) VALUES ($key, $kwid)";
      $rs = mysqli_query($etomiteDBConn, $sql);
    }
    // ------------------------

    if($syncsite == 1)
    {
      // empty cache
      include_once("cache_sync.class.processor.php");
      $sync = new synccache();
      $sync->setCachepath("../assets/cache/");
      $sync->setReport(false);
      $sync->emptyCache(); // first empty the cache
    }

    $header="Location: index.php?r=1&id=$id&a=7&dv=1";
    header($header);
  break;

  case 'edit':
    // first, get the document's current parent.
    $sql = "SELECT parent FROM $dbase.".$table_prefix."site_content WHERE id=".$_REQUEST['id'].";";
    $rs = mysqli_query($etomiteDBConn, $sql);
    if(!$rs)
    {
      echo "An error occured while attempting to find the document's current parent.";
      exit;
    }
    $row = mysqli_fetch_assoc($rs);
    $oldparent = $row['parent'];
    // ok, we got the parent

    if($id == $site_start && $published == 0)
    {
      echo "Document is linked to site_start variable and cannot be unpublished!";
      exit;
    }
    if($id == $site_start && ($pub_date != "0" || $unpub_date != "0"))
    {
      echo "Document is linked to site_start variable and cannot have publish or unpublish dates set!";
      exit;
    }
    if($parent == $id)
    {
      echo "Document can not be it's own parent!";
      exit;
    }
    // check to see document is a folder.
    $sql = "SELECT count(*) FROM $dbase.".$table_prefix."site_content WHERE parent=".$_REQUEST['id'].";";
    $rs = mysqli_query($etomiteDBConn, $sql);
    if(!$rs)
    {
      echo "An error occured while attempting to find the document's children.";
      exit;
    }
    $row = mysqli_fetch_assoc($rs);
    if($row['count(*)']>0)
    {
      $isfolder=1;
    }

    $createdby = isset($_POST['resetCreatedon']) ? $_SESSION['internalKey'] : $_POST['createdby'];
    $createdon = isset($_POST['resetCreatedon']) ? time() : $createdon;

    // update the document
    $sql = "UPDATE $dbase.".$table_prefix."site_content SET content='$content', pagetitle='$pagetitle', longtitle='$longtitle', type='$type', description='$description', alias='$alias',
    isfolder=$isfolder, richtext=$richtext, published=$published, pub_date=$pub_date, unpub_date=$unpub_date, parent=$parent, template=$template, menuindex=$menuindex,
    searchable=$searchable, cacheable=$cacheable, createdby=$createdby, createdon=$createdon, editedby=".$_SESSION['internalKey'].", editedon=".time().", contentType='$contentType', authenticate=$authenticate, showinmenu=$showinmenu WHERE id=$id;";

    $rs = mysqli_query($etomiteDBConn, $sql);
    if(!$rs)
    {
      echo "An error occured while attempting to save the edited document. The generated SQL is: <i> $sql </i>.";
    }

    /*******************************************************************************/
    // put the document in the document_groups it should be in
    // first, check that up_perms are switched on!
    if($use_udperms == 1)
    {
      // delete old permissions on the document
      $sql = "DELETE FROM $dbase.".$table_prefix."document_groups WHERE document=$id;";
      $rs = mysqli_query($etomiteDBConn, $sql);
      if(!$rs)
      {
        echo "An error occured while attempting to delete previous document_group entries.";
        exit;
      }
      if(is_array($document_groups))
      {
        foreach ($document_groups as $dgkey=>$value)
        {
          $sql = "INSERT INTO $dbase.".$table_prefix."document_groups(document_group, document) values(".stripslashes($dgkey).", $id)";
          $rs = mysqli_query($etomiteDBConn, $sql);
          if(!$rs)
          {
            echo "An error occured while attempting to add the document to a document_group.<br /><i>$sql</i>";
            exit;
          }
        }
      }
    }
    // end of document_groups stuff!
    /*******************************************************************************/

    /*******************************************************************************/
    // do the parent stuff

    if($parent != 0)
    {
      $sql = "UPDATE $dbase.".$table_prefix."site_content SET isfolder=1 WHERE id=".$_REQUEST['parent'].";";
      $rs = mysqli_query($etomiteDBConn, $sql);
      if(!$rs)
      {
        echo "An error occured while attempting to change the new parent to a folder.";
      }
    }
    // finished moving the document, now check to see if the old_parent should no longer be a folder.
    $sql = "SELECT count(*) FROM $dbase.".$table_prefix."site_content WHERE parent=$oldparent;";
    $rs = mysqli_query($etomiteDBConn, $sql);
    if(!$rs)
    {
      echo "An error occured while attempting to find the old parents' children.";
    }
    $row = mysqli_fetch_assoc($rs);
    $limit = $row['count(*)'];

    if($limit == 0)
    {
      $sql = "UPDATE $dbase.".$table_prefix."site_content SET isfolder=0 WHERE id=$oldparent;";
      $rs = mysqli_query($etomiteDBConn, $sql);
      if(!$rs)
      {
        echo "An error occured while attempting to change the old parent to a regular document.";
      }
    }

    // end of the parent stuff
    /*******************************************************************************/

    // rebuild document keywords
    // remove old keywords first
    $sql = "DELETE FROM $dbase.".$table_prefix."keyword_xref WHERE content_id = $id";
    $rs = mysqli_query($etomiteDBConn, $sql);
    for($i=0; $i < count($keywords); $i++)
    {
      $kwid = $keywords[$i];
      $sql = "INSERT INTO $dbase.".$table_prefix."keyword_xref (content_id, keyword_id) VALUES ($id, $kwid)";
      $rs = mysqli_query($etomiteDBConn, $sql);
    }
    // ------------------------

    // perform cache management
    if($syncsite == 1)
    {
      // empty cache
      include_once("cache_sync.class.processor.php");
      $sync = new synccache();
      $sync->setCachepath("../assets/cache/");
      $sync->setReport(false);
      $sync->emptyCache(); // first empty the cache
    }

    header("Location: index.php?r=1&id=$id&a=7&dv=1");
  break;

  default:
    echo "You supposed to be here now?";
    exit;
}
?>
