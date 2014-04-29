<?php
include_once 'data.php';

if (isset($_SESSION['auth']) && isset($_SESSION['permissions']) && ($_SESSION['permissions'] == 'A' || $_SESSION['permissions'] == 'U')) {

    include_once 'functions.php';

    database_connect($database_path, 'library');

    if (!empty($_GET['old_category']))	$old_category_query = $dbHandle->quote($_GET['old_category']);

    if (!empty($_GET['add_category']) && !empty($_GET['new_category'])) {

        $new_category_query = $dbHandle->quote($_GET['new_category']);
        $dbHandle->exec("INSERT INTO categories (category) VALUES ($new_category_query)");
    }

    if (!empty($_GET['change_category']) && !empty($_GET['new_category']) && !empty($_GET['old_category'])) {

        $new_category_query = $dbHandle->quote($_GET['new_category']);
        $dbHandle->exec("UPDATE categories SET category=$new_category_query WHERE categoryID=$old_category_query");
    }

    if (!empty($_GET['delete_category']) && !empty($_GET['old_category'])) {

        $dbHandle->beginTransaction();
        $dbHandle->exec("DELETE FROM filescategories WHERE categoryID=$old_category_query");
        $dbHandle->exec("DELETE FROM categories WHERE categoryID=$old_category_query");
        $dbHandle->commit();
    }

    $stmt = $dbHandle->prepare("SELECT categoryID,category FROM categories ORDER BY category COLLATE NOCASE");
    ?>
<form action="rename_category.php" method="GET">
    <table border="0" cellpadding="0" cellspacing="0" style="width: 100%">
        <tr>
            <td class="details alternating_row" colspan="2"><b>Add category:</b></td>
        </tr>
        <tr>
            <td class="details">New category:</td>
            <td class="details">
                <input type="text" size="30" name="new_category">
            </td>
        </tr>
        <tr>
            <td class="details" colspan="2">
                <input type="hidden" name="add_category" value="add_category">
                <input type="submit" value="  Add  ">
            </td>
        </tr>
    </table>
</form>
<form action="rename_category.php" method="GET">
    <table border="0" cellpadding="0" cellspacing="0" style="width: 100%">
        <tr>
            <td class="details alternating_row" colspan="2"><b>Rename category:</b></td>
        </tr>
        <tr>
            <td class="details">Old category:</td>
            <td class="details">
                <select name="old_category">
                    <option value="">-</option>
                        <?php
                        $stmt->execute();
                        while ($category = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            print "\r\n<option value=\"".htmlspecialchars($category['categoryID'])."\">".htmlspecialchars(substr($category['category'], 0, 50))."</option>";
                        }
                        ?>
                </select>
            </td>
        </tr>
        <tr>
            <td class="details">New category:</td>
            <td class="details">
                <input type="text" size="30" name="new_category">
            </td>
        </tr>
        <tr>
            <td class="details" colspan="2">
                <input type="hidden" name="change_category" value="change_category">
                <input type="submit" value="  Rename  ">
            </td>
        </tr>
    </table>
</form>
<br><br>
<form action="rename_category.php" method="GET">
    <table border="0" cellpadding="0" cellspacing="0" style="width: 100%">
        <tr>
            <td class="details alternating_row"><b>Delete category:</b></td>
        </tr>
        <tr>
            <td class="details">
                <select name="old_category">
                    <option value="">-</option>
                        <?php
                        $stmt->execute();
                        while ($category = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            print "\r\n<option value=\"".htmlspecialchars($category['categoryID'])."\">".htmlspecialchars(substr($category['category'], 0, 50))."</option>";
                        }
                        ?>
                </select>
                <input type="hidden" name="delete_category" value="delete_category">
                &nbsp;&nbsp;<input type="submit" value="  Delete  ">
            </td>
        </tr>
    </table>
</form>
    <?php
} else {
    print 'Super User or User permissions required.';
}
?>