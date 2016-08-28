<head>
    <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
</head>
<form action="#" method="post" style="text-align:center;margin-top: 150px;">
    <input class="form-control" type="text" placeholder="Paste yp.com search link" style="width: 30%;left: 35%;position: relative;" name="query" />
    <br>
    <input type="submit" class="btn btn-primary" value="Download results" name="submit_button" />
</form>
<?php

require_once('simple_html_dom.php');


if(isset($_POST['submit_button']))
{
    clear_prev_results();
    global $term, $searchloc;
    $url                = $_REQUEST['query'];
    $parts              = parse_url($url);
    parse_str($parts['query'], $query);
    $term     = $query['search_terms'];
    $searchloc   = $query['geo_location_terms'];
    // require_once 'download.php';
    $query_url = build_query();
    $total_calls = calculate_total_calls($query_url);
    write_headers_csv_file();
    for ($i=1; $i <= $total_calls ; $i++) {
    	get_results($i);
    	flush();
    	sleep(2);
    	// die();
    }
    require_once 'download.php';
}

function clear_prev_results(){
    $file = getcwd().'/results.csv';
    $fp = fopen( $file ,'w');
}


function write_headers_csv_file(){
    $list = array (
        array('Name', 'Phone', 'Website', 'Email' ,'Address', 'Average Rating', 'Categories','Location latitude', 'Location longitude', 'moreInfoURL', 'primaryCategory'),
    );

    $file = getcwd().'/results.csv';
    $fp = fopen( $file ,'w');

    foreach ($list as $fields) {
        fputcsv($fp, $fields);
    }

    fclose($fp);

}

function get_website_from_link($link){
    sleep(2);
    $html = str_get_html(doCall($link));
    $website = false;
    $email = false;
    if (empty($html)) {
        echo "No Website Found <hr>";
        return false;
    }
    foreach($html->find('.bottom-section footer a') as $e){
        $innertext =  $e->innertext;
        if (stripos($innertext, 'website') !== FALSE) {
        	$website = $e->href;
        } else if (stripos($innertext, 'Email') !== FALSE) {
        	$email = str_replace('mailto:', '', $e->href);
        }
    }
    // echo $website."<br>";
    // echo $email."<br>";
    $result['website'] = $website;
    $result['email'] = $email;
    return $result;
}

function get_email_from_site($website){
    echo "Parsing email from main page...<br />";
    if (stripos($website, 'http') === FALSE) {
        $website = 'http://'.$website;
    }
    echo "Website :" . $website."<br>";
    sleep(1);
    $email = parse_email($website);
    if (empty($email)) {
        echo "Deep searching email ...<br />";
        $email = deep_email_search($website);
    }
    if ($email) {
        echo "Email : ".$email . "<br/>";
    } else {
        echo "Email : Not found <br/>";
    }
    return $email;
}

function parse_email($link){
	echo "Parsing : ".$link."<br>";
    $text = doCall($link);
    if (!empty($text)) {
        $res = preg_match_all(
            "/[a-z0-9]+([_\\.-][a-z0-9]+)*@([a-z0-9]+([\.-][a-z0-9]+)*)+\\.[a-z]{2,}/i",
            $text,
            $matches
        );
        if ($res) {
            foreach(array_unique($matches[0]) as $email) {
                return $email;
            }
        }
        else {
            return false;
        }
    }
}

function deep_email_search($website){
    echo "Parsing other pages...<br />";
    sleep(2);
    $html = str_get_html(doCall($website));
    $email = false;
    if (empty($html)) {
        return false;
    }
    $email = false;
    foreach($html->find('a') as $e){
        if (stripos($e->href, 'contact') !== FALSE || stripos($e->href, 'about') !== FALSE || stripos($e->href, 'impressum') !== FALSE) {
        	sleep(3);
        	$deep_link = make_valid_url($website, $e->href);
            $email = parse_email($deep_link);
            if ($email) {
                break;
            }
        }
    }
    return $email;
}

