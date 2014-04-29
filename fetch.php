<?php
include_once 'data.php';

$proxy_name = '';
$proxy_port = '';
$proxy_username = '';
$proxy_password = '';

    if (isset($_SESSION['connection']) && ($_SESSION['connection'] == "autodetect" || $_SESSION['connection'] == "url")) {
        if(!empty($_GET['proxystr'])) {
            $proxy_arr = explode (';', $_GET['proxystr']);
            foreach ($proxy_arr as $proxy_str) {
                if (stripos(trim($proxy_str), 'PROXY') === 0) {
                    $proxy_str = trim(substr ($proxy_str, 6));
                    $proxy_name = parse_url($proxy_str, PHP_URL_HOST);
                    $proxy_port = parse_url($proxy_str, PHP_URL_PORT);
                    $proxy_username = parse_url($proxy_str, PHP_URL_USER);
                    $proxy_password = parse_url($proxy_str, PHP_URL_PASS);
                    break;
                }
            }
        }
    } else {
        if(isset($_SESSION['proxy_name'])) $proxy_name = $_SESSION['proxy_name'];
        if(isset($_SESSION['proxy_port'])) $proxy_port = $_SESSION['proxy_port'];
        if(isset($_SESSION['proxy_username'])) $proxy_username = $_SESSION['proxy_username'];
        if(isset($_SESSION['proxy_password'])) $proxy_password = $_SESSION['proxy_password'];
    }

include_once 'functions.php';

##########	reference fetching from PubMed	##########

