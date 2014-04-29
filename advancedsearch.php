<?php
if (isset($_GET['searchtype']) && $_GET['searchtype'] == 'metadata') {

    if (!empty($_GET['anywhere'])) {

        $anywhere_array = array($_GET['anywhere']);
        if ($_GET['anywhere_separator'] == 'AND' || $_GET['anywhere_separator'] == 'OR')
            $anywhere_array = explode(' ', $_GET['anywhere']);

        while ($anywhere = each($anywhere_array)) {

            $like_query = str_replace("\\", "\\\\", $anywhere[1]);
            $like_query = str_replace("%", "\%", $like_query);
            $like_query = str_replace("_", "\_", $like_query);
            $like_query = str_replace("<*>", "%", $like_query);
            $like_query = str_replace("<?>", "_", $like_query);

            $regexp_query = addcslashes($anywhere[1], "\044\050..\053\056\057\074\076\077\133\134\136\173\174");
            $regexp_query = str_replace('\<\*\>', '.*', $regexp_query);
            $regexp_query = str_replace('\<\?\>', '.?', $regexp_query);

            $author_like_query = $dbHandle->quote("%L:\"$like_query%");
            $like_query = $dbHandle->quote("%$like_query%");
            $author_regexp_query = str_replace("'", "''", "L:\"$regexp_query");
            $regexp_query = str_replace("'", "''", $regexp_query);

            $translation = utf8_deaccent($anywhere[1]);

            if ($translation != $anywhere[1]) {
                $author_like_query_translated = $dbHandle->quote("%L:\"$translation%");
                $like_query_translated = $dbHandle->quote("%$translation%");
                $author_regexp_query_translated = str_replace("'", "''", "L:\"$regexp_query");
                $regexp_query_translated = str_replace("'", "''", $translation);

                $like_sql = "authors LIKE $like_query ESCAPE '\' OR journal LIKE $like_query ESCAPE '\' OR secondary_title LIKE $like_query ESCAPE '\' OR affiliation LIKE $like_query ESCAPE '\' OR title LIKE $like_query ESCAPE '\' OR abstract LIKE $like_query ESCAPE '\' OR year LIKE $like_query ESCAPE '\' OR id=" . intval($anywhere[1]) . " OR keywords LIKE $like_query ESCAPE '\' OR
						authors_ascii LIKE $like_query_translated ESCAPE '\' OR title_ascii LIKE $like_query_translated ESCAPE '\' OR abstract_ascii LIKE $like_query_translated ESCAPE '\'";

                $regexp_sql = "regexp_match(authors, '$regexp_query', $case2) OR regexp_match(journal, '$regexp_query', $case2) OR regexp_match(secondary_title, '$regexp_query', $case2) OR regexp_match(affiliation, '$regexp_query', $case2) OR regexp_match(title, '$regexp_query', $case2) OR
						regexp_match(abstract, '$regexp_query', $case2) OR regexp_match(year, '$regexp_query', $case2) OR regexp_match(keywords, '$regexp_query', $case2) OR
						regexp_match(authors_ascii, '$regexp_query_translated', $case2) OR regexp_match(title_ascii, '$regexp_query_translated', $case2) OR regexp_match(abstract_ascii, '$regexp_query_translated', $case2)";
            } else {

                $like_sql = "authors_ascii LIKE $like_query ESCAPE '\' OR journal LIKE $like_query ESCAPE '\' OR secondary_title LIKE $like_query ESCAPE '\' OR affiliation LIKE $like_query ESCAPE '\' OR title_ascii LIKE $like_query ESCAPE '\' OR abstract_ascii LIKE $like_query ESCAPE '\' OR year LIKE $like_query ESCAPE '\' OR id=" . intval($anywhere[1]) . " OR keywords LIKE $like_query ESCAPE '\'";
                $regexp_sql = "regexp_match(authors_ascii, '$regexp_query', $case2) OR regexp_match(journal, '$regexp_query', $case2) OR regexp_match(secondary_title, '$regexp_query', $case2) OR regexp_match(affiliation, '$regexp_query', $case2) OR regexp_match(title_ascii, '$regexp_query', $case2) OR
						regexp_match(abstract_ascii, '$regexp_query', $case2) OR regexp_match(year, '$regexp_query', $case2) OR regexp_match(keywords, '$regexp_query', $case2)";
            }

            if ($whole_words == 1) {
                $anywhere_regexp[] = '(' . $like_sql . ') AND (' . $regexp_sql . ')';
            } else {
                $anywhere_regexp[] = '(' . $like_sql . ')';
            }
        }

        if ($_GET['anywhere_separator'] == 'AND')
            $search_string[] = join(' AND ', $anywhere_regexp);
        if ($_GET['anywhere_separator'] == 'OR')
            $search_string[] = join(' OR ', $anywhere_regexp);
        if ($_GET['anywhere_separator'] == 'PHRASE')
            $search_string[] = join('', $anywhere_regexp);
    }

    #######################################################################

    if (!empty($_GET['authors'])) {

        $authors_array = array($_GET['authors']);
        if ($_GET['authors_separator'] == 'AND' || $_GET['authors_separator'] == 'OR')
            $authors_array = explode(' ', $_GET['authors']);

        while ($authors = each($authors_array)) {

            $like_query = str_replace("\\", "\\\\", $authors[1]);
            $like_query = str_replace("%", "\%", $like_query);
            $like_query = str_replace("_", "\_", $like_query);
            $like_query = str_replace("<*>", "%", $like_query);
            $like_query = str_replace("<?>", "_", $like_query);

            $regexp_query = addcslashes($authors[1], "\044\050..\053\056\057\074\076\077\133\134\136\173\174");
            $regexp_query = str_replace('\<\*\>', '.*', $regexp_query);
            $regexp_query = str_replace('\<\?\>', '.?', $regexp_query);

            $like_query = $dbHandle->quote("%$like_query%");
            $regexp_query = str_replace("'", "''", $regexp_query);

            $translation = utf8_deaccent($authors[1]);

            if ($translation != $authors[1]) {

                $like_query_translated = $dbHandle->quote("%$translation%");
                $regexp_query_translated = str_replace("'", "''", $translation);

                $like_sql = "authors LIKE $like_query ESCAPE '\' OR authors_ascii LIKE $like_query_translated ESCAPE '\'";
                $regexp_sql = "regexp_match(authors, '$regexp_query', $case2) OR regexp_match(authors_ascii, '$regexp_query_translated', $case2)";
            } else {

                $like_sql = "authors_ascii LIKE $like_query ESCAPE '\'";
                $regexp_sql = "regexp_match(authors_ascii, '$regexp_query', $case2)";
            }

            if ($whole_words == 1) {
                $authors_regexp[] = '(' . $like_sql . ') AND (' . $regexp_sql . ')';
            } else {
                $authors_regexp[] = '(' . $like_sql . ')';
            }
        }

        if ($_GET['authors_separator'] == 'AND')
            $search_string[] = join(' AND ', $authors_regexp);
        if ($_GET['authors_separator'] == 'OR')
            $search_string[] = join(' OR ', $authors_regexp);
        if ($_GET['authors_separator'] == 'PHRASE')
            $search_string[] = join('', $authors_regexp);
    }

    #######################################################################

    if (!empty($_GET['journal'])) {

        $journal_array = array($_GET['journal']);
        if ($_GET['journal_separator'] == 'AND' || $_GET['journal_separator'] == 'OR')
            $journal_array = explode(' ', $_GET['journal']);

        while ($journal = each($journal_array)) {

            $like_query = str_replace("\\", "\\\\", $journal[1]);
            $like_query = str_replace("%", "\%", $like_query);
            $like_query = str_replace("_", "\_", $like_query);
            $like_query = str_replace("<*>", "%", $like_query);
            $like_query = str_replace("<?>", "_", $like_query);

            $regexp_query = addcslashes($journal[1], "\044\050..\053\056\057\074\076\077\133\134\136\173\174");
            $regexp_query = str_replace('\<\*\>', '.*', $regexp_query);
            $regexp_query = str_replace('\<\?\>', '.?', $regexp_query);

            $like_query = $dbHandle->quote("%$like_query%");
            $regexp_query = str_replace("'", "''", $regexp_query);

            $translation = utf8_deaccent($journal[1]);

            if ($translation != $journal[1]) {

                $like_query_translated = $dbHandle->quote("%$translation%");
                $regexp_query_translated = str_replace("'", "''", $translation);

                $like_sql = "journal LIKE $like_query ESCAPE '\' OR journal LIKE $like_query_translated ESCAPE '\'";
                $regexp_sql = "regexp_match(journal, '$regexp_query', $case2) OR regexp_match(journal, '$regexp_query_translated', $case2)";
            } else {

                $like_sql = "journal LIKE $like_query ESCAPE '\'";
                $regexp_sql = "regexp_match(journal, '$regexp_query', $case2)";
            }

            if ($whole_words == 1) {
                $journal_regexp[] = '(' . $like_sql . ') AND (' . $regexp_sql . ')';
            } else {
                $journal_regexp[] = '(' . $like_sql . ')';
            }
        }

        if ($_GET['journal_separator'] == 'AND')
            $search_string[] = join(' AND ', $journal_regexp);
        if ($_GET['journal_separator'] == 'OR')
            $search_string[] = join(' OR ', $journal_regexp);
        if ($_GET['journal_separator'] == 'PHRASE')
            $search_string[] = join('', $journal_regexp);
    }

    #######################################################################

    if (!empty($_GET['secondary_title'])) {

        $secondary_title_array = array($_GET['secondary_title']);
        if ($_GET['secondary_title_separator'] == 'AND' || $_GET['secondary_title_separator'] == 'OR')
            $secondary_title_array = explode(' ', $_GET['secondary_title']);

        while ($secondary_title = each($secondary_title_array)) {

            $like_query = str_replace("\\", "\\\\", $secondary_title[1]);
            $like_query = str_replace("%", "\%", $like_query);
            $like_query = str_replace("_", "\_", $like_query);
            $like_query = str_replace("<*>", "%", $like_query);
            $like_query = str_replace("<?>", "_", $like_query);

            $regexp_query = addcslashes($secondary_title[1], "\044\050..\053\056\057\074\076\077\133\134\136\173\174");
            $regexp_query = str_replace('\<\*\>', '.*', $regexp_query);
            $regexp_query = str_replace('\<\?\>', '.?', $regexp_query);

            $like_query = $dbHandle->quote("%$like_query%");
            $regexp_query = str_replace("'", "''", $regexp_query);

            $translation = utf8_deaccent($secondary_title[1]);

            if ($translation != $secondary_title[1]) {

                $like_query_translated = $dbHandle->quote("%$translation%");
                $regexp_query_translated = str_replace("'", "''", $translation);

                $like_sql = "secondary_title LIKE $like_query ESCAPE '\' OR secondary_title LIKE $like_query_translated ESCAPE '\'";
                $regexp_sql = "regexp_match(secondary_title, '$regexp_query', $case2) OR regexp_match(secondary_title, '$regexp_query_translated', $case2)";
            } else {

                $like_sql = "secondary_title LIKE $like_query ESCAPE '\'";
                $regexp_sql = "regexp_match(secondary_title, '$regexp_query', $case2)";
            }

            if ($whole_words == 1) {
                $secondary_title_regexp[] = '(' . $like_sql . ') AND (' . $regexp_sql . ')';
            } else {
                $secondary_title_regexp[] = '(' . $like_sql . ')';
            }
        }

        if ($_GET['secondary_title_separator'] == 'AND')
            $search_string[] = join(' AND ', $secondary_title_regexp);
        if ($_GET['secondary_title_separator'] == 'OR')
            $search_string[] = join(' OR ', $secondary_title_regexp);
        if ($_GET['secondary_title_separator'] == 'PHRASE')
            $search_string[] = join('', $secondary_title_regexp);
    }

    #######################################################################

    if (!empty($_GET['affiliation'])) {

        $affiliation_array = array($_GET['affiliation']);
        if ($_GET['affiliation_separator'] == 'AND' || $_GET['affiliation_separator'] == 'OR')
            $affiliation_array = explode(' ', $_GET['affiliation']);

        while ($affiliation = each($affiliation_array)) {

            $like_query = str_replace("\\", "\\\\", $affiliation[1]);
            $like_query = str_replace("%", "\%", $like_query);
            $like_query = str_replace("_", "\_", $like_query);
            $like_query = str_replace("<*>", "%", $like_query);
            $like_query = str_replace("<?>", "_", $like_query);

            $regexp_query = addcslashes($affiliation[1], "\044\050..\053\056\057\074\076\077\133\134\136\173\174");
            $regexp_query = str_replace('\<\*\>', '.*', $regexp_query);
            $regexp_query = str_replace('\<\?\>', '.?', $regexp_query);

            $like_query = $dbHandle->quote("%$like_query%");
            $regexp_query = str_replace("'", "''", $regexp_query);

            $translation = utf8_deaccent($affiliation[1]);

            if ($translation != $affiliation[1]) {

                $like_query_translated = $dbHandle->quote("%$translation%");
                $regexp_query_translated = str_replace("'", "''", $translation);

                $like_sql = "affiliation LIKE $like_query ESCAPE '\' OR affiliation LIKE $like_query_translated ESCAPE '\'";
                $regexp_sql = "regexp_match(affiliation, '$regexp_query', $case2) OR regexp_match(affiliation, '$regexp_query_translated', $case2)";
            } else {

                $like_sql = "affiliation LIKE $like_query ESCAPE '\'";
                $regexp_sql = "regexp_match(affiliation, '$regexp_query', $case2)";
            }

            if ($whole_words == 1) {
                $affiliation_regexp[] = '(' . $like_sql . ') AND (' . $regexp_sql . ')';
            } else {
                $affiliation_regexp[] = '(' . $like_sql . ')';
            }
        }

        if ($_GET['affiliation_separator'] == 'AND')
            $search_string[] = join(' AND ', $affiliation_regexp);
        if ($_GET['affiliation_separator'] == 'OR')
            $search_string[] = join(' OR ', $affiliation_regexp);
        if ($_GET['affiliation_separator'] == 'PHRASE')
            $search_string[] = join('', $affiliation_regexp);
    }

    #######################################################################

    if (!empty($_GET['title'])) {

        $title_array = array($_GET['title']);
        if ($_GET['title_separator'] == 'AND' || $_GET['title_separator'] == 'OR')
            $title_array = explode(' ', $_GET['title']);

        while ($title = each($title_array)) {

            $like_query = str_replace("\\", "\\\\", $title[1]);
            $like_query = str_replace("%", "\%", $like_query);
            $like_query = str_replace("_", "\_", $like_query);
            $like_query = str_replace("<*>", "%", $like_query);
            $like_query = str_replace("<?>", "_", $like_query);

            $regexp_query = addcslashes($title[1], "\044\050..\053\056\057\074\076\077\133\134\136\173\174");
            $regexp_query = str_replace('\<\*\>', '.*', $regexp_query);
            $regexp_query = str_replace('\<\?\>', '.?', $regexp_query);

            $like_query = $dbHandle->quote("%$like_query%");
            $regexp_query = str_replace("'", "''", $regexp_query);

            $translation = utf8_deaccent($title[1]);

            if ($translation != $title[1]) {

                $like_query_translated = $dbHandle->quote("%$translation%");
                $regexp_query_translated = str_replace("'", "''", $translation);

                $like_sql = "title LIKE $like_query ESCAPE '\' OR title_ascii LIKE $like_query_translated ESCAPE '\'";
                $regexp_sql = "regexp_match(title, '$regexp_query', $case2) OR regexp_match(title_ascii, '$regexp_query_translated', $case2)";
            } else {

                $like_sql = "title_ascii LIKE $like_query ESCAPE '\'";
                $regexp_sql = "regexp_match(title_ascii, '$regexp_query', $case2)";
            }

            if ($whole_words == 1) {
                $title_regexp[] = '(' . $like_sql . ') AND (' . $regexp_sql . ')';
            } else {
                $title_regexp[] = '(' . $like_sql . ')';
            }
        }

        if ($_GET['title_separator'] == 'AND')
            $search_string[] = join(' AND ', $title_regexp);
        if ($_GET['title_separator'] == 'OR')
            $search_string[] = join(' OR ', $title_regexp);
        if ($_GET['title_separator'] == 'PHRASE')
            $search_string[] = join('', $title_regexp);
    }


    #######################################################################

    if (!empty($_GET['keywords'])) {

        $keywords_array = array($_GET['keywords']);
        if ($_GET['keywords_separator'] == 'AND' || $_GET['keywords_separator'] == 'OR')
            $keywords_array = explode(' ', $_GET['keywords']);

        while ($keywords = each($keywords_array)) {

            $like_query = str_replace("\\", "\\\\", $keywords[1]);
            $like_query = str_replace("%", "\%", $like_query);
            $like_query = str_replace("_", "\_", $like_query);
            $like_query = str_replace("<*>", "%", $like_query);
            $like_query = str_replace("<?>", "_", $like_query);

            $regexp_query = addcslashes($keywords[1], "\044\050..\053\056\057\074\076\077\133\134\136\173\174");
            $regexp_query = str_replace('\<\*\>', '.*', $regexp_query);
            $regexp_query = str_replace('\<\?\>', '.?', $regexp_query);

            $like_query = $dbHandle->quote("%$like_query%");
            $regexp_query = str_replace("'", "''", $regexp_query);

            $translation = utf8_deaccent($keywords[1]);

            if ($translation != $keywords[1]) {

                $like_query_translated = $dbHandle->quote("%$translation%");
                $regexp_query_translated = str_replace("'", "''", $translation);

                $like_sql = "keywords LIKE $like_query ESCAPE '\' OR keywords LIKE $like_query_translated ESCAPE '\'";
                $regexp_sql = "regexp_match(keywords, '$regexp_query', $case2) OR regexp_match(keywords, '$regexp_query_translated', $case2)";
            } else {

                $like_sql = "keywords LIKE $like_query ESCAPE '\'";
                $regexp_sql = "regexp_match(keywords, '$regexp_query', $case2)";
            }

            if ($whole_words == 1) {
                $keywords_regexp[] = '(' . $like_sql . ') AND (' . $regexp_sql . ')';
            } else {
                $keywords_regexp[] = '(' . $like_sql . ')';
            }
        }

        if ($_GET['keywords_separator'] == 'AND')
            $search_string[] = join(' AND ', $keywords_regexp);
        if ($_GET['keywords_separator'] == 'OR')
            $search_string[] = join(' OR ', $keywords_regexp);
        if ($_GET['keywords_separator'] == 'PHRASE')
            $search_string[] = join('', $keywords_regexp);
    }


    #######################################################################

    if (!empty($_GET['abstract'])) {

        $abstract_array = array($_GET['abstract']);
        if ($_GET['abstract_separator'] == 'AND' || $_GET['abstract_separator'] == 'OR')
            $abstract_array = explode(' ', $_GET['abstract']);

        while ($abstract = each($abstract_array)) {

            $like_query = str_replace("\\", "\\\\", $abstract[1]);
            $like_query = str_replace("%", "\%", $like_query);
            $like_query = str_replace("_", "\_", $like_query);
            $like_query = str_replace("<*>", "%", $like_query);
            $like_query = str_replace("<?>", "_", $like_query);

            $regexp_query = addcslashes($abstract[1], "\044\050..\053\056\057\074\076\077\133\134\136\173\174");
            $regexp_query = str_replace('\<\*\>', '.*', $regexp_query);
            $regexp_query = str_replace('\<\?\>', '.?', $regexp_query);

            $like_query = $dbHandle->quote("%$like_query%");
            $regexp_query = str_replace("'", "''", $regexp_query);

            $translation = utf8_deaccent($abstract[1]);

            if ($translation != $abstract[1]) {

                $like_query_translated = $dbHandle->quote("%$translation%");
                $regexp_query_translated = str_replace("'", "''", $translation);

                $like_sql = "title LIKE $like_query ESCAPE '\' OR abstract LIKE $like_query ESCAPE '\' OR
						title_ascii LIKE $like_query_translated ESCAPE '\' OR abstract_ascii LIKE $like_query_translated ESCAPE '\'";
                $regexp_sql = "regexp_match(title, '$regexp_query', $case2) OR regexp_match(abstract, '$regexp_query', $case2) OR
						regexp_match(title_ascii, '$regexp_query_translated', $case2) OR regexp_match(abstract_ascii, '$regexp_query_translated', $case2)";
            } else {

                $like_sql = "title_ascii LIKE $like_query ESCAPE '\' OR abstract_ascii LIKE $like_query ESCAPE '\'";
                $regexp_sql = "regexp_match(title_ascii, '$regexp_query', $case2) OR regexp_match(abstract_ascii, '$regexp_query', $case2)";
            }

            if ($whole_words == 1) {
                $abstract_regexp[] = '(' . $like_sql . ') AND (' . $regexp_sql . ')';
            } else {
                $abstract_regexp[] = '(' . $like_sql . ')';
            }
        }

        if ($_GET['abstract_separator'] == 'AND')
            $search_string[] = join(' AND ', $abstract_regexp);
        if ($_GET['abstract_separator'] == 'OR')
            $search_string[] = join(' OR ', $abstract_regexp);
        if ($_GET['abstract_separator'] == 'PHRASE')
            $search_string[] = join('', $abstract_regexp);
    }

    #######################################################################

    if (!empty($_GET['year'])) {

        $year_array = explode(' ', $_GET['year']);

        while ($year = each($year_array)) {
            $year_regexp[] = "(year=" . intval($year[1]) . " OR strftime('%Y', year) LIKE '" . intval($year[1]) . "')";
        }

        $search_string[] = join(' OR ', $year_regexp);
    }

    #######################################################################

    if (!empty($_GET['search_id'])) {

        $search_id_array = explode(' ', $_GET['search_id']);

        while ($search_id = each($search_id_array)) {
            $search_id_regexp[] = "id=" . intval($search_id[1]);
        }

        $search_string[] = join(' OR ', $search_id_regexp);
    }

    #######################################################################

    if (count($search_string) > 1) {
        $search_string = join(') AND (', $search_string);
    } elseif (count($search_string) == 1) {
        $search_string = join('', $search_string);
    }
    $search_string = '(' . $search_string . ')';

##########################notes#####################################
} elseif (!empty($_GET['notes']) && $_GET['searchtype'] == 'notes') {

    $notes_array = array($_GET['notes']);
    if ($_GET['notes_separator'] == 'AND' || $_GET['notes_separator'] == 'OR')
        $notes_array = explode(' ', $_GET['notes']);


    while ($notes = each($notes_array)) {

        $like_query = str_replace("\\", "\\\\", $notes[1]);
        $like_query = str_replace("%", "\%", $like_query);
        $like_query = str_replace("_", "\_", $like_query);
        $like_query = str_replace("<*>", "%", $like_query);
        $like_query = str_replace("<?>", "_", $like_query);

        $regexp_query = addcslashes($notes[1], "\044\050..\053\056\057\074\076\077\133\134\136\173\174");
        $regexp_query = str_replace('\<\*\>', '.*', $regexp_query);
        $regexp_query = str_replace('\<\?\>', '.?', $regexp_query);

        $like_query = $dbHandle->quote("%$like_query%");
        $regexp_query = str_replace("'", "''", $regexp_query);

        $like_sql = "search_strip_tags(notes) LIKE $like_query ESCAPE '\'";
        $regexp_sql = "regexp_match(search_strip_tags(notes), '$regexp_query', $case2)";

        if ($whole_words == 1) {
            $notes_regexp[] = '(' . $like_sql . ') AND (' . $regexp_sql . ')';
        } else {
            $notes_regexp[] = '(' . $like_sql . ')';
        }
    }

    if ($_GET['notes_separator'] == 'AND')
        $search_string = join(' AND ', $notes_regexp);
    if ($_GET['notes_separator'] == 'OR')
        $search_string = join(' OR ', $notes_regexp);
    if ($_GET['notes_separator'] == 'PHRASE')
        $search_string = join('', $notes_regexp);

##########################fulltext#####################################
} elseif (!empty($_GET['fulltext']) && $_GET['searchtype'] == 'pdf') {

    $fulltext_array = explode(' ', $_GET['fulltext']);

//INDEX
    #if ($_GET['fulltext_separator'] == 'AND') $search_string = join (' ', $fulltext_array);

    $stopwords = "a's, able, about, above, according, accordingly, across, actually, after, afterwards, again, against, ain't, all, allow, allows, almost, alone, along, already, also, although, always, am, among, amongst, an, and, another, any, anybody, anyhow, anyone, anything, anyway, anyways, anywhere, apart, appear, appreciate, appropriate, are, aren't, around, as, aside, ask, asking, associated, at, available, away, awfully, be, became, because, become, becomes, becoming, been, before, beforehand, behind, being, believe, below, beside, besides, best, better, between, beyond, both, brief, but, by, c'mon, c's, came, can, can't, cannot, cant, cause, causes, certain, certainly, changes, clearly, co, com, come, comes, concerning, consequently, consider, considering, contain, containing, contains, corresponding, could, couldn't, course, currently, definitely, described, despite, did, didn't, different, do, does, doesn't, doing, don't, done, down, downwards, during, each, edu, eg, eight, either, else, elsewhere, enough, entirely, especially, et, etc, even, ever, every, everybody, everyone, everything, everywhere, ex, exactly, example, except, far, few, fifth, first, five, followed, following, follows, for, former, formerly, forth, four, from, further, furthermore, get, gets, getting, given, gives, go, goes, going, gone, got, gotten, greetings, had, hadn't, happens, hardly, has, hasn't, have, haven't, having, he, he's, hello, help, hence, her, here, here's, hereafter, hereby, herein, hereupon, hers, herself, hi, him, himself, his, hither, hopefully, how, howbeit, however, i'd, i'll, i'm, i've, ie, if, ignored, immediate, in, inasmuch, inc, indeed, indicate, indicated, indicates, inner, insofar, instead, into, inward, is, isn't, it, it'd, it'll, it's, its, itself, just, keep, keeps, kept, know, knows, known, last, lately, later, latter, latterly, least, less, lest, let, let's, like, liked, likely, little, look, looking, looks, ltd, mainly, many, may, maybe, me, mean, meanwhile, merely, might, more, moreover, most, mostly, much, must, my, myself, name, namely, nd, near, nearly, necessary, need, needs, neither, never, nevertheless, new, next, nine, no, nobody, non, none, noone, nor, normally, not, nothing, novel, now, nowhere, obviously, of, off, often, oh, ok, okay, old, on, once, one, ones, only, onto, or, other, others, otherwise, ought, our, ours, ourselves, out, outside, over, overall, own, particular, particularly, per, perhaps, placed, please, plus, possible, presumably, probably, provides, que, quite, qv, rather, rd, re, really, reasonably, regarding, regardless, regards, relatively, respectively, right, said, same, saw, say, saying, says, second, secondly, see, seeing, seem, seemed, seeming, seems, seen, self, selves, sensible, sent, serious, seriously, seven, several, shall, she, should, shouldn't, since, six, so, some, somebody, somehow, someone, something, sometime, sometimes, somewhat, somewhere, soon, sorry, specified, specify, specifying, still, sub, such, sup, sure, t's, take, taken, tell, tends, th, than, thank, thanks, thanx, that, that's, thats, the, their, theirs, them, themselves, then, thence, there, there's, thereafter, thereby, therefore, therein, theres, thereupon, these, they, they'd, they'll, they're, they've, think, third, this, thorough, thoroughly, those, though, three, through, throughout, thru, thus, to, together, too, took, toward, towards, tried, tries, truly, try, trying, twice, two, un, under, 				unfortunately, unless, unlikely, until, unto, up, upon, us, use, used, useful, uses, using, usually, value, various, very, via, viz, vs, want, wants, was, wasn't, way, we, we'd, we'll, we're, we've, welcome, well, went, were, weren't, what, what's, whatever, when, whence, whenever, where, where's, whereafter, whereas, whereby, wherein, whereupon, wherever, whether, which, while, whither, who, who's, whoever, whole, whom, whose, why, will, willing, wish, with, within, without, won't, wonder, would, would, wouldn't, yes, yet, you, you'd, you'll, you're, you've, your, yours, yourself, yourselves, zero";

    $stopwords = explode(', ', $stopwords);

    $patterns = join("\b/ui /\b", $stopwords);
    $patterns = "/\b$patterns\b/ui";
    $patterns = explode(" ", $patterns);

    $_GET['fulltext'] = preg_replace($patterns, '<*>', $_GET['fulltext']);

    while ($fulltext = each($fulltext_array)) {

        $like_query = str_replace("\\", "\\\\", $fulltext[1]);
        $like_query = str_replace("%", "\%", $like_query);
        $like_query = str_replace("_", "\_", $like_query);
        $like_query = str_replace("<*>", "%", $like_query);
        $like_query = str_replace("<?>", "_", $like_query);

        $regexp_query = addcslashes($fulltext[1], "\044\050..\053\056\057\074\076\077\133\134\136\173\174");
        $regexp_query = str_replace('\<\*\>', '.*', $regexp_query);
        $regexp_query = str_replace('\<\?\>', '.?', $regexp_query);

        $like_query = $dbHandle->quote("%$like_query%");
        $regexp_query = str_replace("'", "''", $regexp_query);

        $translation = utf8_deaccent($fulltext[1]);

        if ($translation != $fulltext[1]) {

            $like_query_translated = $dbHandle->quote("%$translation%");
            $regexp_query_translated = str_replace("'", "''", $translation);

            $like_sql = "full_text LIKE $like_query ESCAPE '\' OR full_text LIKE $like_query_translated ESCAPE '\'";
            $regexp_sql = "regexp_match(full_text, '$regexp_query', $case2) OR regexp_match(full_text, '$regexp_query_translated', $case2)";
        } else {

            $like_sql = "full_text LIKE $like_query ESCAPE '\'";
            $regexp_sql = "regexp_match(full_text, '$regexp_query', $case2)";
        }

        if ($whole_words == 1) {
            $fulltext_regexp[] = '(' . $like_sql . ') AND (' . $regexp_sql . ')';
        } else {
            $fulltext_regexp[] = '(' . $like_sql . ')';
        }
    }

    if ($_GET['fulltext_separator'] == 'AND')
        $search_string = join(' AND ', $fulltext_regexp);
    if ($_GET['fulltext_separator'] == 'OR')
        $search_string = join(' OR ', $fulltext_regexp);
} else {

    include_once 'data.php';
    include_once 'functions.php';

    function search_row($field) {

        print '
<input type="text" size="70" style="width:99.5%" name="' . $field . '" value="' . (isset($_SESSION['session_' . $field]) ? htmlspecialchars($_SESSION['session_' . $field]) : '') . '">
<table border=0 cellspacing=0 cellpadding=0>
 <tr>
  <td class="select_span" style="line-height:16px">
   <input type="radio" name="' . $field . '_separator" value="AND" style="display:none" ' . ((isset($_SESSION['session_' . $field . '_separator']) && $_SESSION['session_' . $field . '_separator'] == 'AND') ? 'checked' : '') . '>
   <span class="ui-icon ui-icon-radio-' . ((isset($_SESSION['session_' . $field . '_separator']) && $_SESSION['session_' . $field . '_separator'] == 'AND') ? 'on' : 'off') . '" style="float:left">
   </span> AND&nbsp;
  </td>
  <td class="select_span" style="line-height:16px">
   <input type="radio" name="' . $field . '_separator" value="OR" style="display:none" ' . ((isset($_SESSION['session_' . $field . '_separator']) && $_SESSION['session_' . $field . '_separator'] == 'OR') ? 'checked' : '') . '>
   <span class="ui-icon ui-icon-radio-' . ((isset($_SESSION['session_' . $field . '_separator']) && $_SESSION['session_' . $field . '_separator'] == 'OR') ? 'on' : 'off') . '" style="float:left">
   </span> OR&nbsp;
  </td>
  <td class="select_span" style="line-height:16px">
   <input type="radio" name="' . $field . '_separator" value="PHRASE" style="display:none" ' . ((!isset($_SESSION['session_' . $field . '_separator']) || $_SESSION['session_' . $field . '_separator'] == 'PHRASE') ? 'checked' : '') . '>
   <span class="ui-icon ui-icon-radio-' . ((!isset($_SESSION['session_' . $field . '_separator']) || $_SESSION['session_' . $field . '_separator'] == 'PHRASE') ? 'on' : 'off') . '" style="float:left">
   </span> phrase
  </td>
 </tr>
</table>';
    }
    ?>
    <form action="search.php" method="GET" id="advancedsearchform">
        <input type="hidden" name="searchtype" value="metadata">
        <input type="hidden" name="searchmode" value="advanced">
        <table cellspacing=0 class="threed" style="width:99%">
            <tr>
                <td style="width:49.5%">
                    <table id="advancedsearchtabs">
                        <tr>
                            <td>
                                <div class="ui-state-highlight ui-corner-top clicked" style="margin-left:2px;padding:0 4px" id="advtab-search-ref">
                                    Search References
                                </div>
                            </td>
                            <td>
                                <div class="ui-state-highlight ui-corner-top" style="margin-left:2px;padding:0 4px" id="advtab-search-pdf">
                                    Search PDFs
                                </div>
                            </td>
                            <td style="<?php if (!isset($_SESSION['auth'])) print 'display:none'; ?>">
                                <div class="ui-state-highlight ui-corner-top" style="margin-left:2px;padding:0 4px" id="advtab-search-notes">
                                    Search Notes
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style="width:49.5%">
                    <table style="margin-left:1px;margin-right:auto;width:100%">
                        <tr>
                            <td>
                                <table cellspacing=0>
                                    <tr>
                                        <td class="select_span" style="line-height:16px">
                                            <input type="radio" name="include-categories" value="1" style="display:none"
                                            <?php if (isset($_SESSION['session_include-categories']) && $_SESSION['session_include-categories'] == 1)
                                                print ' checked' ?>>
                                            <span class="ui-icon ui-icon-radio-<?php print (isset($_SESSION['session_include-categories']) && $_SESSION['session_include-categories'] == 1) ? 'on' : 'off'  ?>" style="float:left">
                                            </span>Include
                                        </td>
                                        <td class="select_span" style="line-height:16px">
                                            <input type="radio" name="include-categories" value="2" style="display:none"
                                            <?php if (isset($_SESSION['session_include-categories']) && $_SESSION['session_include-categories'] == 2)
                                                print ' checked' ?>>
                                            <span class="ui-icon ui-icon-radio-<?php print (isset($_SESSION['session_include-categories']) && $_SESSION['session_include-categories'] == 2) ? 'on' : 'off'  ?>" style="float:left">
                                            </span>Exclude these categories:
                                        </td>
                                    </tr>
                                </table>
                            </td>
                            <td style="text-align:right">
                                <input type="text" id="advanced-filter" value="Filter" style="margin-left:auto;margin-right:2px">
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        <table cellspacing=0 class="threed" style="width:49.5%;float:left">
            <tr class="refrow">
                <td class="threed" style="width:8em">
                    Anywhere:
                </td>
                <td class="threed" colspan="3">
                    <?php search_row('anywhere'); ?>
                </td>
            </tr>
            <tr class="refrow">
                <td class="threed">
                    Authors:
                </td>
                <td class="threed" colspan="3">
                    <?php search_row('authors'); ?>
                </td>
            </tr>
            <tr class="refrow">
                <td class="threed">
                    Title:
                </td>
                <td class="threed" colspan="3">
                    <?php search_row('title'); ?>
                </td>
            </tr>
            <tr class="refrow">
                <td class="threed">
                    Title/Abstract:
                </td>
                <td class="threed" colspan="3">
                    <?php search_row('abstract'); ?>
                </td>
            </tr>
            <tr class="refrow">
                <td class="threed">
                    Keywords:
                </td>
                <td class="threed" colspan="3">
                    <?php search_row('keywords'); ?>
                </td>
            </tr>
            <tr class="refrow">
                <td class="threed">
                    Journal:
                </td>
                <td class="threed" colspan="3">
                    <?php search_row('journal'); ?>
                </td>
            </tr>
            <tr class="refrow">
                <td class="threed">
                    Secondary title:
                </td>
                <td class="threed" colspan="3">
                    <?php search_row('secondary_title'); ?>
                </td>
            </tr>
            <tr class="refrow">
                <td class="threed">
                    Affiliation:
                </td>
                <td class="threed" colspan="3">
                    <?php search_row('affiliation'); ?>
                </td>
            </tr>
            <tr class="refrow">
                <td class="threed">
                    Year:
                </td>
                <td class="threed">
                    <input type="text"  size="70" style="width:98%" name="year" value="<?php print isset($_SESSION['session_year']) ? htmlspecialchars($_SESSION['session_year']) : ''; ?>">
                    <table border=0 cellspacing=0 cellpadding=0>
                        <tr>
                            <td style="line-height:16px">
                                <span class="ui-icon ui-icon-radio-on" style="float:left"></span>OR
                            </td>
                        </tr>
                    </table>
                </td>
                <td class="threed">
                    &nbsp;ID:&nbsp;
                </td>
                <td class="threed">
                    <input type="text" size="70" style="width:98%" name="search_id" value="<?php print isset($_SESSION['session_search_id']) ? htmlspecialchars($_SESSION['session_search_id']) : ''; ?>">
                    <table border=0 cellspacing=0>
                        <tr>
                            <td style="line-height:16px">
                                <span class="ui-icon ui-icon-radio-on" style="float:left"></span>OR
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr id="fulltextrow" style="display:none">
                <td class="threed" style="width:8em">
                    Full text:
                </td>
                <td class="threed" colspan="3">
                    <input type="text" size="70" style="width:99.5%" name="fulltext" value="<?php print isset($_SESSION['session_fulltext']) ? htmlspecialchars($_SESSION['session_fulltext']) : ''; ?>">
                    <table border=0 cellspacing=0 cellpadding=0>
                        <tr>
                            <td class="select_span" style="line-height:16px">
                                <input type="radio" name="fulltext_separator" value="AND" style="display:none" <?php print (empty($_SESSION['session_fulltext_separator']) || (isset($_SESSION['session_fulltext_separator']) && $_SESSION['session_fulltext_separator'] == 'AND')) ? 'checked' : ''  ?>>
                                <span class="ui-icon ui-icon-radio-<?php print (empty($_SESSION['session_fulltext_separator']) || (isset($_SESSION['session_fulltext_separator']) && $_SESSION['session_fulltext_separator'] == 'AND')) ? 'on' : 'off'  ?>" style="float:left">
                                </span>AND
                            </td>
                            <td class="select_span" style="line-height:16px">
                                <input type="radio" name="fulltext_separator" value="OR" style="display:none" <?php print (isset($_SESSION['session_fulltext_separator']) && $_SESSION['session_fulltext_separator'] == 'OR') ? 'checked' : ''  ?>>
                                <span class="ui-icon ui-icon-radio-<?php print (isset($_SESSION['session_fulltext_separator']) && $_SESSION['session_fulltext_separator'] == 'OR') ? 'on' : 'off'  ?>" style="float:left">
                                </span>OR
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr id="notesrow" style="display:none">
                <td class="threed" style="width:8em">
                    Notes:
                </td>
                <td class="threed" colspan="3">
                    <?php search_row('notes'); ?>
                </td>
            </tr>
            <tr>
                <td colspan="4">
                    <input type="submit" style="position:absolute;left:-999px;top:0;height:1px">
                </td>
            </tr>
        </table>
        <table cellspacing=0 class="threed" style="width:49.5%">
            <tr>
                <td class="threed" colspan=2>
                    <div style="height:281px;overflow:auto;border: 1px solid #C5C6C9;background-color:#FFF">
                        <table cellspacing=0 style="width: 50%;float: left;padding:2px">
                            <tr>
                                <td class="select_span">
                                    <input type="checkbox" name="category[]" value="0" style="display:none"><span class="ui-icon ui-icon-close" style="float:left"></span>!unassigned
                                </td>
                            </tr>
                            <?php
                            include_once 'functions.php';
                            database_connect($database_path, 'library');
                            $category_string = null;

                            $result3 = $dbHandle->query("SELECT count(*) FROM categories");
                            $totalcount = $result3->fetchColumn();
                            $result3 = null;

                            $i = 1;
                            $isdiv = null;
                            $result3 = $dbHandle->query("SELECT categoryID,category FROM categories ORDER BY category COLLATE NOCASE ASC");
                            while ($category = $result3->fetch(PDO::FETCH_ASSOC)) {
                                $cat_all[$category['categoryID']] = $category['category'];
                                if ($i > 1 && $i > ($totalcount / 2) && !$isdiv) {
                                    print '</table><table cellspacing=0 style="width: 50%;padding:2px">';
                                    $isdiv = true;
                                }
                                print PHP_EOL . '<tr><td class="select_span">';
                                print "<input type=\"checkbox\" name=\"category[]\" value=\"" . htmlspecialchars($category['categoryID']) . "\"";
                                print " style=\"display:none\"><span class=\"ui-icon ui-icon-close\" style=\"float:left\"></span>" . htmlspecialchars($category['category']) . "</td></tr>";
                                $i = $i + 1;
                            }
                            $result3 = null;
                            ?>
                        </table>
                    </div>
                </td>
            </tr>
            <td class="threed" style="width:6em">
                Match:
            </td>
            <td class="threed">
                <table border=0 cellspacing=0 cellpadding=0>
                    <tr>
                        <td class="select_span" style="line-height:16px">
                            <input type="checkbox" name="whole_words" value="1" style="display:none" <?php if (isset($_SESSION['session_whole_words']) && $_SESSION['session_whole_words'] == 1)
                            print ' checked' ?>>
                            <span class="ui-icon ui-icon-<?php print (isset($_SESSION['session_whole_words']) && $_SESSION['session_whole_words'] == 1) ? 'check' : 'close'  ?>" style="float:left">
                            </span>Whole words only&nbsp;
                        </td>
                        <td class="select_span" style="line-height:16px">
                            <input type="checkbox" name="case" value="1" style="display:none" <?php print (isset($_SESSION['session_case']) && $_SESSION['session_case'] == 1) ? 'checked' : ''  ?>>
                            <span class="ui-icon ui-icon-<?php print (isset($_SESSION['session_case']) && $_SESSION['session_case'] == 1) ? 'check' : 'close'  ?>" style="float:left">
                            </span>Match case&nbsp;
                        </td>
                    </tr>
                </table>
            </td></tr>
            <tr>
                <td class="threed">
                    Rating:
                </td>
                <td class="threed">
                    <table border=0 cellspacing=0 cellpadding=0>
                        <tr>
                            <td class="select_span" style="line-height:16px">
                                <input type="checkbox" name="rating[]" value="1" style="display:none" <?php if (!isset($_SESSION['session_rating']) || (isset($_SESSION['session_rating']) && in_array(1, $_SESSION['session_rating'])))
                               print ' checked' ?>>
                                <span class="ui-icon ui-icon-<?php print (!isset($_SESSION['session_rating']) || (isset($_SESSION['session_rating']) && in_array(1, $_SESSION['session_rating']))) ? 'check' : 'close'  ?>" style="float:left">
                                </span>Low&nbsp;
                            </td>
                            <td class="select_span" style="line-height:16px">
                                <input type="checkbox" name="rating[]" value="2" style="display:none" <?php if (!isset($_SESSION['session_rating']) || (isset($_SESSION['session_rating']) && in_array(2, $_SESSION['session_rating'])))
                                   print ' checked' ?>>
                                <span class="ui-icon ui-icon-<?php print (!isset($_SESSION['session_rating']) || (isset($_SESSION['session_rating']) && in_array(2, $_SESSION['session_rating']))) ? 'check' : 'close'  ?>" style="float:left">
                                </span>Medium&nbsp;
                            </td>
                            <td class="select_span" style="line-height:16px">
                                <input type="checkbox" name="rating[]" value="3" style="display:none" <?php if (!isset($_SESSION['session_rating']) || (isset($_SESSION['session_rating']) && in_array(3, $_SESSION['session_rating'])))
                                   print ' checked' ?>>
                                <span class="ui-icon ui-icon-<?php print (!isset($_SESSION['session_rating']) || (isset($_SESSION['session_rating']) && in_array(3, $_SESSION['session_rating']))) ? 'check' : 'close'  ?>" style="float:left">
                                </span>High&nbsp;
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <?php
            if (isset($_SESSION['auth'])) {
                ?>
                <tr>
                    <td class="threed">
                        Save as:
                    </td>
                    <td class="threed">
                        <input type="text" name="searchname" style="width:99.5%">
                    </td></tr>
                <?php
            }
            ?>
        </table>
    </form>
    <?php
}
?>