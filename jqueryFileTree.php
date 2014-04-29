<?php
//
// jQuery File Tree PHP Connector
//
// Version 1.01
//
// Cory S.N. LaViska
// A Beautiful Site (http://abeautifulsite.net/)
// 24 March 2008
//
// History:
//
// 1.01 - updated to work with foreign characters in directory/file names (12 April 2008)
// 1.00 - released (24 March 2008)
//
// Output a list of files for jQuery File Tree
//

$_POST['dir'] = urldecode($_POST['dir']);

if( file_exists($_POST['dir']) && is_readable($_POST['dir']) ) {
	$files = scandir($_POST['dir']);

	if( count($files) > 2 ) { /* The 2 accounts for . and .. */
		echo "<ul class=\"jqueryFileTree\" style=\"display: none;\">";
		// All dirs
		foreach( $files as $file ) {
			if($file != '.' && $file != '..' && is_dir($_POST['dir'].$file) && $file[0] != '.') {
				echo "<li class=\"directory collapsed\"><span class=\"ui-icon ui-icon-folder-collapsed\" style=\"float:left\"></span><a href=\"#\" rel=\"" . htmlentities($_POST['dir'] . $file) . "/\">" . htmlentities($file) . "</a></li>";
			}
		}
		echo "</ul>";	
	}
}

?>