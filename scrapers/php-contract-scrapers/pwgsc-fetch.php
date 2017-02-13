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


// General fetcher configuration (across all departments)
// See below for department-specific URLs and text splitting
class Configuration {
	
	public static $limitQuarters = 2;
	public static $limitContractsPerQuarter = 2;
	public static $sleepBetweenDownloads = 0;
	public static $redownloadExistingFiles = 0;
	public static $outputFolder = 'output';

}

// Per-department fetcher class
class DepartmentFetcher
{
	public $ownerAcronym;
	public $indexUrl;
	public $indexSplitParameters;

	public $quarterSplitParameters;

	public $quarterUrls;
	public $contractUrls;
	
	public $totalContractsFetched = 0;

	// Initialize new instances:
	function __construct($detailsArray) {

		$this->ownerAcronym = $detailsArray['ownerAcronym'];
		$this->indexUrl = $detailsArray['indexUrl'];
		$this->indexSplitParameters = $detailsArray['indexSplitParameters'];
		$this->quarterSplitParameters = $detailsArray['quarterSplitParameters'];
	
	}

	// Generic scraper function
	// Retrieves a page based on the specified parameters, and splits it according to the requested start and end
	public static function simpleScraper($indexUrl, $startSplit, $endSplit, $prependString = '', $appendString = '') {

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

	// Generic page download function
	// Downloads the requested URL and saves it to the specified directory
	// If the same URL has already been downloaded, it avoids re-downloading it again.
	// This makes it easier to stop and re-start the script without having to go from the very beginning again.
	public static function downloadPage($url, $subdirectory = '') {

		$pageSource = file_get_contents($url);
		$filename = md5($url) . '.html';
		$directoryPath = dirname(__FILE__) . '/' . Configuration::$outputFolder;

		if($subdirectory) {
			$directoryPath .= '/' . $subdirectory;
		}

		// If the folder doesn't exist yet, create it:
		// Thanks to http://stackoverflow.com/a/15075269/756641
		if(! is_dir($directoryPath)) {
			mkdir($directoryPath, 0755, true);
		}

		// If that particular page has already been downloaded,
		// don't download it again.
		// That lets us re-start the script without starting from the very beginning again.
		if(file_exists($filepath) == false || Configuration::$redownloadExistingFiles) {

			file_put_contents($directoryPath . '/' . $filename, $pageSource);

			// Optionally sleep for a certain amount of time (eg. 0.1 seconds) in between fetches to avoid angry sysadmins:
			if(Configuration::$sleepBetweenDownloads) {
				sleep(Configuration::$sleepBetweenDownloads);
			}
			
			return true;

		}
		else {
			return false;
		}


	}

	// Retrieve the original reports index page, which lists links to fiscal quarter report pages:
	public function fetchIndexPage() {

		return self::simpleScraper($this->indexUrl, $this->indexSplitParameters['startSplit'], $this->indexSplitParameters['endSplit'], $this->indexSplitParameters['prependString']);


	}


	// Retrieve a "quarters" page that lists all contracts in that fiscal quarter:
	public function fetchQuarterPage($quarterUrl) {

		return self::simpleScraper($quarterUrl, $this->quarterSplitParameters['startSplit'], $this->quarterSplitParameters['endSplit'], $this->quarterSplitParameters['prependString']);


	}


	// This is the main "go" function.
	// It calls the functions above and then downloads all of the individual contract pages one at a time.
	// This could take more than an hour per department, depending on network speed and number of contract pages:
	public function fetchContracts() {

		// Run the operation!
		echo "Starting " . $this->ownerAcronym . " at ". date('Y-m-d H:i:s') . " \n\n";
		$startTime = microtime(true);

		// 1. Get all the URLs of the "quarters" pages from the index page:
		$this->quarterUrls = $this->fetchIndexPage();

		// 2. Loop through each of the URLs for the quarters pages, and retrieve the URLs for all of the contract pages:
		$quartersFetched = 0;
		foreach($this->quarterUrls as $quarterUrl) {

			if($quartersFetched >= Configuration::$limitQuarters) {
				break;
			}
			
			// 2a. Retrieve the quarter page itself:
			$contractPages = $this->fetchQuarterPage($quarterUrl);

			echo "Downloading contract pages from \n$quarterUrl \n";

			// 2b. Loop through each of the contract URLs on that quarter page, and download each page:
			$contractsFetched = 0;
			foreach($contractPages as $contractPage) {

				if($contractsFetched >= Configuration::$limitContractsPerQuarter) {
					break;
				}

				$this->contractUrls[] = $contractPage;

				self::downloadPage($contractPage, $this->ownerAcronym);
				$this->totalContractsFetched++;

				$contractsFetched++;

			}

			echo "$contractsFetched pages downloaded for this quarter.\n\n";

			$quartersFetched++;

		}


		echo "Finished " . $this->ownerAcronym . " at ". date('Y-m-d H:i:s') . " \n";
		$timeDiff = microtime(true) - $startTime;
		echo $this->totalContractsFetched . " " . $this->ownerAcronym . " contract pages downloaded, across $quartersFetched fiscal quarters, in $timeDiff seconds. \n\n";

	}



}


// Store all the DepartmentFetcher instances in an array, in case we wanted to do some kind of bulk operations in the future:
$departments = [];

// Determine the starting URL and "explode"-based string parsing for a specific department's pages, in this case, PWGSC:
$departments['pwgsc'] = new DepartmentFetcher([
	'ownerAcronym' => 'pwgsc',
	'indexUrl' => 'http://www.tpsgc-pwgsc.gc.ca/cgi-bin/proactive/cl.pl?lang=eng&SCR=Q&Sort=0',

	'indexSplitParameters' => [
		'startSplit' => 'http://www.tpsgc-pwgsc.gc.ca/cgi-bin/proactive/cl.pl',
		'endSplit' => '">',
		'prependString' => 'http://www.tpsgc-pwgsc.gc.ca/cgi-bin/proactive/cl.pl',
	],

	'quarterSplitParameters' => [
		'startSplit' => '<td><a href="http://www.tpsgc-pwgsc.gc.ca/cgi-bin/proactive/cl.pl?',
		'endSplit' => '" title',
		'prependString' => 'http://www.tpsgc-pwgsc.gc.ca/cgi-bin/proactive/cl.pl?',
	],
]);



// Run the fetchContracts method:
$departments['pwgsc']->fetchContracts();

// No return output is needed, since it saves the files directly, and outputs logging information to the console when run.

// Rock on!
