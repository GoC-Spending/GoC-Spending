<?php

class VendorData {

	public $vendorTable;

	function __construct() {

		$this->vendorTable = self::reindexVendorData(self::$vendors);

	}

	// Re-index the vendor data so that the name variants are keys and the common name is the value for each, to speed up matching in the consolidateVendorNames function:
	public static function reindexVendorData($vendors) {

		$vendorTable = [];

		foreach($vendors as $vendor => $vendorNames) {

			foreach($vendorNames as $vendorName) {

				$vendorTable[self::cleanupVendorName($vendorName)] = self::cleanupVendorName($vendor);

			}

		}

		return $vendorTable;

	}

	public function consolidateVendorNames($vendorName) {

		$vendorName = self::cleanupVendorName($vendorName);

		$output = $vendorName;

		if(isset($this->vendorTable[$vendorName])) {
			$output = $this->vendorTable[$vendorName];
		}

		// if($vendorName != $output) {
		// 	echo "Replacing [$vendorName] with [$output]. \n";
		// }

		return $output;

	}

	public static function cleanupVendorName($input) {

		$charactersToRemove = [
			',',
			"'",
			"\t",
			'.',
			' INC',
			' LTD',
			' -',
			' /',
		];

		$output = str_replace($charactersToRemove, '', strtoupper($input));
		return trim($output);

	}

