<?php
mb_language('uni'); mb_internal_encoding('UTF-8');

// A simple script to retrieve all proactive disclosure contract pages
// from the PWGSC website, and store them in an "output" folder.
// Depending on your folder permissions, you may have to create the
// "output" folder as a subdirectory before running this script.
// Estimated runtime is at least an hour on a home internet connection.

// This script only retrieves and stores the PWGSC contract pages,
// and doesn't do any actual scraping and analysis.

// toobs2017@gmail.com and the GoC-Spending team!

$urls = [
	'index' => 'http://www.tpsgc-pwgsc.gc.ca/cgi-bin/proactive/cl.pl?lang=eng&SCR=Q&Sort=0',
];

function simpleScraper($indexUrl, $startSplit, $endSplit, $prependString = '', $appendString = '') {

	$output = [];

	$pageSource = file_get_contents($indexUrl);

	$values = explode($startSplit, $pageSource);

	// Remove the first array value (the main part of the page source prior to the first table entry)
	array_shift($values);

	foreach($values as $value) {
		$valueUrl = explode($endSplit, $value);
		$output[] = $prependString . $valueUrl[0] . $appendString;
		// echo $valueUrl[0] . "\n";
	}

	return $output;

}

// Retrieve the original reports page that lists links to quarters
function getIndexPage($indexUrl) {

	$startSplit = 'http://www.tpsgc-pwgsc.gc.ca/cgi-bin/proactive/cl.pl';
	$endSplit = '">';

	$prependString = $startSplit;

	return simpleScraper($indexUrl, $startSplit, $endSplit, $prependString);


}


// Retrieve a "quarters" page that lists all contracts in that quarter document:
function getQuarterPage($quarterUrl) {

	$startSplit = '<td><a href="http://www.tpsgc-pwgsc.gc.ca/cgi-bin/proactive/cl.pl?';
	$endSplit = '" title';

	$prependString = 'http://www.tpsgc-pwgsc.gc.ca/cgi-bin/proactive/cl.pl?';

	return simpleScraper($quarterUrl, $startSplit, $endSplit, $prependString);


}

function downloadPage($url, $folder = 'output', $sleep = 0) {

	$pageSource = file_get_contents($url);
	$filename = md5($url) . '.html';
	$filepath = dirname(__FILE__) . '/' . $folder . '/' . $filename;

	// If that particular page has already been downloaded,
	// don't download it again.
	// That lets us re-start the script without starting from the very beginning again.
	if(! file_exists($filepath)) {
		file_put_contents($filepath, $pageSource);

		sleep($sleep);

		return true;
	}
	else {
		return false;
	}


}


// Run the operation!
echo "Starting at ". date('Y-m-d H:i:s') . " \n\n";
$startTime = microtime(true);


$urls['quarters'] = getIndexPage($urls['index']);

$totalDownloads = 0;

foreach($urls['quarters'] as $quarterUrl) {

	$contractPages = getQuarterPage($quarterUrl);

	echo "Downloading contract pages from $quarterUrl \n\n";

	foreach($contractPages as $contractPage) {

		downloadPage($contractPage);
		$totalDownloads++;

	}

}


echo "Finished at ". date('Y-m-d H:i:s') . " \n";
$timeDiff = microtime(true) - $startTime;
echo "$totalDownloads contract pages downloaded in $timeDiff seconds. \n\n";



