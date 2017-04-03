<?php
// A simple script to retrieve all proactive disclosure contract pages
// from the PWGSC website, and store them in an "output" folder.
// Depending on your folder permissions, you may have to create the
// "output" folder as a subdirectory before running this script.
// Estimated runtime is at least an hour on a home internet connection.

// This script only retrieves and stores the PWGSC contract pages,
// and doesn't do any actual scraping and analysis.

// toobs2017@gmail.com and the GoC-Spending team!

// Sample background usage:
// php scrapers/contracts-scraper.php > scraper-results.log 2>&1 &

// Require Guzzle, via composer package
// Note that the vendor directory is one level up
require dirname(__FILE__) . '/../vendor/autoload.php';
use GuzzleHttp\Client;

// These aren't required in PHP 7+
if(function_exists('mb_language')) {
	mb_language('uni'); mb_internal_encoding('UTF-8');
}

class Configuration {

	public static $rawHtmlFolder = 'contracts';
	
	public static $jsonOutputFile = 'contracts-output.json';

	public static $limitDepartments = 1;
	public static $limitFiles = 0;

}

class DepartmentParser {

	public $acronym;
	public $contracts;

	public static $rowParams = [
			'uuid' => '',
			'vendorName' => '',
			'referenceNumber' => '',
			'contractDate' => '',
			'description' => '',
			'contractPeriodStart' => '',
			'contractPeriodEnd' => '',
			'startYear' => '',
			'endYear' => '',
			'deliveryDate' => '',
			'originalValue' => '',
			'contractValue' => '',
			'comments' => '',
			'ownerAcronym' => '',
			'sourceYear' => '',
			'sourceQuarter' => '',
			'sourceFilename' => '',
			'sourceURL' => '',
			'amendedValues' => [],
		];

	function __construct($acronym) {

		$this->acronym = $acronym;

	}

	public static function getSourceDirectory($acronym = false) {

		if($acronym) {
			return dirname(__FILE__) . '/' . Configuration::$rawHtmlFolder . '/' . $acronym;
		}
		else {
			return dirname(__FILE__) . '/' . Configuration::$rawHtmlFolder;
		}

	}

	public static function cleanupDate($dateInput) {

		// 11/1/2013
		$time = strtotime($dateInput);
		if($time) {
			return $time;
		}
		else {
			dd("Date cleanup error: '$dateInput'");
			return false;
		}
		


	}

	public static function dateToYear($dateInput) {

		$time = self::cleanupDate($dateInput);
		if($time) {
			return date('Y', $time);
		}
		else {
			return false;
		}

	}

	public static function cleanupContractValue($input) {

		$output = str_replace(['$', ','], '', $input);
		return floatval($output);

	}

	public static function cleanParsedArray(&$values) {

		$values['startYear'] = self::dateToYear($values['contractPeriodStart']);
		$values['endYear'] = self::dateToYear($values['contractPeriodEnd']);

		$values['originalValue'] = self::cleanupContractValue($values['originalValue']);
		$values['contractValue'] = self::cleanupContractValue($values['contractValue']);
	}

	public function parseDepartment() {

		$sourceDirectory = self::getSourceDirectory($this->acronym);

		$validFiles = [];
		$files = array_diff(scandir($sourceDirectory), ['..', '.']);

		foreach($files as $file) {
			// Check if it ends with .html
			$suffix = '.html';
			if(substr_compare( $file, $suffix, -strlen( $suffix )) === 0) {
				$validFiles[] = $file;
			}
		}

		$filesParsed = 0;
		foreach($validFiles as $file) {
			if(Configuration::$limitFiles && $filesParsed >= Configuration::$limitFiles) {
				break;
			}

			// Retrieve the values from the department-specific file parser
			// And merge these with the default values
			// Just to guarantee that all the array keys are around:
			$fileValues = array_merge(self::$rowParams, $this->parseFile($file));

			self::cleanParsedArray($fileValues);
			// var_dump($fileValues);

			$fileValues['ownerAcronym'] = $this->acronym;

			// Useful for troubleshooting:
			$fileValues['sourceFilename'] = $this->acronym . '/' . $file;


			$referenceNumber = $fileValues['referenceNumber'];
			// If the row already exists, update it
			// Otherwise, add it
			if(isset($this->contracts[$referenceNumber])) {
				echo "Updating $referenceNumber\n";

				// Because we don't have a year/quarter for all organizations, let's use the largest contractValue for now:
				$existingContract = $this->contracts[$referenceNumber];
				if($fileValues['contractValue'] > $existingContract['contractValue']) {
					$this->contracts[$referenceNumber] = $fileValues;
				}

				// Add entries to the amendedValues array
				// If it's the first time, add the original too
				if($existingContract['amendedValues']) {
					$this->contracts[$referenceNumber]['amendedValues'] = array_merge($existingContract['amendedValues'], [$fileValues['contractValue']]);
				}
				else {
					$this->contracts[$referenceNumber]['amendedValues'] = [
						$existingContract['contractValue'],
						$fileValues['contractValue'],
					];
				}

			} else {
				// Add to the contracts array:
				$this->contracts[$referenceNumber] = $fileValues;
			}

			$filesParsed++;

		}
		// var_dump($validFiles);

	}

