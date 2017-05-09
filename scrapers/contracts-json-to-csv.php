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
ini_set('memory_limit', '3200M');



class ParserJsonToCsv {

	public static function checkContract(&$contract, $vendorData) {

		// In some cases, entries are missing a contract period start, but do have a contract date. If so, use that instead:
		if(! $contract['startYear']) {
			// if($contract['deliveryDate']) {
			// 	$contract['startYear'] = Helpers::yearFromDate($contract['deliveryDate']);
			// }
			// else {
			// 	$contract['startYear'] = Helpers::yearFromDate($contract['contractDate']);
			// }

			$contract['startYear'] = Helpers::yearFromDate($contract['contractDate']);
		}

		// If there's no end year, assume that it's the same as the start year:
		if(! $contract['endYear']) {
			if($contract['deliveryDate']) {
				$contract['endYear'] = Helpers::yearFromDate($contract['deliveryDate']);
			}
			else {
				$contract['endYear'] = $contract['startYear'];
			}
			
		}

		// If there's no original contract value, use the current value:
		if(! $contract['originalValue']) {
			$contract['originalValue'] = $contract['contractValue'];
		}


		$contract['yearsDuration'] = abs($contract['endYear'] - $contract['startYear']) + 1;
		$contract['valuePerYear'] = $contract['contractValue'] / $contract['yearsDuration'];

		// Find the consolidated vendor name:
		$contract['vendorClean'] = $vendorData->consolidateVendorNames($contract['vendorName']);


		// Remove any linebreaks etc.
		// vendorName
		// referenceNumber
		// description
		// comments
		foreach(['vendorName', 'referenceNumber', 'description', 'comments'] as $textField) {
			if(isset($contract[$textField]) && $contract[$textField]) {
				$contract[$textField] = Helpers::removeLinebreaks($contract[$textField]);
			}

		}


	}

	

	public static function contractToRow($contract) {

		$output = [];

		$output[] = $contract['uuid'];
		$output[] = $contract['ownerAcronym'];
		$output[] = $contract['vendorClean'];
		$output[] = $contract['contractValue'];
		$output[] = $contract['contractDate'];
		$output[] = $contract['startYear'];
		$output[] = $contract['endYear'];
		
		$output[] = $contract['yearsDuration'];
		$output[] = $contract['valuePerYear'];
		
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
			'Duration in Years',
			'Value Per Year',
			'Original Value',
			'Number of Amendments',
			'Description',
			'Original Vendor Name',
		];

		return $output;

	}

	public static function rowToDuplicateIndicator($contract) {

		return sha1($contract['vendorClean'] . $contract['contractDate'] . $contract['contractValue']);

	}

	public static function convert($sourceDirectory, $outputDirectory, $ignoreDuplicates = 0) {

		$yearsToExport = [
			2016,
			2015,
			2014,
		];

		$startDate = date('Y-m-d H:i:s');
		echo "Starting at ". $startDate . " \n";

		$vendorData = new VendorData;

		// Thanks to,
		// https://www.skoumal.net/en/making-utf-8-csv-excel/
		$fp = fopen($outputDirectory . '/contracts-output.csv', 'w');
		fputs($fp, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));
		fputcsv($fp, self::headers());

		// Also output to each of the year pointers.
		$yearFPs = [];
		foreach($yearsToExport as $year) {
			$yearFPs[$year] = fopen($outputDirectory . '/contracts-output-' . $year .'.csv', 'w');
			fputs($yearFPs[$year], $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));
			fputcsv($yearFPs[$year], self::headers());
		}


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

			echo "$file\n";


			// This could be pretty memory-intensive:
			$jsonData = json_decode(file_get_contents($file), 1);

			$cleanData = [];
			$duplicateIndicators = [];

			// Loop through once, to get hints at possible duplicates
			foreach($jsonData as $contract) {

				self::checkContract($contract, $vendorData);

				$hash = self::rowToDuplicateIndicator($contract);
				if(in_array($hash, $duplicateIndicators)) {
					$contract['possibleDuplicate'] = 1;
				}
				else {
					$contract['possibleDuplicate'] = 0;
					$duplicateIndicators[] = $hash;
				}
				

				$cleanData[] = $contract;
			}

			// var_dump($duplicateIndicators);
			// exit();

			echo "Starting: " . count($cleanData) . " rows \n";
			unset($jsonData);

			$index = 0;

			foreach($cleanData as $row) {

				$index++;
				if($index % 100 == 0) {
					// echo "$index\n";
				}

				// Optionally ignore possible duplicate entries:
				if($ignoreDuplicates && $row['possibleDuplicate'] == 1) {
					continue;
				}

				fputcsv($fp, self::contractToRow($row));

				// Export per-year CSV files (eg. for 2016)
				foreach($yearsToExport as $year) {

					if(Helpers::dateIsWithinYearRange($row['startYear'], $row['endYear'], $year)) {
						fputcsv($yearFPs[$year], self::contractToRow($row));
					}

				}

			}

			echo "...done.\n";


		}

		echo "Started at " . $startDate . "\n";
		echo "Finished at ". date('Y-m-d H:i:s') . " \n\n";


	}
}

ParserJsonToCsv::convert(dirname(__FILE__) . '/generated-data', dirname(__FILE__) . '/csv-output', 1);