function make_valid_url($website, $deep_link){
	if (stripos($deep_link, 'http') !== FALSE) {
		echo "valid URL :".$deep_link."</br>";
		return $deep_link;
	}
	$parsed_url = parse_url($deep_link);
	if (empty($parsed_url['host'])) {
		$link = addTrailingSlash($website).removeBeginningSlash($deep_link);
		echo "Formed valid URL :".$link."</br>";
		return $link;
	}
}

function addTrailingSlash($string) {
	return removeTrailingSlash($string) . '/';
}

function removeTrailingSlash($string) {
	return rtrim($string, '/');
}

function removeBeginningSlash($string) {
	return ltrim($string, '/');
}

function get_results($pagenum){
	sleep(3);
	$query_url = build_query($pagenum);
    $results = json_decode(doCall($query_url), true);
    // print_r_custom($result);
    // die();
    $limit = 0;
    foreach ($results['searchResult']['searchListings']['searchListing'] as $key => $result) {
    	get_all_results($result);
    	flush();
    	sleep(2);
    	// if (++$limit > 4 ) {
    	// 	break;
    	// }
	}
}

function get_all_results($result){
	$scraped_data = array();
	$website = '';
	$email = '';
	if(isset($result['businessName']) && !empty($result['businessName']) ){
		$scraped_data[] = $result['businessName'];
		echo "Name : ".$result['businessName']."<br>";
	} else {
		$scraped_data[] = '';
	}
	if(isset($result['phone']) && !empty($result['phone']) ){
		$scraped_data[] = $result['phone'];
		echo "phone : ".$result['phone']."<br>";
	} else {
		$scraped_data[] = '';
	}
	$extra_result = get_website_from_link($result['moreInfoURL']);
	if ($extra_result['website']) {
		$website = $extra_result['website'];
		if ($extra_result['email']) {
			$email = $extra_result['email'];
		} else {
			$email = get_email_from_site($extra_result['website']);
		}
	} else {
		$website = '';
		if ($extra_result['email']) {
			$email = $extra_result['email'];
		} else {
			$email = '';
		}
	}
	$scraped_data[] = $website;
	$scraped_data[] = $email;
	echo "Website: ".$website."<br>" ;
	echo "email: ".$email."<hr>" ;
	$scraped_data[] = $result['street'].' '.$result['city'].' '.$result['state'].' '.$result['zip'];
	if(isset($result['averageRating']) && !empty($result['averageRating']) ){
		$scraped_data[] = $result['averageRating'];
	} else {
		$scraped_data[] = '';
	}
	
	if(isset($result['categories']) && !empty($result['categories']) ){
		$scraped_data[] = $result['categories'];
	} else {
		$scraped_data[] = '';
	}
	if(isset($result['latitude']) && !empty($result['latitude']) ){
		$scraped_data[] = $result['latitude'];
	} else {
		$scraped_data[] = '';
	}
	if(isset($result['longitude']) && !empty($result['longitude']) ){
		$scraped_data[] = $result['longitude'];
	} else {
		$scraped_data[] = '';
	}
	if(isset($result['moreInfoURL']) && !empty($result['moreInfoURL']) ){
		$scraped_data[] = $result['moreInfoURL'];
	} else {
		$scraped_data[] = '';
	}
	if(isset($result['primaryCategory']) && !empty($result['primaryCategory']) ){
		$scraped_data[] = $result['primaryCategory'];
	} else {
		$scraped_data[] = '';
	}
	write_into_csv_file($scraped_data);
	// print_r_custom($scraped_data);
	// die();
}

function write_into_csv_file($data){
    $file = getcwd().'/results.csv';
    $fp = fopen( $file ,'a+');
    // echo "<pre>";
    // print_r($data);
    // echo "</pre>";
    // foreach ($data as $fields) {
    //     echo "<pre>";
    // print_r($fields);
    // echo "</pre>";
    fputcsv($fp, $data);
    // }
    fclose($fp);
}

function print_r_custom($result){
	echo "<pre>";
	print_r($result);
	echo "</pre>";
}

function check_website_exist($result){
	return (isset($result['websiteURL']) && !empty($result['websiteURL']) ) ? $result['websiteURL'] : false;
}

function check_email_exist($result){
	return (isset($result['email']) && !empty($result['email']) ) ? $result['email'] : false;
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
	echo "total_count : ".$total_count."<br>";
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