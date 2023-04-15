<?php

// Crawler
use Symfony\Component\Panther\Client;
use Symfony\Component\DomCrawler\Crawler;

require __DIR__ . '/vendor/autoload.php';

$client  = Client::createChromeClient();
$url     = 'https://www.jleague.co/match/j1/2023041506';
$crawler = $client->request( 'GET', $url );
$crawler = $client->waitFor( '.player-events__body' );

// Helper functions
function get_string_between( $string, $start, $end ): string {
	$string = ' ' . $string;
	$ini    = strpos( $string, $start );
	if ( $ini == 0 ) {
		return '';
	}
	$ini += strlen( $start );
	$len = strpos( $string, $end, $ini ) - $ini;

	return substr( $string, $ini, $len );
}

function clean( $string ): array|string|null {
	$string = str_replace( ' ', '-', $string ); // Replaces all spaces with hyphens.

	return preg_replace( '/[^A-Za-z0-9\-]/', '', $string ); // Removes special chars.
}

// Round
$round_text = $crawler->filter( '.competition-title' )->text();
$round      = substr( $round_text, - 1 );

// Date
$date_time = $crawler->filter( '.match-date-time' )->text();
$date_raw  = get_string_between( $date_time, ',', '·' );
$date      = trim( ucwords( strtolower( $date_raw ) ) );

// Time
$time = trim( get_string_between( $date_time, 'OFF', 'JST' ) );

// Teams
$team_one = $crawler->filter( '.summary-teams__team--home' )->text();
$team_two = $crawler->filter( '.summary-teams__team--away' )->text();

// Score
$score = clean( $crawler->filter( '.summary-teams__result' )->text() );

// Meta
$meta = $crawler->filter( '.match-extra-info-item__value' )->each( function ( Crawler $node, $i ) {
	return $node->text();
} );

$stadium    = $meta[0];
$attendance = $meta[2];
$referee    = ucwords( strtolower( $meta[3] ) );

$football_box = array(
	'round'      => $round,
	'date'       => $date,
	'time'       => $time,
	'team1'      => $team_one,
	'score'      => $score,
	'team2'      => $team_two,
	'report'     => $url,
	'goals1'     => '',
	'goals2'     => '',
	'stadium'    => $stadium,
	'location'   => '',
	'attendance' => $attendance,
	'referee'    => $referee,
	'result'     => '',
);

var_dump( $football_box );

