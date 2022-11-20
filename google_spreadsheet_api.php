<?php
require __DIR__ . '/vendor/autoload.php';

/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */


// Get the API client and construct the service object.
$client = new Google\Client();
$client->setApplicationName('Google Sheets Test');
$client->setScopes( [\Google_Service_Sheets::SPREADSHEETS] );
$client->setAccessType('offline');
$client->setAuthConfig(__DIR__ . '/secret.json');

$service = new Google_Service_Sheets( $client );

// Prints the names and majors of students in a sample spreadsheet:
// https://docs.google.com/spreadsheets/d/1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms/edit
$spreadsheetId = '';
$range = 'Sheet1!A2:E';
$response = $service->spreadsheets_values->get( $spreadsheetId, $range );
$values = $response->getValues();

if ( empty( $values ) ) {
	print "No data found.\n";
}
else {
	print "Name, Major:\n";
	foreach ( $values as $row ) {
		// Print columns A and E, which correspond to indices 0 and 4.
		printf( "%s, %s\n", $row[0], $row[4] );
	}
}
