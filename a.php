<head>
    <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
</head>
<form action="#" method="post" style="text-align:center;margin-top: 150px;">
    <input class="form-control" type="text" placeholder="Paste yp.com search link" style="width: 30%;left: 35%;position: relative;" name="query" />
    <br>
    <input type="submit" class="btn btn-primary" value="Download results" name="submit_button" />
</form>
<?php

// require_once('simple_html_dom.php');


if(isset($_POST['submit_button']))
{
    // clear_prev_results();
    global $term, $searchloc;
    $url                = $_REQUEST['query'];
    $parts              = parse_url($url);
    parse_str($parts['query'], $query);
    $term     = $query['search_terms'];
    $searchloc   = $query['geo_location_terms'];
    // require_once 'download.php';
    $query_url = build_query();
    $total_calls = calculate_total_calls($query_url);
    for ($i=1; $i <= $total_calls ; $i++) {
    	get_results($i);
    }
}

function get_results($pagenum){
	$query_url = build_query($pagenum);
    $result = json_decode(doCall($query_url), true);
}

function build_query($pagenum = 1){
    global $term, $searchloc;
    $url_params['key']       = '7nlrj25t33';
    $url_params['listingcount']       = '50';
    $url_params['format']       = 'json';
    $url_params['term']       = $term;
    $url_params['searchloc']   = $searchloc;
    $url_params['pagenum']       =  $pagenum;
    $query_url = "http://pubapi.yp.com/search-api/search/devapi/search?".http_build_query($url_params);
    return $query_url;
}

function calculate_total_calls($query_url){
	// echo $query_url."<br>";
	$result = json_decode(doCall($query_url), true);
	// echo "<pre>";
	// print_r($result);
	// echo "</pre>";
	$req_total_call = 0;
	$total_count = $result['searchResult']['metaProperties']['totalAvailable'];
	echo "total_count ".$total_count."<br>";
	if (!empty($total_count)) {
		$loop_limit = $total_count / 50 ;
        $req_total_call = ceil($loop_limit);
	}
	return $req_total_call;
}

function doCall($URL) //Needs a timeout handler
{
    $SSLVerify = false;
    $URL = trim($URL);
    if(stripos($URL, 'https://') !== false){ $SSLVerify = true; }
    $ch = curl_init($URL);
    curl_setopt($ch, CURLOPT_URL, $URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 2);
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/43.0.2357.124 Safari/537.36');
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, ($SSLVerify === true) ? 2 : false );
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $SSLVerify);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HEADER, false);
    @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    $rawResponse      = curl_exec($ch);
    curl_close($ch);
    return $rawResponse;
}