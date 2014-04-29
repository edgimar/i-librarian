<?php

include_once 'data.php';
include_once 'functions.php';

session_write_close();

$library_path = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library';

if (!empty($_GET['file'])) {

    database_connect($database_path, 'library');
    $file_query = $dbHandle->quote(intval($_GET['file']));
    $result = $dbHandle->query("SELECT file FROM library WHERE id=$file_query LIMIT 1");
    $filename = $result->fetchColumn();
    $dbHandle = null;

    ##########	extract text from pdf	##########

    if (is_file($library_path . DIRECTORY_SEPARATOR . $filename)) {

        system(select_pdftotext() . '"' . $library_path . DIRECTORY_SEPARATOR . $filename . '" "' . $temp_dir . DIRECTORY_SEPARATOR . $filename . '.txt"', $ret);

        if (is_file($temp_dir . DIRECTORY_SEPARATOR . $filename . ".txt")) {

            $stopwords = "a's, able, about, above, according, accordingly, across, actually, after, afterwards, again, against, ain't, all, allow, allows, almost, alone, along, already, also, although, always, am, among, amongst, an, and, another, any, anybody, anyhow, anyone, anything, anyway, anyways, anywhere, apart, appear, appreciate, appropriate, are, aren't, around, as, aside, ask, asking, associated, at, available, away, awfully, be, became, because, become, becomes, becoming, been, before, beforehand, behind, being, believe, below, beside, besides, best, better, between, beyond, both, brief, but, by, c'mon, c's, came, can, can't, cannot, cant, cause, causes, certain, certainly, changes, clearly, co, com, come, comes, concerning, consequently, consider, considering, contain, containing, contains, corresponding, could, couldn't, course, currently, definitely, described, despite, did, didn't, different, do, does, doesn't, doing, don't, done, down, downwards, during, each, edu, eg, eight, either, else, elsewhere, enough, entirely, especially, et, etc, even, ever, every, everybody, everyone, everything, everywhere, ex, exactly, example, except, far, few, fifth, first, five, followed, following, follows, for, former, formerly, forth, four, from, further, furthermore, get, gets, getting, given, gives, go, goes, going, gone, got, gotten, greetings, had, hadn't, happens, hardly, has, hasn't, have, haven't, having, he, he's, hello, help, hence, her, here, here's, hereafter, hereby, herein, hereupon, hers, herself, hi, him, himself, his, hither, hopefully, how, howbeit, however, i'd, i'll, i'm, i've, ie, if, ignored, immediate, in, inasmuch, inc, indeed, indicate, indicated, indicates, inner, insofar, instead, into, inward, is, isn't, it, it'd, it'll, it's, its, itself, just, keep, keeps, kept, know, knows, known, last, lately, later, latter, latterly, least, less, lest, let, let's, like, liked, likely, little, look, looking, looks, ltd, mainly, many, may, maybe, me, mean, meanwhile, merely, might, more, moreover, most, mostly, much, must, my, myself, name, namely, nd, near, nearly, necessary, need, needs, neither, never, nevertheless, new, next, nine, no, nobody, non, none, noone, nor, normally, not, nothing, novel, now, nowhere, obviously, of, off, often, oh, ok, okay, old, on, once, one, ones, only, onto, or, other, others, otherwise, ought, our, ours, ourselves, out, outside, over, overall, own, particular, particularly, per, perhaps, placed, please, plus, possible, presumably, probably, provides, que, quite, qv, rather, rd, re, really, reasonably, regarding, regardless, regards, relatively, respectively, right, said, same, saw, say, saying, says, second, secondly, see, seeing, seem, seemed, seeming, seems, seen, self, selves, sensible, sent, serious, seriously, seven, several, shall, she, should, shouldn't, since, six, so, some, somebody, somehow, someone, something, sometime, sometimes, somewhat, somewhere, soon, sorry, specified, specify, specifying, still, sub, such, sup, sure, t's, take, taken, tell, tends, th, than, thank, thanks, thanx, that, that's, thats, the, their, theirs, them, themselves, then, thence, there, there's, thereafter, thereby, therefore, therein, theres, thereupon, these, they, they'd, they'll, they're, they've, think, third, this, thorough, thoroughly, those, though, three, through, throughout, thru, thus, to, together, too, took, toward, towards, tried, tries, truly, try, trying, twice, two, un, under, 				unfortunately, unless, unlikely, until, unto, up, upon, us, use, used, useful, uses, using, usually, value, various, very, via, viz, vs, want, wants, was, wasn't, way, we, we'd, we'll, we're, we've, welcome, well, went, were, weren't, what, what's, whatever, when, whence, whenever, where, where's, whereafter, whereas, whereby, wherein, whereupon, wherever, whether, which, while, whither, who, who's, whoever, whole, whom, whose, why, will, willing, wish, with, within, without, won't, wonder, would, would, wouldn't, yes, yet, you, you'd, you'll, you're, you've, your, yours, yourself, yourselves, zero";

            $stopwords = explode(', ', $stopwords);

            $string = file_get_contents($temp_dir . DIRECTORY_SEPARATOR . $filename . ".txt");
            unlink($temp_dir . DIRECTORY_SEPARATOR . $filename . ".txt");

            if (!empty($string)) {

                $string = preg_replace('/[^[\x09\x0A\x0D\x20-\x7E]|[\xC2-\xDF][\x80-\xBF]|\xE0[\xA0-\xBF][\x80-\xBF]|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}|\xED[\x80-\x9F][\x80-\xBF]|\xF0[\x90-\xBF][\x80-\xBF]{2}|[\xF1-\xF3][\x80-\xBF]{3}|\xF4[\x80-\x8F][\x80-\xBF]{2}]/', ' ', $string);

                $patterns = join("\b/ui /\b", $stopwords);
                $patterns = "/\b$patterns\b/ui";
                $patterns = explode(" ", $patterns);

                $order = array("\r\n", "\n", "\r");
                $string = str_replace($order, ' ', $string);
                $string = preg_replace($patterns, '', $string);
                $string = preg_replace('/\s{2,}/ui', ' ', $string);

                $fulltext_array = array();
                $fulltext_unique = array();

                $fulltext_array = explode(" ", $string);
                $fulltext_unique = array_unique($fulltext_array);
                $string = implode(" ", $fulltext_unique);

                $output = null;

                database_connect($database_path, 'fulltext');
                $file_query = $dbHandle->quote(intval($_GET['file']));
                $fulltext_query = $dbHandle->quote($string);
                $dbHandle->beginTransaction();
                $result = $dbHandle->query("SELECT id FROM full_text WHERE fileID=$file_query LIMIT 1");
                $record_exists = $result->fetchColumn();
                $result = null;
                if (!$record_exists)
                    $output = $dbHandle->exec("INSERT INTO full_text (fileID,full_text) VALUES ($file_query,$fulltext_query)");
                if ($record_exists)
                    $output = $dbHandle->exec("UPDATE full_text SET full_text=$fulltext_query WHERE id=$record_exists");
                $dbHandle->commit();
                $dbHandle = null;

                if (!$output)
                    $answer = 'Database error.';
            } else {
                $answer = "Text extraction error.";
            }
        } else {
            $answer = "Text extracting not allowed.";
        }
    } else {
        $answer = "File not found.";
    }
}
if (isset($answer))
    print $answer;
?>