<?php
// make sure browsers see this page as utf-8 encoded HTML
ini_set('memory_limit', '8192M');
header('Content-Type: text/html; charset=utf-8');
$limit = 10;
$query = isset($_REQUEST['q']) ? $_REQUEST['q'] : false;
$results = false;
if ($query)
{
 
    require_once('solr-php-client-master/Apache/Solr/Service.php');
    require_once('SpellCorrector.php');
    $solr = new Apache_Solr_Service('localhost', 8983, '/solr/wsjSearchEngine/');    
    $corrector = SpellCorrector::correct($query);
    $finalQuery = "";
    if ($corrector == $query) {
        $corrector = "";
        $finalQuery = $query;
    } else {
        $finalQuery = $corrector;
    }
    if (get_magic_quotes_gpc() == 1) {
        $query = stripslashes($query);
    }
    // in production code you'll always want to use a try /catch for any
    // possible exceptions emitted by searching (i.e. connection
    // problems or a query parsing error)
    $param = [
        'hl' => 'on',
        'hl.fl' => '*',
        'hl.simple.post' => '</b>',
        'hl.simple.pre' => '<b>',
    ];
    try {
        if($_REQUEST['searchtype']=="external") {
            $param['sort'] = 'pageRankFile desc';
        }
        $results = $solr->search($finalQuery, 0, $limit, $param);
    }
    catch (Exception $e) {
        die("<html><head><title>SEARCH EXCEPTION</title><body><pre>{$e}</pre></body></html>");
    }
}


//start reading csv

$num;
$CSVfp = fopen("WSJmap.csv", "r");
if($CSVfp !== FALSE) {
    while(! feof($CSVfp)) {
        $data = fgetcsv($CSVfp, 1000, ",");
        $num[$data[0]] = $data[1];
    }
}
fclose($CSVfp);

$counter=0;
foreach ($num as $key => $value) {
    $counter++;
}

//end of reading csv
?>
<html>
    <head>
        <title>PHP Solr Client</title>
        <link rel="stylesheet" href="http://code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
        <script src="https://code.jquery.com/jquery-3.2.1.min.js" integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4=" crossorigin="anonymous"></script>
        <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js" integrity="sha256-VazP97ZCwtekAsvgPBSUwPFKdrwD3unUfSGVYrahUqU=" crossorigin="anonymous"></script>
        <script src="index.js"></script>
    </head>
    <body>
        <form accept-charset="utf-8" method="get">
            <label for="q">Search:</label>
            <input id="q" name="q" type="text" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'utf-8'); ?>"/>
            <br>
            <input type="radio" name="searchtype" <?php if (isset($_GET['searchtype']) && $_GET['searchtype']=="inbuilt") echo "checked";?> value="inbuilt" checked />solr default search
            <input type="radio" name="searchtype" <?php if (isset($_GET['searchtype']) && $_GET['searchtype']=="external") echo "checked";?> value="external" /> external pagerank search<br>
            <input type="submit"/>
        </form>
<?php
// display results
if ($results)
{
 $total = (int) $results->response->numFound;
 $start = min(1, $total);
 $end = min($limit, $total);
?>
        <?php if ($corrector != "") { ?>
            <p>Including Results for: <a href="http://localhost/cs572/index.php?q=<?php echo $corrector; ?>"><?php echo $corrector; ?></a></p>
            <p>Show only for: <a href="http://localhost/cs572/index.php?q=<?php echo $query; ?>"><?php echo $query; ?></a></p>
        <?php } ?>
            
        
        <div>Results <?php echo $start; ?> - <?php echo $end;?> of <?php echo $total; ?>:</div>
        <ol>
<?php
 // iterate result documents
    $fileName = "";
    $resNum = 0;
 foreach ($results->response->docs as $doc)
 {
             
            $id = $doc->id;
            $url = $doc->og_url;
            $pos=strripos($id,"/");
            $fileName=substr($id,$pos+1);
            if ($url == "" || $url == "None") {
                
                $url=$num[$fileName];
            }
            $title = $doc->title;
            $desc = $doc->description;
?>
            <p>
                <a href="<?php echo htmlspecialchars($url, ENT_NOQUOTES, 'utf-8'); ?>" target="_blank">
                    <?php echo htmlspecialchars($title, ENT_NOQUOTES, 'utf-8'); ?>
                </a>
            </p>
            <p>
                <a href="<?php echo htmlspecialchars($url, ENT_NOQUOTES, 'utf-8'); ?>" target="_blank">
                    <?php echo htmlspecialchars($url, ENT_NOQUOTES, 'utf-8'); ?>
                </a>
            </p>
            <p>   
                <?php echo htmlspecialchars($id, ENT_NOQUOTES, 'utf-8'); ?>
            </p>
            <p>
                <?php echo htmlspecialchars($desc, ENT_NOQUOTES, 'utf-8'); ?>
            </p>
            <p name="snippets" >
                <?php 
                    echo "<script>";
                    echo "getSnippets('$finalQuery', '$fileName',  '$resNum');";
                    echo "</script>";
                ?>
                <?php //echo htmlspecialchars($snippet, ENT_NOQUOTES, 'utf-8'); ?>
            </p>
            <hr>
<?php
            $resNum = $resNum + 1;
} //end for loop
?>
            </ol>
        <?php
} //end if statement
?>
    </body>
</html>
