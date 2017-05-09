#!/usr/bin/perl -w

$| = 1;

use strict;
use Davin::Session;
use Text::CSV::Encoded;
use Date::Format;
use Data::Dumper;
use Digest::MD5 qw(md5_hex);

my $session = new Davin::Session;
my $dbh = $session->dbh;
my %data;
my %config;

$config{industry1}{sort} = 1;
$config{industry1}{filename} = "/local/contracts/corporations.csv";
$config{industry1}{table} = "contract.industry";
$config{industry1}{import_key} = "primaryIndustry";
$config{industry1}{map}{'primaryIndustry'} = "name";
$config{industry1}{map}{'primaryIndustryNAICS'} = "naics";

$config{industry2}{sort} = 2;
$config{industry2}{filename} = "/local/contracts/corporations.csv";
$config{industry2}{table} = "contract.industry";
$config{industry2}{import_key} = "alternateIndustry";
$config{industry2}{map}{'alternateIndustry'} = "name";
$config{industry2}{map}{'alternateIndustryNAICS'} = "naics";

$config{activity}{sort} = 3;
$config{activity}{filename} = "/local/contracts/corporations.csv";
$config{activity}{table} = "contract.activity";
$config{activity}{import_key} = "primaryBusinessActivity";
$config{activity}{map}{'primaryBusinessActivity'} = "name";

$config{corp}{sort} = 10;
$config{corp}{filename} = "/local/contracts/corporations.csv";
$config{corp}{table} = "contract.company";
$config{corp}{import_key} = "filename";
$config{corp}{map}{'operatingName'} = "operating_name";
$config{corp}{map}{'telephone'} = "telephone";
$config{corp}{map}{'email'} = "email";
$config{corp}{map}{'employees'} = "employees";
$config{corp}{map}{'yearEstablished'} = "year_established";
$config{corp}{map}{'exporting'} = "exporting";
$config{corp}{map}{'website'} = "url";
$config{corp}{map}{'alternateName'} = "alternate_name";
$config{corp}{map}{'locationAddress'} = "location_address";
$config{corp}{map}{'legalName'} = "legal_name";
$config{corp}{map}{'mailingAddress'} = "mailing_address";
$config{corp}{lookup}{'primaryIndustry'} = "contract.industry/primary_industry_id";
$config{corp}{lookup}{'alternateIndustry'} = "contract.industry/primary_industry_id";
$config{corp}{lookup}{'primaryBusinessActivity'} = "contract.activity/primary_business_activity_id";

$config{dept}{sort} = 20;
$config{dept}{filename} = "/local/contracts/contracts.csv";
$config{dept}{table} = "contract.department";
$config{dept}{import_key} = "Department";
$config{dept}{map}{'Department'} = "name";

$config{category}{sort} = 30;
$config{category}{filename} = "/local/contracts/contracts.csv";
$config{category}{table} = "contract.category";
$config{category}{import_key} = "Description";
$config{category}{map}{'Description'} = "name";

$config{contract}{sort} = 50;
$config{contract}{filename} = "/local/contracts/contracts.csv";
$config{contract}{table} = "contract.contract";
$config{contract}{import_key} = "ID";
$config{contract}{post_process} = \&process_contract;
$config{contract}{map}{'ID'} = "contract_number";
$config{contract}{map}{'Vendor Name'} = "company_name";
$config{contract}{map}{'Contract Value'} = "contract_value";
$config{contract}{map}{'Contract Date'} = "contract_date";
$config{contract}{map}{'Start Year'} = "start_year";
$config{contract}{map}{'End Year'} = "end_year";
$config{contract}{map}{'Duration in Years'} = "duration";
$config{contract}{map}{'Value Per Year'} = "yearly_value";
$config{contract}{map}{'Original Value'} = "original_value";
$config{contract}{map}{'Number of Amendments'} = "ammendment_count";
$config{contract}{map}{'Original Vendor Name'} = "company_name_orig";
$config{contract}{lookup}{'Department'} = "contract.department/department_id";
$config{contract}{lookup}{'Description'} = "contract.category/category_id";
$config{contract}{lookup}{'Vendor Name'} = "contract.company/company_id";

# --------------------------------------------------------------------------
# Main

my $start_time = time2str("%Y-%m-%d %T", time);

foreach my $key (sort {$config{$a}{sort} <=> $config{$b}{sort}} keys %config) {
#	next if ($key =~ /industry/);
#	next if ($key =~ /activity/);
#	next if ($key =~ /corp/);
#	next if ($key =~ /dept/);
#	next if ($key =~ /category/);
#	next if ($key =~ /contract/);
	go ($key);
}