if (isset($_GET['id'])) {

    ##########	open efetch, read xml	##########

    $request_url = 'http://www.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi?db=Pubmed&rettype=abstract&retmode=XML&id='.urlencode($_GET['id']).'&tool=I,Librarian&email=i.librarian.software@gmail.com';

    $xml = proxy_simplexml_load_file ($request_url, $proxy_name, $proxy_port, $proxy_username, $proxy_password);

    if (empty($xml)) die('Error! I, Librarian could not connect with an external web service. This usually indicates that you access the Web through a proxy server.
    Enter your proxy details in Tools->Settings. Alternatively, the external service may be temporarily down. Try again later.');

    $xml_type = '';
    if (!empty($xml->PubmedBookArticle)) $xml_type= 'book';
    if (!empty($xml->PubmedArticle)) $xml_type= 'article';
    if (empty($xml_type)) die('Error');

    if ($xml_type == 'article') {

        foreach ($xml->PubmedArticle->PubmedData->ArticleIdList->ArticleId as $articleid) {

            foreach ($articleid->attributes() as $a => $b) {

                if ($a == 'IdType' && $b == 'doi') $doi = $articleid[0];
                if ($a == 'IdType' && $b == 'pmc') $pmcid = substr($articleid[0], 3);
            }
        }

        $pmid         	= $xml->PubmedArticle->MedlineCitation->PMID;

        $uid_array[] = "PMID:$pmid";
        if(isset($pmcid)) $uid_array[] = "PMCID:$pmcid";

        $url_array[] = "http://www.pubmed.org/$pmid";
        if(isset($pmcid)) $url_array[] = "http://www.ncbi.nlm.nih.gov/pmc/articles/PMC$pmcid/";

        $reference_type	= 'article';

        $title			= $xml->PubmedArticle->MedlineCitation->Article->ArticleTitle;

        $abstract_array = array();

        $xml_abstract = $xml->PubmedArticle->MedlineCitation->Article->Abstract->AbstractText;

        if(!empty($xml_abstract)) {
            foreach ($xml_abstract as $mini_ab) {
                foreach($mini_ab->attributes() as $a => $b) {
                    if ($a == 'Label') $mini_ab = $b.": ".$mini_ab;
                }
                $abstract_array[] = "$mini_ab";
            }
            $abstract = implode(' ', $abstract_array);
        }

        $secondary_title	= $xml->PubmedArticle->MedlineCitation->Article->Journal->Title;

        $day			= $xml->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->PubDate->Day;
        $month			= $xml->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->PubDate->Month;
        $year			= $xml->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->PubDate->Year;

        if (empty($year)) {
            $year = (string) $xml->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->PubDate->MedlineDate;
            preg_match ('/\d{4}/', $year, $year_match);
            $year = $year_match[0];
        }

        $date = '';
        if (!empty($year)) {
            if(empty($day)) $day = '01';
            if(empty($month)) $month = '01';
            $date = date('Y-m-d', strtotime($day.'-'.$month.'-'.$year));
        }

        $volume		= $xml->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->Volume;

        $issue			= $xml->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->Issue;

        $pages 		= $xml->PubmedArticle->MedlineCitation->Article->Pagination->MedlinePgn;

        $journal_abbr		= $xml->PubmedArticle->MedlineCitation->MedlineJournalInfo->MedlineTA;

        $affiliation		= $xml->PubmedArticle->MedlineCitation->Article->Affiliation;

        $authors		= $xml->PubmedArticle->MedlineCitation->Article->AuthorList->Author;

        if (!empty($authors)) {

            foreach ($authors as $author) {

                $name_array[] = $author->LastName.', '.$author->ForeName;
            }
        }

        $keywords		= $xml->PubmedArticle->MedlineCitation->MeshHeadingList->MeshHeading;

        if (!empty($keywords)) {

            foreach ($keywords as $keywordsheading) {

                $keywords_array[] = $keywordsheading->DescriptorName;
            }
        }

        if (isset($name_array)) $names = join ("; ", $name_array);
        if (isset($keywords_array)) $keywords = join (" / ", $keywords_array);
    }

    if ($xml_type == 'book') {

        $pmid         	= $xml->PubmedBookArticle->BookDocument->PMID;

        $uid_array[] = "PMID:$pmid";

        $url_array[] = "http://www.pubmed.org/$pmid";

        $reference_type	= 'book';

        $title			= $xml->PubmedBookArticle->BookDocument->ArticleTitle;
        if(empty($title)) $title = 'Title not available';
        $publisher		= $xml->PubmedBookArticle->BookDocument->Book->Publisher->PublisherName;
        $place_published	= $xml->PubmedBookArticle->BookDocument->Book->Publisher->PublisherLocation;

        $abstract_array = array();

        foreach ($xml->PubmedBookArticle->BookDocument->Abstract->AbstractText as $mini_ab) {

            foreach($mini_ab->attributes() as $a => $b) {
                if ($a == 'Label') $mini_ab = $b.": ".$mini_ab;
            }
            $abstract_array[] = "$mini_ab";
        }
        $abstract = implode(' ', $abstract_array);

        $secondary_title	= $xml->PubmedBookArticle->BookDocument->Book->BookTitle;

        $day			= $xml->PubmedBookArticle->BookDocument->Book->PubDate->Day;
        $month			= $xml->PubmedBookArticle->BookDocument->Book->PubDate->Month;
        $year			= $xml->PubmedBookArticle->BookDocument->Book->PubDate->Year;

        $date = '';
        if (!empty($year)) {
            if(empty($day)) $day = '01';
            if(empty($month)) $month = '01';
            $date = date('Y-m-d', strtotime($day.'-'.$month.'-'.$year));
        }

        $authors		= $xml->PubmedBookArticle->BookDocument->AuthorList->Author;

        if (!empty($authors)) {

            foreach ($authors as $author) {

                $name_array[] = $author->LastName.', '.$author->ForeName;
            }
        }

        if (isset($name_array)) $names = join ("; ", $name_array);
        if (isset($keywords_array)) $keywords = join (" / ", $keywords_array);
    }

    ##########	print results into table	##########

    print '<form enctype="application/x-www-form-urlencoded" action="upload.php" method="POST" class="fetch-form">';

    print '<table cellspacing="0" width="100%"><tr><td class="items">';

    print '<div>';
    if (!empty($journal_abbr)) print htmlspecialchars($journal_abbr);
    if (empty($journal_abbr) && !empty($secondary_title)) print htmlspecialchars($secondary_title);
    if (!empty($date)) print " (".htmlspecialchars($date).")";
    if (!empty($volume)) print " ".htmlspecialchars($volume);
    if (!empty($issue)) print " ($issue)";
    if (!empty($pages)) print ": ".htmlspecialchars($pages);
    print '</div>';

    if (!empty($names)) {
        print '<div class="authors"><span class="author_expander ui-icon ui-icon-plus" style="float:left"></span><div>'.htmlspecialchars($names).'</div></div>';
        $array = explode(';', $names);
        $array = array_filter($array);
        if (!empty($array)) {
            foreach ($array as $author) {
                $array2 = explode(',', $author);
                $last = trim($array2[0]);
                $first = trim($array2[1]);
                $new_authors[] = 'L:"'.$last.'",F:"'.$first.'"';
            }
            $names = join(';', $new_authors);
        }
    }

    if (!empty($affiliation)) print '<div class="authors"><span class="author_expander ui-icon ui-icon-plus" style="float:left"></span><div>'.htmlspecialchars($affiliation).'</div></div>';

    print '</td></tr>';

    print '<tr><td><div class="abstract" style="padding:0 10px">';

    !empty($abstract) ? print htmlspecialchars($abstract) : print 'No abstract available.';

    print '</div></td></tr><tr><td class="items">';

    foreach ($uid_array as $uid) {
        print '<input type="hidden" name="uid[]" value="'.htmlspecialchars($uid).'">';
    }
    foreach ($url_array as $url) {
        print '<input type="hidden" name="url[]" value="'.htmlspecialchars($url).'">';
    }
    ?>
<input type="hidden" name="doi" value="<?php if (!empty($doi)) print htmlspecialchars($doi); ?>">
<input type="hidden" name="reference_type" value="<?php if (!empty($reference_type)) print htmlspecialchars($reference_type); ?>">
<input type="hidden" name="authors" value="<?php if (!empty($names)) print htmlspecialchars($names); ?>">
<input type="hidden" name="affiliation" value="<?php if (!empty($affiliation)) print htmlspecialchars($affiliation); ?>">
<input type="hidden" name="title" value="<?php if (!empty($title)) print htmlspecialchars($title); ?>">
<input type="hidden" name="secondary_title" value="<?php if (!empty($secondary_title)) print htmlspecialchars($secondary_title); ?>">
<input type="hidden" name="journal_abbr" value="<?php if (!empty($journal_abbr)) print htmlspecialchars($journal_abbr); ?>">
<input type="hidden" name="year" value="<?php if (!empty($year)) print htmlspecialchars($date); ?>">
<input type="hidden" name="volume" value="<?php if (!empty($volume)) print htmlspecialchars($volume); ?>">
<input type="hidden" name="issue" value="<?php if (!empty($issue)) print htmlspecialchars($issue); ?>">
<input type="hidden" name="pages" value="<?php if (!empty($pages)) print htmlspecialchars($pages); ?>">
<input type="hidden" name="keywords" value="<?php if (!empty($keywords)) print htmlspecialchars($keywords); ?>">
<input type="hidden" name="abstract" value="<?php print !empty($abstract) ? htmlspecialchars($abstract) : "No abstract available."; ?>">
<input type="hidden" name="publisher" value="<?php print !empty($publisher) ? htmlspecialchars($publisher) : ""; ?>">
<input type="hidden" name="place_published" value="<?php print !empty($place_published) ? htmlspecialchars($place_published) : ""; ?>">
<input type="hidden" name="form_new_file_link" value="<?php print !empty($pmcid) ? htmlspecialchars("http://www.ncbi.nlm.nih.gov/pmc/articles/PMC".$pmcid."/pdf") : ""; ?>">

    <?php
    ##########	print full text links	##########

    print '<b>Full text options:</b><br>
	<a href="'.htmlspecialchars("http://eutils.ncbi.nlm.nih.gov/entrez/eutils/elink.fcgi?dbfrom=pubmed&id=".urlencode($_GET['id'])."&retmode=ref&cmd=prlinks&tool=I,Librarian&email=i.librarian.software@gmail.com").'" target="_blank">
	PubMed LinkOut
	</a>';

    if (!empty($pmcid)) print ' <b>&middot;</b> <a href="'.htmlspecialchars("http://www.ncbi.nlm.nih.gov/pmc/articles/PMC$pmcid/pdf/").'" target="_blank">
	Full Text PDF</a> (PubMed Central)';

    if (!empty($doi)) print ' <b>&middot;</b> <a href="'.htmlspecialchars("http://dx.doi.org/".urlencode($doi)).'" target="_blank">Publisher Website</A>';

    print '<br><button class="save-item">Save</button> <button class="quick-save-item">Quick Save</button>';

    print '</td></tr></table>';
    ?>
</form>
    <?php
}	##########	reference fetching from PubMed	##########
?>