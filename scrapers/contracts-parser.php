<?php
// A simple script to retrieve all proactive disclosure contract pages
// from the PWGSC website, and store them in an "output" folder.
// Depending on your folder permissions, you may have to create the
// "output" folder as a subdirectory before running this script.
// Estimated runtime is at least an hour on a home internet connection.

// This script retrieves the contracts downloaded by contract-scraper.php
// And parses the data values contained in their HTML tables.
// A future update should merge these together, to do both operations in one pass.

// toobs2017@gmail.com and the GoC-Spending team!

require('contracts-helpers.php');

// Go crazy!
ini_set('memory_limit', '512M');

class Configuration {

	public static $rawHtmlFolder = 'contracts';
	
	public static $jsonOutputFolder = 'generated-data';
	
	public static $departmentsToSkip = [
		// 'agr',
		// 'csa',
		// 'fin',
		// 'ic',
	];

	public static $limitDepartments = 0;
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

	

	public static function cleanParsedArray(&$values) {

		$values['startYear'] = Helpers::dateToYear($values['contractPeriodStart']);
		$values['endYear'] = Helpers::dateToYear($values['contractPeriodEnd']);

		$values['originalValue'] = Helpers::cleanupContractValue($values['originalValue']);
		$values['contractValue'] = Helpers::cleanupContractValue($values['contractValue']);
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

			if($fileValues) {

				self::cleanParsedArray($fileValues);
				// var_dump($fileValues);

				$fileValues['ownerAcronym'] = $this->acronym;

				// Useful for troubleshooting:
				$fileValues['sourceFilename'] = $this->acronym . '/' . $file;
				
				// TODO - update this to match the schema discussed at 2017-03-28's Civic Tech!
				$fileValues['uuid'] = $this->acronym . '-' . $fileValues['referenceNumber'];


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

			}
			else {
				echo "Error: could not parse data for $file\n";
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

			if(in_array($acronym, Configuration::$departmentsToSkip)) {
				echo "Skipping " . $acronym . "\n";
				continue;
			}

			if(Configuration::$limitDepartments && $departmentsParsed >= Configuration::$limitDepartments) {
				break;
			}

			$startDate = date('Y-m-d H:i:s');
			echo "Starting " . $acronym . " at ". $startDate . " \n";

			$department = new DepartmentParser($acronym);

			$department->parseDepartment();

			// Rather than storing the whole works in memory, 
			// let's just save one department at a time in individual
			// JSON files:

			$directoryPath = dirname(__FILE__) . '/' . Configuration::$jsonOutputFolder . '/' . $acronym;

			// If the folder doesn't exist yet, create it:
			// Thanks to http://stackoverflow.com/a/15075269/756641
			if(! is_dir($directoryPath)) {
				mkdir($directoryPath, 0755, true);
			}

			file_put_contents($directoryPath . '/contracts.json', json_encode($department->contracts, JSON_PRETTY_PRINT));

			// var_dump($department->contracts);
			// $output[$acronym] = $department->contracts;

			echo "Started " . $acronym . " at " . $startDate . "\n";
			echo "Finished at ". date('Y-m-d H:i:s') . " \n\n";

			$departmentsParsed++;

		}

		

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
		$pattern = '/<th scope="row">([\wÀ-ÿ@$#%^&+\*\-.\'(),;:<\/>\s]*)<\/th><td>([\wÀ-ÿ@$#%^&+\*\-.\'(),;:<\/>\s]*)<\/td>/';

		preg_match_all($pattern, $html, $matches, PREG_SET_ORDER);

		// var_dump($matches);

		foreach($matches as $match) {

			$label = $match[1];
			$value = $match[2];

			if(array_key_exists($label, $labelToKey)) {

				$values[$labelToKey[$label]] = Helpers::cleanHtmlValue($value);

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

	public static function csa($html) {

		// Just get the table in the middle:
		$html = Helpers::stringBetween('DEBUT DU CONTENU', 'FIN DU CONTENU', $html);

		$values = [];

		$keys = [
			'vendorName',
			'referenceNumber',
			'description',
			'deliveryDate',
			'contractValue',
			'comments',
		];

		$keysWithModifications = [
			'vendorName',
			'referenceNumber',
			'description',
			'deliveryDate',
			'originalValue',
			'modificationValue',
			'contractValue',
			'comments',
		];

		$matches = [];
		$pattern = '/<td class="align-middle">([\wÀ-ÿ@$#%^&+\*\-.\'(),;:<\/>\s]*)<\/td>/';

		preg_match_all($pattern, $html, $matches, PREG_SET_ORDER);

		// var_dump($matches);

		if(count($matches) == 8) {
			$keys = $keysWithModifications;
		}

		foreach($matches as $index => $match) {

			$value = $match[1];

			if(array_key_exists($index, $keys)) {

				$value = Helpers::cleanHtmlValue($value);

				$values[$keys[$index]] = $value;

			}

		}

		// Interestingly, the decimal points for CSA are commas rather than periods (probably coded in French originally).
		if(isset($values['originalValue'])) {
			$values['originalValue'] = str_replace([',', ' '], ['.', ''], $values['originalValue']);
		}
		if(isset($values['contractValue'])) {
			$values['contractValue'] = str_replace([',', ' '], ['.', ''], $values['contractValue']);
		}
		if(isset($values['modificationValue'])) {
			$values['modificationValue'] = str_replace([',', ' ', '$'], ['.', '', ''], $values['modificationValue']);
		}

		


		// If there isn't an originalValue, use the contractValue
		if(! (isset($values['originalValue']) && $values['originalValue'])) {
			$values['originalValue'] = $values['contractValue'];
		}

		// Do a separate regular expression to retrieve the time values
		// The first one is the contract date, and the second two are the start and end dates
		// For the contract period (the second two values), these are inexplicably in YYYY-DD-MMM format (eg. 2011-31-003)
		$matches = [];
		$pattern = '/<time datetime="([\w-@$#%^&+.,;:<\/>\s]*)">/';

		preg_match_all($pattern, $html, $matches, PREG_SET_ORDER);

		// var_dump($matches);

		$values['contractDate'] = $matches[0][1];

		// Fix the date issue while we're at it:
		if(isset($matches[1][1])) {
			$values['contractPeriodStart'] = Helpers::switchMonthsAndDays(str_replace('-00', '-0', $matches[1][1]));
		}
		if(isset($matches[2][1])) {
			$values['contractPeriodEnd'] = Helpers::switchMonthsAndDays(str_replace('-00', '-0', $matches[2][1]));
		}
		
		

		// var_dump($values);

		return $values;

	}

	public static function fin($html) {

		$html = Helpers::stringBetween('MainContentStart', 'MainContentEnd', $html);

		$values = [];
		$keyToLabel = [
			'vendorName' => 'Vendor Name:',
			'referenceNumber' => 'Reference Number:',
			'contractDate' => 'Contract Date:',
			'description' => 'Description of work:',
			'contractPeriodStart' => '',
			'contractPeriodEnd' => '',
			'contractPeriodRange' => 'Contract Period:',
			'deliveryDate' => 'Delivery Date:',
			'originalValue' => 'Original Contract Value:',
			'contractValue' => 'Contract Value:',
			'comments' => 'Comments:',
		];
		$labelToKey = array_flip($keyToLabel);

		$matches = [];
		$pattern = '/<div class="span-2"><strong>([\wÀ-ÿ@$#%^&+\*\-.\'(),;:<\/>\s]*)<\/strong><\/div>[\s]*<div class="span-3">([\wÀ-ÿ@$#%^&+\*\-.\'(),;:<\/>\s]*)<\/div>/';

		preg_match_all($pattern, $html, $matches, PREG_SET_ORDER);

		// var_dump($matches);
		// exit();

		foreach($matches as $match) {

			$label = trim(str_replace('&nbsp;', '', $match[1]));
			$value = trim(str_replace('&nbsp;', '', $match[2]));

			if(array_key_exists($label, $labelToKey)) {

				$values[$labelToKey[$label]] = Helpers::cleanHtmlValue($value);

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

	public static function infra($html) {

		$html = Helpers::stringBetween('MainContentStart', 'MainContentEnd', $html);

		$values = [];
		$keyToLabel = [
			'vendorName' => 'Vendor Name',
			'referenceNumber' => 'Reference Number',
			'contractDate' => 'Contract Date',
			'description' => 'Description of Work',
			'contractPeriodStart' => '',
			'contractPeriodEnd' => '',
			'contractPeriodRange' => 'Contract Period',
			'deliveryDate' => 'Delivery Date',
			'originalValue' => 'Original Contract Value',
			'contractValue' => 'Contract Value',
			'comments' => 'Comments',
		];
		$labelToKey = array_flip($keyToLabel);

		$matches = [];
		$pattern = '/<th>([\wÀ-ÿ@$#%^&+\*\-.\'(),;:\/\s]*)<\/th>[\s]*<td>([\wÀ-ÿ@$#%^&+\*\-.\'(),;:\s]*)<\/td>/';

		preg_match_all($pattern, $html, $matches, PREG_SET_ORDER);

		// var_dump($matches);
		// exit();

		foreach($matches as $match) {

			$label = trim(str_replace('&nbsp;', '', $match[1]));
			$value = trim(str_replace('&nbsp;', '', $match[2]));

			if(array_key_exists($label, $labelToKey)) {

				$values[$labelToKey[$label]] = Helpers::cleanHtmlValue($value);

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

	public static function ic($html) {

		$html = Helpers::stringBetween('<div typeof="Action">', '<p class="notPrintable">', $html);

		// Remove spans that are kind of un-helpful
		$html = str_replace([
			'<span property="agent">',
			'<span property="startTime">',
			'<span property="description">',
			'<span property="object">',
			'</span>',
			], '', $html);

		// var_dump($html);
		// exit();

		$values = [];
		$keyToLabel = [
			'vendorName' => 'Vendor name:',
			'referenceNumber' => 'Reference number:',
			'contractDate' => 'Contract date:',
			'description' => 'Description of work:',
			'contractPeriodStart' => '',
			'contractPeriodEnd' => '',
			'contractPeriodRange' => 'Contract period / delivery date:',
			'deliveryDate' => 'Delivery Date:',
			'originalValue' => 'Original contract value:',
			'contractValue' => 'Contract value:',
			'comments' => 'Comments:',
		];
		$labelToKey = array_flip($keyToLabel);

		$matches = [];
		$pattern = '/<div class="ic2col1 formLeftCol">([\wÀ-ÿ@$#%^&+\*\-.\'(),;:<\/>\s]*)<\/div>[\s]*<div class="ic2col2 formRightCol">([\wÀ-ÿ@$#%^&+\*\-.\'(),;:<\/>\s]*)<\/div>/';

		preg_match_all($pattern, $html, $matches, PREG_SET_ORDER);

		// var_dump($matches);
		// exit();

		foreach($matches as $match) {

			$label = trim(str_replace('&nbsp;', '', $match[1]));
			$value = trim(str_replace('&nbsp;', '', $match[2]));

			if(array_key_exists($label, $labelToKey)) {

				$values[$labelToKey[$label]] = Helpers::cleanHtmlValue($value);

			}

		}

		// Change the "to" range into start and end values:
		if(isset($values['contractPeriodRange']) && $values['contractPeriodRange']) {
			$split = explode(' - ', $values['contractPeriodRange']);
			$values['contractPeriodStart'] = trim($split[0]);
			$values['contractPeriodEnd'] = trim($split[1]);


		}

		// var_dump($values);
		// exit();

		return $values;

	}

}

DepartmentParser::parseAllDepartments();