foreach my $key (sort {$config{$a}{sort} <=> $config{$b}{sort}} keys %config) {
	cleanup ($key);
}

# --------------------------------------------------------------------------
# Subroutines

sub go {
	my ($key) = @_;

	my $filename = $config{$key}{filename};
	my $table = $config{$key}{table};
	my $import_key = $config{$key}{import_key};
	my $post_process = $config{$key}{post_process};
	my $success = 0;
	my $fail = 0;

	print ">> Processing $key: $filename: ";

	read_db ($table);

	my @columns;
	my $csv = Text::CSV::Encoded->new ({ encoding  => "utf8" });
	my $fh;
	open ($fh, "<", $filename);
	my $count = 0;
	while (<$fh>) {
		$count++;
		$csv->parse ($_);
		my @fields = $csv->fields ();
		if ($count == 1) {
			foreach my $field (@fields) {
				$field =~ s/[^[:ascii:]]//g;
				push @columns, $field;
			}
		} else {
			my %entry;

			# ---------- primary key

			foreach my $i (0 .. $#columns) {
				next if ($columns[$i] ne $import_key);
				my $value = fixup_value ($fields[$i]);
				if (defined $value) {
					#print "KEY: $columns[$i]: $value\n";
					$entry{import_key} = md5_hex($value);
				}
			}

			if (! $entry{import_key}) {
				#no warnings;
				#warn "Primary key missing: file=<$filename> line=<$count>: " . join ('|', @fields) . "\n";
				$fail++;
				next;
			}

			# ---------- plain entries

			foreach my $i (0 .. $#columns) {
				my $column = $config{$key}{map}{$columns[$i]};
				next if (! $column);
				my $value = fixup_value ($fields[$i]);

				$entry{$column} = $value;
			}

			# ---------- lookup tables

			foreach my $i (0 .. $#columns) {
				my $column = $config{$key}{lookup}{$columns[$i]};
				next if (! $column);
				my $value = fixup_value ($fields[$i]);
				my ($src_table, $dest_column) = split (/\//, $column);
				if (defined $value) {
					$value = $data{$src_table}{md5_hex($value)}{id};
				}
				$entry{$dest_column} = $value;
			}

			# preserve certain values from the existing database record (unlisted values will not be overridden)
			if ($data{$table}{$entry{import_key}}{id}) {
				$entry{id} = $data{$table}{$entry{import_key}}{id};
				$entry{create_time} = $data{$table}{$entry{import_key}}{create_time};
			}

			if ($post_process) {
				&$post_process (\%entry);
			}

			write_db ($table, \%entry);
			$data{$table}{$entry{import_key}} = \%entry;
			$success++;
		}
	}

	print "success=<$success> fail=<$fail>\n";
}

sub cleanup {
	my ($key) = @_;

	my $table = $config{$key}{table};

	# delete the records that disapeared
	$dbh->do("DELETE FROM $table WHERE update_time < '$start_time'");
}

sub read_db {
	my ($table) = @_;

	my $sql = "SELECT * FROM $table";
	my $import = $dbh->selectall_hashref ($sql, ['import_key']);
	$data{$table} = $import;
}

sub write_db {
	my ($table, $record) = @_;

	my $time = time2str("%Y-%m-%d %T", time);
	my $found = (defined $$record{id}) ? 1 : 0;
	my @columns;

	$$record{update_time} = $time;
	$$record{create_time} = $time if (! defined $$record{create_time});

	foreach my $key (sort keys %$record) {
		push (@columns, "$key = " . $dbh->quote($$record{$key}));
	}
	if ($found) {
		my $sql = "UPDATE $table SET " . join(', ', @columns) . " WHERE id = $$record{id}";
		#print ">> $sql\n";
		$dbh->do($sql);
	} else {
		my $sql = "INSERT into $table SET " . join(', ', @columns);
		#print ">> $sql\n";
		$dbh->do($sql);
		$$record{id} = $dbh->last_insert_id (undef, undef, undef, undef);
	}
}

sub fixup_value {
	my ($value) = @_;

	if (defined $value) {
		$value =~ s/^\s+//;
		$value =~ s/\s+$//;
		$value =~ s/\s+/ /;
		$value = undef if ($value eq '');
	}

	return $value;
}

sub process_contract {
	my ($entry) = @_;

	if (! defined $$entry{company_id} && $$entry{company_name}) {
		my $lookup = $dbh->quote($$entry{company_name});
		my ($company_id) = $dbh->selectrow_array ("SELECT id FROM contract.company WHERE (operating_name = $lookup OR legal_name = $lookup) LIMIT 1");
		$$entry{company_id} = $company_id;
		#print ">>> Process $$entry{id}: $$entry{company_name} >> $company_id\n";
	}
}