	public function parseFile($filename) {

		$acronym = $this->acronym;

		if(! method_exists('FileParser', $acronym)) {
			echo 'Cannot find matching FileParser for ' . $acronym . "\n";
			return false;
		}

		$source = file_get_contents(self::getSourceDirectory($this->acronym) . '/' . $filename);

		return FileParser::$acronym($source);


	}

	public static function getDepartments() {

		$output = [];
		$sourceDirectory = self::getSourceDirectory();


		$departments = array_diff(scandir($sourceDirectory), ['..', '.']);

		// Make sure that these are really directories
		// This could probably done with some more elegant array map function
		foreach($departments as $department) {
			if(is_dir(dirname(__FILE__) . '/' . Configuration::$rawHtmlFolder . '/' . $department)) {
				$output[] = $department;
			}
		}

		return $output;

	}

	public static function parseAllDepartments() {

		// Run the operation!
		$startTime = microtime(true);

		// Question of the day is... how big can PHP arrays get?
		$output = [];

		$departments = DepartmentParser::getDepartments();

		$departmentsParsed = 0;
		foreach($departments as $acronym) {

			if(Configuration::$limitDepartments && $departmentsParsed >= Configuration::$limitDepartments) {
				break;
			}

			$startDate = date('Y-m-d H:i:s');
			echo "Starting " . $acronym . " at ". $startDate . " \n";

			$department = new DepartmentParser($acronym);

			$department->parseDepartment();

			// var_dump($department->contracts);
			$output[$acronym] = $department->contracts;

			echo "Started " . $acronym . " at " . $startDate . "\n";
			echo "Finished at ". date('Y-m-d H:i:s') . " \n\n";

			$departmentsParsed++;

		}

		file_put_contents(dirname(__FILE__) . '/' . Configuration::$jsonOutputFile, json_encode($output, JSON_PRETTY_PRINT));

	}




}

class FileParser {

	// Fun with Regular Expressions
	// ([A-Z])\w+
	public static function agr($html) {

		$values = [];
		$keyToLabel = [
			'vendorName' => 'Vendor Name:',
			'referenceNumber' => 'Reference Number:',
			'contractDate' => 'Contract Date:',
			'description' => 'Description Of Work:',
			'contractPeriodStart' => '',
			'contractPeriodEnd' => '',
			'contractPeriodRange' => 'Contract Period:',
			'deliveryDate' => '',
			'originalValue' => 'Original Contract Value:',
			'contractValue' => 'Contract Value:',
			'comments' => 'Comments:',
		];
		$labelToKey = array_flip($keyToLabel);

		$matches = [];
		$pattern = '/<th scope="row">([\w-@$#%^&+.,;:\s]*)<\/th><td>([\w-@$#%^&+.,;:\s]*)<\/td>/';

		preg_match_all($pattern, $html, $matches, PREG_SET_ORDER);

		// var_dump($matches);

		foreach($matches as $match) {

			$label = $match[1];
			$value = $match[2];

			if(array_key_exists($label, $labelToKey)) {

				$values[$labelToKey[$label]] = $value;

			}

		}

		// Change the "to" range into start and end values:
		if(isset($values['contractPeriodRange']) && $values['contractPeriodRange']) {
			$split = explode(' to ', $values['contractPeriodRange']);
			$values['contractPeriodStart'] = $split[0];
			$values['contractPeriodEnd'] = $split[1];
		}

		return $values;

	}

}

DepartmentParser::parseAllDepartments();