	public static $vendors = [

		'IBM CANADA' => [
			'IBM',
			'IBM Business Consulting Services',
			'IBM BUSINESS CONSULTING SERVICES/',
			'IBM CAN*91898807',
			'IBM CANADA',
			'IBM CANADA LIMITED',
			'IBM CANADA LTD',
			'IBM CANADA LTD.',
			'IBM CANADA LTEE',

		],

		'BELL CANADA' => [
			'THE BELL TELEPHONE COMPANY OF',
			'THE BELL TELEPHONE COMPANY OF CANADA',
			'The Bell Telephone Company of Canada  / Bell Canada',
			'Bell',
			'Bell Advanced Communications',
			'BELL ALIANT',
			'BELL ALIANT REGIONAL',
			'BELL ALIANT REGIONAL COMM.    L.P',
			'BELL ALIANT REGIONAL COMM.   L.P',
			'BELL ALIANT REGIONAL COMM. L.P',
			'BELL ALIANT REGIONAL COMMUNICATIONS',
			'Bell Aliant Regional Communications L.P',
			'Bell Aliant Regional Communications L.P.',
			'Bell Aliant Regional Communications, LP',
			'BELL CANADA',
			'Bell Canada ICT Solutions',
			'Bell Canada, Enterprise Division',
			'BELL MOBILITY',
			'Bell Mobility Inc.',
			'BELL NEXXIA',
			'BELL WORLD/KANATA WRLSS',
			'BELL/BCE NEXXIA Inc.',

		],

		'MICROSOFT CANADA' => [

			'Microsoft',
			'Microsoft Canada',
			'MICROSOFT CANADA CO.',
			'MICROSOFT CANADA INC',
			'MICROSOFT CANADA INC.',
			'MICROSOFT CANADA LTD.',
			'MICROSOFT CORPORATION',
			'MICROSOFT LICENCING , GP',
			'Microsoft Licensing GP',
			'MICROSOFT LICENSING, GP',

		],

		'HEWLETT PACKARD' => [

			'Hewlett Packard (Canada)',
			'HEWLETT PACKARD (CANADA) CO.',
			'Hewlett Packard (Canada) Ltd',
			'HEWLETT PACKARD CANADA',
			'HEWLETT PACKARD CANADA CO.',
			'Hewlett-Packard (Canada)',
			'HEWLETT-PACKARD (CANADA) CO',
			'HEWLETT-PACKARD (CANADA) CO.',
			'HEWLETT-PACKARD (CANADA) LTD',
			'Hewlett-Packard (Canada) Ltd.',
			'HEWLETT-PACKARD CANADA',
			'Hewlett-Packard Canada Ltd',
			'HEWLETT-PACKARD CANADA LTD.',
			'Hewlett-Packard Ltd.',
			'HewlettPackard (Canada) Co.',
			'HP Canada Co.',

		],

		'CGI' => [

			'CGI INFORMATION SYSTEMS',
			'CGI INFORMATION SYSTEMS &',
			'CGI Information Systems & Management Consultants',
			'CGI Information Systems & Management Consultants I',
			'CGI Information Systems & Management Consultants Inc.',
			'CGI INFORMATION SYSTEMS AND',
			'CGI Information Systems and Management Consultant',
			'CGI Information Systems and Management Consultants',
			'CGI INFORMATION SYSTEMS AND MANAGEMENT CONSULTANTS INC',
			'CGI INFORMATION SYSTEMS AND MANAGEMENT CONSULTANTS INC.',
			'CGI INFORMATION SYSTMES AND',
			'CGI Payroll Services Centre',
			'CGI/AMS MANAGEMENT SYSTEMS',
			'CGI INFORMATIONS SYSTEMS AND MANAGEMENTS CONSULTANTS',

		],

		'ROGERS' => [

			'Rogers AT&T',
			// 'ROGERS BERNADINE',
			'ROGERS BUSINESS SOLUTIONS',
			'ROGERS CABLE COMMUNICATIONS INC',
			'ROGERS CABLE INC',
			'Rogers Cable Inc.',
			'Rogers Communications',
			'ROGERS COMMUNICATIONS CANADA INC.',
			'ROGERS COMMUNICATIONS INC.',
			'ROGERS COMMUNICATIONS PARTNERSHIP',
			'ROGERS DATA CENTRES INC.',
			'ROGERS OTTAWA',
			'ROGERS WIRELESS',

		],
		 
		 'ORACLE CANADA' => [

		 	'Oracle',
		 	'ORACLE CANADA',
		 	'ORACLE CANADA ULC',
		 	'Oracle Canada ULC.',
		 	'ORACLE CORPORATION',
		 	'ORACLE CORPORATION CANADA INC',
		 	'ORACLE CORPORATION CANADA INC.',

		 ],

		 'CANADIAN CORPS OF COMMISSIONAIRES' => [
		 	'CAN. COMMISSIONAIRES (OTTAWA)',
		 	'Canadian Commissionaires (Ottawa)',
		 	'CANADIAN CORP OF COMMISSIONAIRES',
		 	'CANADIAN CORPS',
		 	'CANADIAN CORPS OF COMMISSIONAI',
		 	'CANADIAN CORPS OF COMMISSIONAIRES',
		 	'CANADIAN CORPS OF COMMISSIONAIRES (HAMILTON)',
		 	'Canadian Corps of Commissionaires (NB)',
		 	'CANADIAN CORPS OF COMMISSIONAIRES OTTAWA DIVISION',
		 	'Canadian Corps. of Commissionaires  (NS)',
		 	'CDN CORPS OF COMMISSIONAIRES',
		 	'CDN. CORPS OF COMMISSIONAIRES',
		 	'Commissionaire Kingston',
		 	'COMMISSIONAIRE SERVICES',
		 	'COMMISSIONAIRES',
		 	'COMMISSIONAIRES (GREAT LAKES)',
		 	'COMMISSIONAIRES - NS',
		 	'COMMISSIONAIRES B C',
		 	'COMMISSIONAIRES BRITISH COLUMBIA',
		 	'COMMISSIONAIRES GREAT LAKES',
		 	'COMMISSIONAIRES MANITOBA DIVISION',
		 	'Commissionaires Montreal',
		 	'COMMISSIONAIRES NB-PEI DIVISION',
		 	'COMMISSIONAIRES NEWFOUNDLAND',
		 	'COMMISSIONAIRES NOVA SCOTIA',
		 	'COMMISSIONAIRES OF NFLD',
		 	'COMMISSIONAIRES OTTAWA',
		 	'COMMISSIONAIRES OTTAWA (D)',
		 	'COMMISSIONAIRES OTTAWA (SG)',
		 	'COMMISSIONAIRES QUEBEC',
		 	'COMMISSIONAIRES VICTORIA',
		 	'Commissionaires Victoria &',
		 	'COMMISSIONAIRES, NB/PEI',
		 	'COMMISSIONAIRES-N. ALBERTA',
		 	'COMMISSIONAIRES-OTTAWA',
		 	'COMMISSIONARIE SERVICES',
		 	'COMMISSIONNAIRES DU QUEBEC',
		 	'COMMISSIONNAIRES MONTREAL',
		 	'CORPS CANADIEN    COMMISSIONNAIRE',
		 	'CORPS CANADIEN   COMMISSIONNAIRE',
		 	'Corps Canadien Commissionaire',
		 	'CORPS CANADIEN COMMISSIONNAIRE',
		 	'CORPS CANADIEN DES COMMISSIONNAIRE',
		 	'Corps canadien des commissionnaires',
		 	'Corps Canadiens des Commissionnaires',
		 	'CORPS COMMISSIONAIRES',
		 	'Corps des Commissionnaires du Canada',
		 	'Corps of Commissionaires',
		 	'OTTAWA DIVISION - CANADIAN CORPS',
		 	'OTTAWA DIVISION - CANADIAN CORPS OF COMMISSIONAIRES',
		 	'The British Columbia Corps of Commissionaires',
		 	'The Canadian Corps of Commissionaires',
		 	'THE CANADIAN CORPS OF COMMISSIONAIRES/LE CORPS CANADIEN DES COMMISSIONAIRES',
		 	'THE COMMISSIONAIRES',
		 	'@CANADIAN CORPS OF COMMISSIONAIRES',
		 	'B.C. Corps of Commissionaires',
		 	'BC CORPS OF COMMISSIONAIRES',

		 ],

		 'TELUS CANADA' => [

		 	'Telus',
		 	'TELUS COLLABORATION SERVICES',
		 	'TELUS COMMUNICATION (B.C.) INC.',
		 	'TELUS COMMUNICATIONS',
		 	'TELUS COMMUNICATIONS CO.',
		 	'TELUS COMMUNICATIONS COMPANY',
		 	'TELUS COMMUNICATIONS INC',
		 	'TELUS COMMUNICATIONS INC.',
		 	'TELUS INTEGRATED COMMUNICATIONS',
		 	'TELUS MOBILITY',
		 	'TELUS NATIONAL SYSTEMS INC.',
		 	'Telus Solutions En Sant',

		 ],

		 'VMWARE' => [

		 	'VMWARE',
		 	'VMware Inc',
		 	'VMWare Inc.',
		 	'VMWARE INTERNATIONAL LIMITED',
		 	'VM Ware Inc.',

		 ],

		 'MACDONALD DETTWILER AND ASSOCIATES' => [

		 	'MACDONALD DETTWILER AND',
		 	'MDA SYSTEMS',
		 	'MACDONALD DETT',
		 	'MACDONALD DETTWILER AND ASS',
		 	'MDA GEOSPATIAL SERVICES',
		 	'MACDONALD DETWILLER AND',

		 ],

		 'SASKTEL' => [

		 	'SASKTEL',
		 	'SASKATCHEWAN TELECOMMUNICATIONS',

		 ],

		 'ELSEVIER BV' => [

		 	'ELSEVIER  B V',
		 	'ELSEVIER BV',

		 ],

		 'SAS INSTITUTE' => [

		 	'SAS Institute',
		 	'SAS INSTITUTE ( CANADA ) INC',
		 	'SAS INSTITUTE (CANADA) INC',
		 	'SAS INSTITUTE (CANADA) INC.',
		 	'SAS Institute Canada',
		 	'SAS INSTITUTE CANADA INC',
		 	'SAS INSTITUTE CANADA INC.',
		 	'SAS Institute Inc.',
		 	'SAS Instuitute',

		 ],

		 'SCHNEIDER ELECTRIC CANADA' => [

		 	'SCHNEIDER ELECTRIC CANADA',
		 	'SCHNEIDER ELECTRIC CANADA INC',
		 	'SCHNEIDER ELECTRIC CANADA INC.',
		 	'SCHNEIDER ELECTRIC IT CORPORATION',
		 ],

		 'SECURITAS' => [

		 	'SECURITAS CANADA LTD',
		 	'Securitas Canada Ltd.',
		 	'SECURITAS FRANCE SARL',

		 ],

		 'SHARP ELECTRONICS' => [

		 	'Sharp Electronics',
		 	'SHARP ELECTRONICS OF CA',
		 	'SHARP ELECTRONICS OF CANADA',
		 	'SHARP ELECTRONICS OF CANADA LT',
		 	'SHARP ELECTRONICS OF CANADA LTD',
		 	'Sharp Electronics of Canada Ltd.',
		 	'SHARP ELECTRONICS OF CDA LTD',

		 ],

		 'SHRED IT' => [

		 	'SHRED IT',
		 	'SHRED IT INTERNATIONAL ULC',
		 	'SHRED-IT',
		 	'SHRED-IT - INTERNATIONAL INC',
		 	'Shred-It Canada (Toronto)',
		 	'SHRED-IT HALIFAX',
		 	'SHRED-IT INTERNATIONAL',
		 	'SHRED-IT INTERNATIONAL INC.',
		 	'Shred-it International ULC',
		 	'SHRED-IT MONTR��AL',
		 	'SHRED-IT SOUTHWESTERN ONTARIO',

		 ],

		 'SIEMENS' => [

		 	'SIEMENS',
		 	'SIEMENS - TECHNOLOGIES',
		 	'SIEMENS BUILDING TECHNOLOGIES LTD',
		 	'SIEMENS BUILDING TECHNOLOGIES LTD.',
		 	'SIEMENS CA LIMITED / LIMITEE',
		 	'SIEMENS CANADA LIMITED',
		 	'SIEMENS CANADA LIMITED / LIMITEE',
		 	'SIEMENS CANADA LTD',
		 	'SIEMENS CANADA LTD.',
		 	'SIEMENS DEMAG DELEVAL',
		 	'SIEMENS INDUSTRY SOFTWARE LTD.',
		 	'SIEMENS PLM',
		 	'SIEMENS PRODUCT LIFECYCLE',
		 	'SIEMENS WATER TECHNOLOGIES CANADA',

		 ],

		 'HITACHI DATA SYSTEMS' => [
		 	'HITACHI DATA SYSTEMS',
		 	'HITACHI DATA SYSTEMS INC.',
		 	'HITACHI HIGH TECHNOLOGIES CANADA INC.',
		 	'HITACHI HIGH-TECHNOLOGIES CANADA',
		 ],

		 'SYMANTEC' => [
		 	'SYMANTEC',
		 	'SYMANTEC CORPORATION',
		 ],

		 'COGNOS' => [
		 	'Cognos Inc.',
		 	'COGNOS INCORPORATED',
		 ],

		 'ADGA GROUP' => [
		 	'ADGA GROUP CONSULTANTS INC',
		 	'ADGA GROUP CONSULTANTS INC.',
		 	'ADGA GROUP THE',
		 ],

		 'PRICEWATERHOUSE COOPERS' => [
		 	'Pricewaterhouse Coopers',
		 	'PRICEWATERHOUSE COOPERS INC',
		 	'PRICEWATERHOUSE COOPERS LLP',
		 	'Pricewaterhouse Coopers Llp.',
		 	'PRICEWATERHOUSECOOPERS',
		 	'PRICEWATERHOUSECOOPERS (PWC) LLP',
		 	'PRICEWATERHOUSECOOPERS LLP',
		 	'PRICE WATER HOUSE',
		 	'PRICE WATERHOUSE COOPERS LLP',
		 	'PwC Consulting, A Business',
		 	'PWC Management Services LP',
		 ],

		 'BELL TEXTRON HELICOPTER' => [
		 	'BELL HELICOPTER',
		 	'BELL HELICOPTER TEXTRON CANADA LTD',
		 	'BELL HELICOPTER TEXTRON INC.',
		 ],

		 'MCKESSON CANADA' => [
		 	'McKesson  Canada',
		 	'MCKESSON AUTOMATION CANADA',
		 	'MCKESSON CA',
		 	'MCKESSON CA CORPORATION',
		 	'MCKESSON CANADA',
		 	'MCKESSON CANADA CORPORATION',
		 ],

		 'RANDSTAD' => [
		 	'RANDSTAD',
		 	'RANDSTAD CANADA',
		 	'RANDSTAD INTERIM INC',
		 	'RANDSTAD INTERIM INC.',
		 	'RANDSTAD PARC JARRY',
		 ],

		 'COMPUTER ASSOCIATES CANADA' => [
		 	'COMPUTER ASSOCIATES CANADA COM',
		 	'COMPUTER ASSOCIATES CANADA COMPANY',
		 	'Computer Associates Canada Ltd.',
		 ],

		 'COMPUCOM CANADA' => [
		 	'COMPUCOM',
		 	'COMPUCOM CANADA',
		 	'COMPUCOM CANADA CO',
		 	'COMPUCOM CANADA CO.',
		 ],

		 'TERAMACH TECHNOLOGIES' => [
		 	'TERAMACH',
		 	'TERAMACH TECHNOLOGICS INC',
		 	'TERAMACH TECHNOLOGIES INC',
		 	'TERAMACH TECHNOLOGIES INC.',
		 	'Teramach Techonologies',
		 ],

		 'MODIS CANADA' => [
		 	'MODIS  CANADA INC.',
		 	'MODIS CANADA INC',
		 	'MODIS CANADA INC.',
		 ],

		 'MAPLESOFT CONSULTING' => [
		 	'MAPLESOFT',
		 	'Maplesoft Administrative Services',
		 	'MAPLESOFT ADMINISTRATIVE SERVICES INC',
		 	'MAPLESOFT CONSULTING',
		 	'MAPLESOFT CONSULTING INC.',
		 	'MAPLESOFT LEGAL SUPPORT SERVICES',
		 	'Maplesoft Technology Inc.',
		 ],

		 'MobilShred (Recall)' => [
		 	'MobilShred (Recall)',
		 	'MobilShred Inc (Recall)',
		 	'MobilShred Inc.',
		 	'Mobilshred Inc. (Operating as Recal',
		 	'Mobilshred Inc. (Operating as Recall)',
		 	'MobilShred Inc. (Recall)',
		 ],

		 'G4S SECURITY SERVICES' => [
		 	'G4S CASH SOLUTIONS (CANADA)',
		 	'G4S SECURITY SERVICES (CANADA)',
		 	'G4S SECURITY SERVICES (CANADA) LTD.',
		 ],

		 'CAE' => [
		 	'CAE FLIGHTSCAPE INC',
		 	'CAE INC',
		 	'CAE Simuflite Inc.',
		 ],

		 'ITEX' => [
		 	'ITEX',
		 	'ITEX ENTERPRISE SOLUTIONS',
		 	'ITEX Entreprise Solutions',
		 	'ITEX INC',
		 	'ITEX INC.',
		 ],

		 'MARCOMM' => [
		 	'MARCOMM',
		 	'Marcomm Fibre Optics',
		 	'MARCOMM FIBRE OPTICS INC.',
		 	'MARCOMM INC',
		 	'MARCOMM INC.',
		 	'MARCOMM SYSTEMS GROUP INC',
		 ],

		 'CONEXSYS' => [
		 	'CONEXSYS COMMUNICATION LTD.',
		 	'Conexsys Communications',
		 	'CONEXSYS COMMUNICATIONS LIMITED',
		 	'CONEXSYS COMMUNICATIONS LTD',
		 	'CONEXSYS COMMUNICATIONS LTD.',
		 ],

		 'PROVINCIAL AIRLINES' => [
		 	'PROVINCIAL AEROSPACE LTD (PAL)',
		 	'PROVINCIAL AIRLINES LTD',
		 ],

		 'ADVANCED CHIPPEWA TECHNOLOGIES' => [
		 	'ADVANCED CHIPPEWA TECHNOLOGI',
		 	'ADVANCED CHIPPEWA TECHNOLOGIES',
		 	'ADVANCED CHIPPEWA TECHNOLOGIES INC',
		 	'ADVANCED CHIPPEWA TECHNOLOGIES INC.',
		 ],

		 'DNR CONSULTING GROUP' => [
		 	'DNR CONSULTING GROUP',
		 	'DNR CONSULTING GROUP INC.',
		 	'DNR GROUP',
		 	'DNR GROUP INC.',
		 ],

		 'NOVELL CANADA' => [
		 	'Novell',
		 	'NOVELL CANADA LTD',
		 	'NOVELL CANADA LTD.',
		 	'NOVELL CANADA,LTD.',
		 ],

		 'MCAFEE INTERNATIONAL' => [
		 	'MCAFEE INTERNATIONAL B.V.',
		 	'MCAFEE IRELAND LIMITED',
		 ],

		 'DELOITTE AND TOUCHE' => [
		 	'Deloitte',
		 	'DELOITTE & TOUCHE',
		 	'DELOITTE & TOUCHE LLP',
		 	'DELOITTE & TOUCHE, LLP',
		 	'Deloitte and Touche',
		 	'DELOITTE AND TOUCHE LLP',
		 	'DELOITTE AND TOUCHE, LLP',
		 	'DELOITTE CONSULTING INC.',
		 	'DELOITTE INC',
		 	'Deloitte Inc.',
		 	'Deloitte LLP',
		 ],

		 'ATCO FRONTEC' => [
		 	'ATCO FRONTEC CORP.',
		 	'ATCO STRUCTURES & LOGISTICS LTD.',
		 	'ATCO STRUCTURES AND LOGISTICS',
		 ],

		 'IBISKA TELECOM' => [
		 	'IBISKA',
		 	'IBISKA TELECOM INC.',
		 ],

		 'OEI KRUEGER' => [
		 	'OEI',
		 	'OEI KRUEGER INTERNATIONAL INC',
		 ],

		 'EXCEL HUMAN RESOURCES' => [
		 	'EXCEL HR',
		 	'Excel Human Resources',
		 	'EXCEL HUMAN RESOURCES INC',
		 	'EXCEL HUMAN RESOURCES INC.',
		 	'Excel Human Ressources Inc',
		 	'Excel Human Ressources Inc.',
		 ],

		 'MINDWIRE SYSTEMS' => [
		 	'MINDWIRE',
		 	'Mindwire (Zylog)',
		 	'MINDWIRE SYSTEMS LTD',
		 	'MINDWIRE SYSTEMS LTD.',
		 ],

		 'EBSCO CANADA' => [
		 	'EBSCO Canada',
		 	'EBSCO Canada Ltd',
		 	'EBSCO CANADA LTD.',
		 	'EBSCO CANADA LTEE',
		 	'Ebsco Canada Lt̩e',
		 	'EBSCO CANADA LT��E',
		 	'EBSCO PUBLISHING',
		 ],

		 'CISTEL TECHNOLOGY' => [
		 	'Cistel',
		 	'CISTEL TECHNOLOGY',
		 	'Cistel Technology Inc',
		 	'CISTEL TECHNOLOGY INC TECSIS CORPORATION IN JOINT VENTURE',
		 	'CISTEL TECHNOLOGY INC.',
		 ],

		 'CITRIX' => [
		 	'Citrix',
		 	'CITRIX ONLINE LLC',
		 	'CITRIX SYSTEMS INC',
		 	'CITRIX SYSTEMS INC.',
		 	'CITRIX SYSTEMS, INC.',
		 ],

		 'NISHA TECHNOLOGIES' => [
		 	'Nisha',
		 	'NISHA    Technologie',
		 	'NISHA TECHNOLOGIE INC.',
		 	'Nisha Technologies',
		 	'NISHA TECHNOLOGIES INC',
		 	'NISHA TECHNOLOGIES INC.',
		 ],

		 'ECLIPSYS SOLUTIONS' => [
		 	'ECLIPSYS',
		 	'ECLIPSYS SOLUTIONS INC',
		 	'ECLIPSYS SOLUTIONS INC.',
		 ],

		 'TRANSPOLAR TECHNOLOGY' => [
		 	'TRANSPOLAR TECHNOLOGY CORP',
		 	'TRANSPOLAR TECHNOLOGY CORP.',
		 	'TRANSPOLAR TECHNOLOGY CORPORATION',
		 ],

		 'MOTOROLA SOLUTIONS CANADA' => [
		 	'MOTOROLA CANADA LTD',
		 	'MOTOROLA NATIONAL PARTS',
		 	'MOTOROLA SOLUTIONS CANADA INC',
		 	'MOTOROLA SOLUTIONS CANADA INC.',
		 ],

		 'RESOLVE MARINE GROUP' => [
		 	'RESOLVE',
		 	'RESOLVE MARINE GROUP INC',
		 ],

		 'SALVATION ARMY' => [
		 	'SALVATION ARMY',
		 	'SALVATION ARMY ARC',
		 	'SALVATION ARMY BELKIN HOUSE',
		 	'SALVATION ARMY BOOTH CENTRE BRANTFORD',
		 	'SALVATION ARMY CENTRE OF HOPE',
		 	'SALVATION ARMY CORRECTIONAL &',
		 	'SALVATION ARMY CORRECTIONAL AND JUSTICE SERVICES GREENFIELD HOUSE THE',
		 	'SALVATION ARMY HARBOUR LIGHT',
		 	'SALVATION ARMY HARBOUR LIGHTS CORP',
		 	'SALVATION ARMY WAGNER COTTAGE',
		 ],

		 'VERITAS TECHNOLOGIES' => [
		 	'Veritas',
		 	'VERITAS TECHNOLOGIES LLC',
		 ],

		 'SIERRA SYSTEMS GROUP' => [
		 	'SIERRA SYSTEMS',
		 	'SIERRA SYSTEMS GROUP INC',
		 	'SIERRA SYSTEMS GROUP INC.',
		 ],


	];

}
