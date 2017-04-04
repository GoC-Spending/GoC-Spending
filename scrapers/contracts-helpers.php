<?php
// Helper-functions class for the contracts parser and related scripts.

// toobs2017@gmail.com and the GoC-Spending team!

// These aren't required in PHP 7+
if(function_exists('mb_language')) {
	mb_language('uni'); mb_internal_encoding('UTF-8');
}

class Helpers {

	public static function cleanupDate($dateInput) {

		// 11/1/2013
		$time = strtotime($dateInput);
		if($time) {
			return $time;
		}
		else {
			// Try doing the month switch and see if that helps:
			if($dateInput) {
				$time = strtotime(self::switchMonthsAndDays($dateInput));
				if($time) {
					echo "Switched months and days for: '$dateInput'\n";
					return $time;
				}
				else {
					echo "Date cleanup error: '$dateInput'\n";
				}
			}
			// If there's no $dateInput at all, don't print an error:
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

	public static function cleanHtmlValue($value) {

		$value = str_replace(['&nbsp;', '&amp;', '&AMP;'], [' ', '&', '&'], $value);
		$value = trim(strip_tags($value));
		return $value;

	}

	public static function switchMonthsAndDays($dateString) {
		// Takes a YYYY-DD-MM (whyyyy, CSA?)
		// and changes it to YYYY-MM-DD

		$split = explode('-', $dateString);
		if(count($split) == 3) {
			return $split[0] . '-' . $split[2] . '-' . $split[1];
		}
		else {
			echo "Error: could not switchMonthsAndDays for '$dateString'\n";
			return false;
		}
		

	}

	public static function stringBetween($start, $end, $string) {

		if(! $string) {
			return '';
		}

		$split = explode($start, $string);
		return explode($end, $split[1])[0];

	}

}