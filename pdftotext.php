<?php
die('update this and convert to a tool?');

$microtime1 = microtime(true);

include_once 'data.php';
include_once 'functions.php';

include_once 'index.inc.php';

print '<body style="padding:4px">';

if (isset($_GET['commence'])) {

$files = scandir (dirname(__FILE__).DIRECTORY_SEPARATOR.'library');
$file_number = count($files)-5;

$stopwords = "a's, able, about, above, according, accordingly, across, actually, after, afterwards, again, against, ain't, all, allow, allows, almost, alone, along, already, also, although, always, am, among, amongst, an, and, another, any, anybody, anyhow, anyone, anything, anyway, anyways, anywhere, apart, appear, appreciate, appropriate, are, aren't, around, as, aside, ask, asking, associated, at, available, away, awfully, be, became, because, become, becomes, becoming, been, before, beforehand, behind, being, believe, below, beside, besides, best, better, between, beyond, both, brief, but, by, c'mon, c's, came, can, can't, cannot, cant, cause, causes, certain, certainly, changes, clearly, co, com, come, comes, concerning, consequently, consider, considering, contain, containing, contains, corresponding, could, couldn't, course, currently, definitely, described, despite, did, didn't, different, do, does, doesn't, doing, don't, done, down, downwards, during, each, edu, eg, eight, either, else, elsewhere, enough, entirely, especially, et, etc, even, ever, every, everybody, everyone, everything, everywhere, ex, exactly, example, except, far, few, fifth, figure, figures, first, five, followed, following, follows, for, former, formerly, forth, four, from, further, furthermore, get, gets, getting, given, gives, go, goes, going, gone, got, gotten, greetings, had, hadn't, happens, hardly, has, hasn't, have, haven't, having, he, he's, hello, help, hence, her, here, here's, hereafter, hereby, herein, hereupon, hers, herself, hi, him, himself, his, hither, hopefully, how, howbeit, however, i'd, i'll, i'm, i've, ie, if, ignored, immediate, in, inasmuch, inc, indeed, indicate, indicated, indicates, inner, insofar, instead, into, inward, is, isn't, it, it'd, it'll, it's, its, itself, just, keep, keeps, kept, know, knows, known, last, lately, later, latter, latterly, least, less, lest, let, let's, like, liked, likely, little, look, looking, looks, ltd, mainly, many, may, maybe, me, mean, meanwhile, merely, might, more, moreover, most, mostly, much, must, my, myself, name, namely, nd, near, nearly, necessary, need, needs, neither, never, nevertheless, new, next, nine, no, nobody, non, none, noone, nor, normally, not, nothing, novel, now, nowhere, obviously, of, off, often, oh, ok, okay, old, on, once, one, ones, only, onto, or, other, others, otherwise, ought, our, ours, ourselves, out, outside, over, overall, own, particular, particularly, per, perhaps, placed, please, plus, possible, presumably, probably, provides, que, quite, qv, rather, rd, re, really, reasonably, regarding, regardless, regards, relatively, respectively, right, said, same, saw, say, saying, says, second, secondly, see, seeing, seem, seemed, seeming, seems, seen, self, selves, sensible, sent, serious, seriously, seven, several, shall, she, should, shouldn't, since, six, so, some, somebody, somehow, someone, something, sometime, sometimes, somewhat, somewhere, soon, sorry, specified, specify, specifying, still, sub, such, sup, sure, t's, table, tables, take, taken, tell, tends, th, than, thank, thanks, thanx, that, that's, thats, the, their, theirs, them, themselves, then, thence, there, there's, thereafter, thereby, therefore, therein, theres, thereupon, these, they, they'd, they'll, they're, they've, think, third, this, thorough, thoroughly, those, though, three, through, throughout, thru, thus, to, together, too, took, toward, towards, tried, tries, truly, try, trying, twice, two, un, under, unfortunately, unless, unlikely, until, unto, up, upon, us, use, used, useful, uses, using, usually, value, various, very, via, viz, vs, want, wants, was, wasn't, way, we, we'd, we'll, we're, we've, welcome, well, went, were, weren't, what, what's, whatever, when, whence, whenever, where, where's, whereafter, whereas, whereby, wherein, whereupon, wherever, whether, which, while, whither, who, who's, whoever, whole, whom, whose, why, will, willing, wish, with, within, without, won't, wonder, would, would, wouldn't, yes, yet, you, you'd, you'll, you're, you've, your, yours, yourself, yourselves, zero";

$stopwords = explode (', ', $stopwords);

$patterns = join("\b/ui /\b", $stopwords);
$patterns = "/\b$patterns\b/ui";
$patterns = explode(" ", $patterns);

$order   = array("\r\n", "\n", "\r");

$i = 0;

$dbHandle2 = new PDO('sqlite:'.$database_path.'fulltext.sq3');
$result = $dbHandle2->query("SELECT fileID FROM full_text");

$id_array = array();
$id_array = $result->fetchAll(PDO::FETCH_COLUMN);

$result = null;
$dbHandle2 = null;

while (list($key, $file) = each($files)) {

	if ($file != '.' && $file != '..' && $file != 'failed' && $file != 'recorded' && $file != '.htaccess') {

		$i = $i + 1;
		$string = '';
		$exists = null;

		if (file_exists($temp_dir.DIRECTORY_SEPARATOR."librarian_temp".$i.".txt")) unlink ($temp_dir.DIRECTORY_SEPARATOR."librarian_temp".$i.".txt");

		database_connect($database_path, 'library');
		$file_query = $dbHandle->quote($file);

		$result = $dbHandle->query("SELECT id FROM library WHERE file=$file_query LIMIT 1");
		$id_to_index = $result->fetchColumn();

		$result = null;
		$dbHandle = null;

		if (!empty($id_to_index) && !in_array($id_to_index, $id_array)) {

			##########	extract text from pdf	##########

			system(select_pdftotext().'"'.dirname(__FILE__).DIRECTORY_SEPARATOR.'library'.DIRECTORY_SEPARATOR.$file.'" "'.$temp_dir.DIRECTORY_SEPARATOR.'librarian_temp'.$i.'.txt"', $ret);

			if (file_exists($temp_dir.DIRECTORY_SEPARATOR."librarian_temp".$i.".txt")) {

				$string = file_get_contents ($temp_dir.DIRECTORY_SEPARATOR."librarian_temp".$i.".txt");
				$string = trim($string);
			}

			$dbHandle2 = new PDO('sqlite:'.$database_path.'fulltext.sq3');

			$dbHandle2->beginTransaction();

			if (!empty($string)) {

				$string = str_replace($order, ' ', $string);
				$string = preg_replace ($patterns, ' ', $string);
				$string = preg_replace ('/(^|\s)\S{1,2}(\s|$)/u', ' ', $string);
				$string = preg_replace ('/\s{2,}/u', " ", $string);

				$fulltext_array = array();
				$fulltext_unique = array();

				$fulltext_array = explode(" ", $string);
				$fulltext_unique = array_unique($fulltext_array);
				$string = implode(" ", $fulltext_unique);

				$fulltext_query = $dbHandle2->quote($string);

				$dbHandle2->exec("INSERT INTO full_text (fileID,full_text) VALUES ('$id_to_index',$fulltext_query)");

				$microtime2 = microtime(true);
				$microtime = $microtime2 - $microtime1;
				$microtime = sprintf ("%01d sec", $microtime);

				$remaining_time = ($file_number/$i)*$microtime - $microtime;
				$sec = $remaining_time;
				$hms = null;
    
				if ($sec >= 3600) {

					$hours = intval(intval($sec) / 3600); 
					$hms .= $hours. ' h ';
				}

				if ($sec >= 60) {

					$minutes = intval(($sec / 60) % 60); 
					$hms .= $minutes. ' min ';
				}

				print "<div style=\"position: absolute; top: 10px;background-color: white; width: 800px\">";
				print $microtime;
				print ' <b>'.$hms.'to go</b> ';
				print " File #$i: ($file) ".substr($string,0,60);
				print "</div>\r\n";
				@ob_flush();
				flush();

				$fulltext_query = null;

			} else {

				$dbHandle2->exec("DELETE FROM full_text WHERE fileID='$id_to_index'");

				$microtime2 = microtime(true);
				$microtime = $microtime2 - $microtime1;
				$microtime = sprintf ("%01d sec", $microtime);

				$remaining_time = ($file_number/$i)*$microtime - $microtime;
				$sec = $remaining_time;
				$hms = null;
    
				if ($sec >= 3600) {

					$hours = intval(intval($sec) / 3600); 
					$hms .= $hours. ' h ';
				}

				if ($sec >= 60) {

					$minutes = intval(($sec / 60) % 60); 
					$hms .= $minutes. ' min ';
				}

				print "<div style=\"position: absolute; top: 10px;background-color: white; width: 800px\">";
				print $microtime;
				print ' <b>'.$hms.'to go</b> ';
				print " File #$i: ($file) copying disallowed";
				print "</div>\r\n";
				@ob_flush();
				flush();
			}

			$dbHandle2->commit();
			$dbHandle2 = null;
		}

		$file_query = null;
	}
}

	###### clean the temp directory ########

	print "<br><br><br>Cleaning $temp_dir";
	@ob_flush();
	flush();

	for ($j=$i; $j>=1; $j--) {

		if (file_exists($temp_dir.DIRECTORY_SEPARATOR."librarian_temp".$j.".txt")) unlink ($temp_dir.DIRECTORY_SEPARATOR."librarian_temp".$j.".txt");
	}

	print "<br><br>All done.";

} else {

	$files = scandir (dirname(__FILE__).DIRECTORY_SEPARATOR.'library');
	$file_number = count($files)-5;

	$hms = null;
	$sec = 1 * $file_number;
    
	if ($sec >= 3600) {

		$hours = intval(intval($sec) / 3600); 
		$hms .= $hours. ' hours ';
	}

	if ($sec >= 60) {

		$minutes = intval(($sec / 60) % 60); 
		$hms .= $minutes. ' minutes ';
	}

	if ($sec < 3600) {

		$seconds = intval($sec % 60);
		$hms .= $seconds. ' seconds ';
	}


	print "This tool will index your PDF files, in order to enable the full text search.<br><a href=\"?commence=\">Begin indexing</a><br>Warning! This may take several minutes or even hours.<br>Estimated processing time is $hms. Do not interupt the process.";

}
?>

</BODY>
</HTML>
