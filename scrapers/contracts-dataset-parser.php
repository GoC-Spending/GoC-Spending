<?php
// Parse the open.canada.ca dataset for proactive disclosure
// Data source:
// http://open.canada.ca/data/en/dataset/d8f85d91-7dec-4fd1-8055-483b77225d8b

// toobs2017@gmail.com and the GoC-Spending team!

require('contracts-helpers.php');

// Go crazy!
ini_set('memory_limit', '2048M');

class DatasetParser {

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



	public static $columnMapping = [
		'referenceNumber' => 0,
		'procurementId' => 1,
		'vendorName' => 2,
		'contractDate' => 3,
		'description' => 5,
		'contractPeriodStart' => 7,
		'deliveryDate' => 8,
		'contractValue' => 9,
		'originalValue' => 10,
		'comments' => 12,
		'ownerAcronym' => 31,
	];

	public static function parseDataset($filename) {

		$mapToColumns = array_flip(self::$columnMapping);
		// var_dump($mapToColumns);
		// exit();

		$output = [];

		$fp = fopen($filename, "r");

		$row = 0;
		while (($data = fgetcsv($fp)) !== FALSE) {

			$row++;
			// Skip the header row
			if($row == 1) {
				continue;
			}

			if($row % 1000 == 0) {
				echo "$row\n";
			}

			$rowOutput = [];

			foreach($mapToColumns as $index => $key) {

				$rowOutput[$key] = $data[$index];

			}

			$rowOutput = array_merge(self::$rowParams, $rowOutput);

			// For consistency with the scraped data, use unilingual department acronyms for now:
			if($rowOutput['ownerAcronym']) {
				$rowOutput['ownerAcronym'] = explode('-', $rowOutput['ownerAcronym'])[0];
			}

			// Various minor cleanup:
			$rowOutput['contractValue'] = Helpers::cleanupContractValue($rowOutput['contractValue']);
			$rowOutput['originalValue'] = Helpers::cleanupContractValue($rowOutput['originalValue']);

			$rowOutput['uuid'] = $rowOutput['ownerAcronym'] . '-' . $rowOutput['referenceNumber'];

			// var_dump($data);
			// var_dump($rowOutput);

			// For literally one SSC row, that's missing a referenceNumber but has a procurementId:
			if(! $rowOutput['referenceNumber']) {
				$rowOutput['referenceNumber'] = $rowOutput['procurementId'];
			}
			unset($rowOutput['procurementId']);

			if($rowOutput['ownerAcronym'] && $rowOutput['referenceNumber']) {

				// Store the row!
				if(isset($output[$rowOutput['ownerAcronym']][$rowOutput['referenceNumber']])) {
					echo "Warning: " . $rowOutput['ownerAcronym'] . $rowOutput['referenceNumber'] . " already exists.\n";
				}
				else {
					$output[$rowOutput['ownerAcronym']][$rowOutput['referenceNumber']] = $rowOutput;
				}

				
			}
			else {
				echo "Error: no ownerAcronym for row $row\n";
				var_dump($data);
			}
			

			
			// if($row > 4) {
			// 	break;
			// }

		}

		// var_dump($output);
		return $output;

	}

	public static function exportDataset($filename) {

		$skipDepartments = [
			'fin',
		];

		$startDate = date('Y-m-d H:i:s');
		echo "Starting  at ". $startDate . " \n";

		$jsonData = self::parseDataset($filename);

		// var_dump($jsonData);
		foreach($jsonData as $acronym => $departmentArray) {

			if(in_array($acronym, $skipDepartments)) {
				continue;
			}


			$directoryPath = dirname(__FILE__) . '/generated-data/' . $acronym;

			// If the folder doesn't exist yet, create it:
			// Thanks to http://stackoverflow.com/a/15075269/756641
			if(! is_dir($directoryPath)) {
				mkdir($directoryPath, 0755, true);
			}

			file_put_contents($directoryPath . '/contracts.json', json_encode($departmentArray, JSON_PRETTY_PRINT));

		}

		echo "Started at " . $startDate . "\n";
		echo "Finished at ". date('Y-m-d H:i:s') . " \n\n";

	}

}



DatasetParser::exportDataset(dirname(__FILE__) . '/datasets/open.canada.ca/contracts.csv');



