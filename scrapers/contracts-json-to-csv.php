<?php
// A simple script to retrieve all proactive disclosure contract pages
// from the PWGSC website, and store them in an "output" folder.
// Depending on your folder permissions, you may have to create the
// "output" folder as a subdirectory before running this script.
// Estimated runtime is at least an hour on a home internet connection.

// This script converts the JSON data from contracts-parser.php into a CSV file

// toobs2017@gmail.com and the GoC-Spending team!

require('contracts-helpers.php');
require('contracts-vendor-data.php');

// Go crazy!
ini_set('memory_limit', '512M');



class ParserJsonToCsv {

	public static function checkContract(&$contract, $vendorData) {

		// In some cases, entries are missing a contract period start, but do have a contract date. If so, use that instead:
		if(! $contract['startYear']) {
			if($contract['deliveryDate']) {
				$contract['startYear'] = Helpers::dateToYear($contract['deliveryDate']);
			}
			else {
				$contract['startYear'] = Helpers::dateToYear($contract['contractDate']);
			}
		}

		// If there's no end year, assume that it's the same as the start year:
		if(! $contract['endYear']) {
			if($contract['deliveryDate']) {
				$contract['endYear'] = Helpers::dateToYear($contract['deliveryDate']);
			}
			else {
				$contract['endYear'] = $contract['startYear'];
			}
			
		}

		// If there's no original contract value, use the current value:
		if(! $contract['originalValue']) {
			$contract['originalValue'] = $contract['contractValue'];
		}

		$contract['vendorClean'] = $vendorData->consolidateVendorNames($contract['vendorName']);


	}

	

	public static function contractToRow($contract, $vendorData) {

		self::checkContract($contract, $vendorData);

		$output = [];

		$output[] = $contract['uuid'];
		$output[] = $contract['ownerAcronym'];
		$output[] = $contract['vendorClean'];
		$output[] = $contract['contractValue'];
		$output[] = $contract['contractDate'];
		$output[] = $contract['startYear'];
		$output[] = $contract['endYear'];
		
		$output[] = $contract['originalValue'];
		$output[] = count($contract['amendedValues']);
		$output[] = $contract['description'];
		$output[] = $contract['vendorName'];

		return $output;

	}

	public static function headers() {
		
		$output = [
			'ID',
			'Department',
			'Vendor Name',
			'Contract Value',
			'Contract Date',
			'Start Year',
			'End Year',
			'Original Value',
			'Number of Amendments',
			'Description',
			'Original Vendor Name',
		];

		return $output;

	}

	public static function convert($sourceDirectory, $outputFilename) {

		$startDate = date('Y-m-d H:i:s');
		echo "Starting at ". $startDate . " \n";

		$vendorData = new VendorData;

		// Thanks to,
		// https://www.skoumal.net/en/making-utf-8-csv-excel/
		$fp = fopen($outputFilename, 'w');
		fputs($fp, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));
		fputcsv($fp, self::headers());


		$files = [];
		$departments = array_diff(scandir($sourceDirectory), ['..', '.']);

		foreach($departments as $department) {
			if(file_exists($sourceDirectory . '/' . $department . '/contracts.json')) {
				$files[] = $sourceDirectory . '/' . $department . '/contracts.json';
			}

		}
		// var_dump($files);
		// exit();

		foreach($files as $file) {

			// This could be pretty memory-intensive:
			$jsonData = json_decode(file_get_contents($file), 1);

			foreach($jsonData as $contract) {

				fputcsv($fp, self::contractToRow($contract, $vendorData));

			}


		}

		echo "Started at " . $startDate . "\n";
		echo "Finished at ". date('Y-m-d H:i:s') . " \n\n";


	}
}

ParserJsonToCsv::convert(dirname(__FILE__) . '/generated-data', dirname(__FILE__) . '/contracts-output.csv');

