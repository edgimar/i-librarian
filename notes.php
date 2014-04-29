<?php
include_once 'data.php';
include_once 'functions.php';

if (!empty($_GET['file']))
    $_GET['file'] = intval($_GET['file']);
if (!empty($_POST['file']))
    $_POST['file'] = intval($_POST['file']);
if (!empty($_POST['notesID']))
    $_POST['notesID'] = intval($_POST['notesID']);

database_connect($database_path, 'library');

if (isset($_POST['notesID']) && isset($_POST['file'])) {
    update_notes($_POST['notesID'], $_POST['file'], $_POST['notes'], $dbHandle);
}

if (isset($_GET['file'])) {

    $query = $dbHandle->quote($_GET['file']);
    $user_query = $dbHandle->quote($_SESSION['user_id']);

    $result = $dbHandle->query("SELECT title FROM library WHERE id=$query");
    $title = $result->fetchColumn();
    $result = null;

    $result = $dbHandle->query("SELECT notesID,notes FROM notes WHERE fileID=$query AND userID=$user_query LIMIT 1");
    $fetched = $result->fetch(PDO::FETCH_ASSOC);
    $result = null;

    $notesid = $fetched['notesID'];
    $notes = $fetched['notes'];
}

$dbHandle = null;

if (isset($_GET['editnotes'])) {
    ?>
    <div style="width: 100%;height: 100%">
        <form method="post" action="notes.php" id="form-notes">
            <input type="hidden" name="notesID" value="<?php echo $notesid; ?>">
            <input type="hidden" name="file" value="<?php echo $_GET['file'] ?>">
            <textarea id="notes" name="notes" rows="15" cols="65"><?php echo $notes; ?></textarea>
        </form>
    </div>
    <?php
} else {
    ?>
    <table cellspacing="0" width="100%">
        <tr>
            <td class="items alternating_row" style="border: 0px">
                <button class="edit-notes" id="edit-notes-<?php print intval($_GET['file']) ?>">Write Notes</button>
            </td>
        </tr>
    </table>
    <div style="padding:8px">
    <?php
    print $notes;
    if (empty($notes))
        print '&nbsp;No notes for this record.';
    ?>
    </div>
<?php
}
?>