<?php
include_once 'data.php';
include_once 'functions.php';

if (!empty($_GET['project'])) {
    $projectID = $_GET['project'];
} elseif (!empty($_POST['project'])) {
    $projectID = $_POST['project'];
} else {
    die();
}

$databasename = 'project'.intval($projectID);
$dbHandle = new PDO('sqlite:'.$database_path.$databasename.'.sq3');

if(isset($_POST['newmessage']) && !empty($_POST['newmessage'])) {

	$dbHandle->exec("CREATE TABLE IF NOT EXISTS discussion (id INTEGER PRIMARY KEY, user TEXT NOT NULL, timestamp TEXT NOT NULL, message TEXT NOT NULL)");

	$stmt = $dbHandle->prepare("INSERT INTO discussion (user, timestamp, message) VALUES (:user, :timestamp, :message)");

	$stmt->bindParam(':user', $user);
	$stmt->bindParam(':timestamp', $timestamp);
	$stmt->bindParam(':message', $message);

	$user = $_SESSION['user'];
	$timestamp = time();
	$message = $_POST['newmessage'];

	$insert = $stmt->execute();
	$dbHandle = null;
        if ($insert) die('OK');
}

if(isset($_GET['delete1']) && !empty($_GET['delete2'])) {

	$delete = $dbHandle->exec("DROP TABLE discussion");
	$dbHandle = null;
        die('OK');
}

if(isset($_GET['read'])) {

    $result = $dbHandle->query("SELECT * FROM discussion ORDER BY id DESC LIMIT 100");
    $dbHandle = null;

    if (!$result) {
            unlink($database_path.$databasename.'.sq3');
            die('No messages.');
    }

    print '<table>';

    while($message = $result->fetch(PDO::FETCH_ASSOC)) {

        $message['user'] = htmlspecialchars($message['user']);
	$message['message'] = htmlspecialchars($message['message']);
	$message['message'] = preg_replace('/(https?\:\/\/\S+)/i', '<a href="\\1" target="_blank">\\1</a>', $message['message']);
	$message['message'] = nl2br($message['message']);

	print "<tr><td style=\"white-space: nowrap;padding: 4px\"><b>".date("M j, Y, h:i:s A", $message['timestamp']).", ".$message['user'].":</b></td>";
	print "<td style=\"padding: 4px\">$message[message]</td></tr>".PHP_EOL;
    }

    print '</table>';
}
?>
