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
		// 'sourceYear' => '',
		// 'sourceQuarter' => '',
		// 'sourceFilename' => '',
		// 'sourceURL' => '',
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

	public static function cleanupRow(&$rowOutput) {

		// Various minor cleanup:
		$rowOutput['contractValue'] = Helpers::cleanupContractValue($rowOutput['contractValue']);
		$rowOutput['originalValue'] = Helpers::cleanupContractValue($rowOutput['originalValue']);

		$rowOutput['uuid'] = $rowOutput['ownerAcronym'] . '-' . $rowOutput['referenceNumber'];

		
		// Date cleanup attempts:
		$rowOutput['contractPeriodStart'] = trim($rowOutput['contractPeriodStart']);
		if($rowOutput['contractPeriodStart']) {
			$rowOutput['contractPeriodStart'] = Helpers::regDateCleanup($rowOutput['contractPeriodStart']);
		}

		$rowOutput['deliveryDate'] = trim($rowOutput['deliveryDate']);
		if($rowOutput['deliveryDate']) {
			$rowOutput['deliveryDate'] = Helpers::regDateCleanup($rowOutput['deliveryDate']);
		}

		$rowOutput['contractDate'] = trim($rowOutput['contractDate']);
		if($rowOutput['contractDate']) {
			$rowOutput['contractDate'] = Helpers::regDateCleanup($rowOutput['contractDate']);
		}
		

		if($rowOutput['contractPeriodStart'] && Helpers::dateToYear($rowOutput['contractPeriodStart']) == false) {
			// 
			echo "Error parsing date for contractPeriodStart\n";
			var_dump($data);
			var_dump($rowOutput);
			exit();
		}


		// For text values, cleanup potential error-prone characters:
		// $rowOutput['vendorName'] = Helpers::cleanNonAsciiCharactersInString($rowOutput['vendorName']);
		// $rowOutput['description'] = Helpers::cleanNonAsciiCharactersInString($rowOutput['description']);
		// $rowOutput['comments'] = Helpers::cleanNonAsciiCharactersInString($rowOutput['comments']);

		// Testing for the DFATD glitch
		// $rowOutput['vendorName'] = sha1($rowOutput['vendorName']);
		// $rowOutput['description'] = sha1($rowOutput['description']);
		// $rowOutput['comments'] = sha1($rowOutput['comments']);

		


		// For literally one SSC row, that's missing a referenceNumber but has a procurementId:
		// if(! $rowOutput['referenceNumber']) {
		// 	$rowOutput['referenceNumber'] = $rowOutput['procurementId'];
		// }
		// unset($rowOutput['procurementId']);


		if($rowOutput['ownerAcronym'] && $rowOutput['referenceNumber']) {
			return true;
		}

		// If this returns false, don't actually add it:
		return false;



	}

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

			// The referenceNumber values are all unique, because they already include the year and quarter
			// So instead we'll use the procurementId, which looks like the common value for the same contract:
			if($rowOutput['procurementId']) {
				$rowOutput['referenceNumber'] = strtolower(trim($rowOutput['procurementId']));
			}
			else {
				echo "Warning: no procurementId for " . $rowOutput['referenceNumber'] . "\n";
			}
			unset($rowOutput['procurementId']);

			// Special handling for some iffy DFATD reference numbers (with encoding issues) that were crashing the JSON encode later:
			if($rowOutput['ownerAcronym'] == 'dfatd') {
				$rowOutput['referenceNumber'] = 'c' . Helpers::cleanNonAsciiCharactersInString($rowOutput['referenceNumber']);
			}

			if(self::cleanupRow($rowOutput)) {

				// Store the row!
				if(isset($output[$rowOutput['ownerAcronym']][$rowOutput['referenceNumber']])) {
					// echo "Warning: " . $rowOutput['ownerAcronym'] . $rowOutput['referenceNumber'] . " already exists.\n";

					// Let's use the largest contractValue for now:
					// From the dataset, we should actually be able to parse based on chronological order, which would be better.
					// TODO - update this to use the newest value rather than largest one.
					$existingContract = $output[$rowOutput['ownerAcronym']][$rowOutput['referenceNumber']];
					if($rowOutput['contractValue'] > $existingContract['contractValue']) {
						$output[$rowOutput['ownerAcronym']][$rowOutput['referenceNumber']] = $rowOutput;
					}


					// Add entries to the amendedValues array
					// If it's the first time, add the original too
					if($existingContract['amendedValues']) {
						$output[$rowOutput['ownerAcronym']][$rowOutput['referenceNumber']]['amendedValues'] = array_merge($existingContract['amendedValues'], [$rowOutput['contractValue']]);
					}
					else {
						$output[$rowOutput['ownerAcronym']][$rowOutput['referenceNumber']]['amendedValues'] = [
							$existingContract['contractValue'],
							$rowOutput['contractValue'],
						];
					}


				}
				else {
					$output[$rowOutput['ownerAcronym']][$rowOutput['referenceNumber']] = $rowOutput;
				}

				
			}
			else {
				echo "Error: could not add row $row\n";
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
			'dnd',
		];

		$startDate = date('Y-m-d H:i:s');
		echo "Starting  at ". $startDate . " \n";

		$jsonData = self::parseDataset($filename);

		// var_dump($jsonData);
		foreach($jsonData as $acronym => $departmentArray) {

			if(in_array($acronym, $skipDepartments)) {
				continue;
			}

			echo "Exporting $acronym " . count($departmentArray) . " ";


			$directoryPath = dirname(__FILE__) . '/generated-data/' . $acronym;

			// If the folder doesn't exist yet, create it:
			// Thanks to http://stackoverflow.com/a/15075269/756641
			if(! is_dir($directoryPath)) {
				mkdir($directoryPath, 0755, true);
			}

			if(file_put_contents($directoryPath . '/contracts.json', json_encode($departmentArray, JSON_PRETTY_PRINT))) {
				echo "...saved.\n";
			}
			else {
				// echo "STARTHERE: \n";
				// var_export($departmentArray);

				// echo "ENDHERE. \n";
				echo "...failed.\n";

				$newOutput = [];

				$index = 0;
				$limit = 100000;

				foreach($departmentArray as $key => $data) {
					$index++;
					if($index > $limit) {
						break;
					}
					$newOutput[$key] = $data;

					echo $index;

					if(json_encode($data, JSON_PRETTY_PRINT)) {
						echo " P\n";
					}
					else {
						echo " F\n";
						var_dump($key);
						var_dump($data);
						exit();
					}

				}

			}

		}

		echo "Started at " . $startDate . "\n";
		echo "Finished at ". date('Y-m-d H:i:s') . " \n\n";

	}

}

// var_dump(Helpers::extraCleanupDate('vendor name'));
// exit();

DatasetParser::exportDataset(dirname(__FILE__) . '/datasets/open.canada.ca/contracts.csv');



