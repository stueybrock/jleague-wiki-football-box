<?php

// Crawler
use Symfony\Component\Panther\Client;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\CssSelector\CssSelectorConverter;

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

// Match events
function get_match_event_arr( $converter, $parentCrawler, $event ) {
	if ( ! empty( $event->count() ) ) {
		$minute = $parentCrawler->filterXPath( $converter->toXPath( '.timeline-item__time' ) );
		$minute = rtrim( $minute->text(), '\'' );

		return array(
			'player_name' => $event->text(),
			'minute'      => $minute,
		);
	}
}

$home_goals = array();
$crawler->filter( '.timeline-item' )->each( function ( Crawler $parentCrawler, $i ) use ( &$home_goals ) {

	$converter = new CssSelectorConverter();

	$home_goals_event = $parentCrawler->filterXPath( $converter->toXPath( '.timeline-item__team--home .timeline-detail--goal' ) );

	if ( ! empty( $home_goals_event->count() ) ) {
		$home_goals[] = get_match_event_arr( $converter, $parentCrawler, $home_goals_event );
	}

	return $home_goals;
} );

$away_goals = array();
$crawler->filter( '.timeline-item' )->each( function ( Crawler $parentCrawler, $i ) use ( &$away_goals ) {

	$converter = new CssSelectorConverter();

	$away_goals_event = $parentCrawler->filterXPath( $converter->toXPath( '.timeline-item__team--away .timeline-detail--goal' ) );

	if ( ! empty( $away_goals_event->count() ) ) {
		$away_goals[] = get_match_event_arr( $converter, $parentCrawler, $away_goals_event );
	}

	return $away_goals;
} );

$home_cards = array();
$crawler->filter( '.timeline-item' )->each( function ( Crawler $parentCrawler, $i ) use ( &$home_cards ) {

	$converter = new CssSelectorConverter();

	$home_cards_event = $parentCrawler->filterXPath( $converter->toXPath( '.timeline-item__team--home .timeline-detail--warning' ) );

	if ( ! empty( $home_cards_event->count() ) ) {
		$home_cards[] = get_match_event_arr( $converter, $parentCrawler, $home_cards_event );
	}

	return $home_cards;
} );

$away_cards = array();
$crawler->filter( '.timeline-item' )->each( function ( Crawler $parentCrawler, $i ) use ( &$away_cards ) {

	$converter = new CssSelectorConverter();

	$away_cards_event = $parentCrawler->filterXPath( $converter->toXPath( '.timeline-item__team--away .timeline-detail--warning' ) );

	if ( ! empty( $away_cards_event->count() ) ) {
		$away_cards[] = get_match_event_arr( $converter, $parentCrawler, $away_cards_event );
	}

	return $away_cards;
} );

var_dump( $home_goals );
var_dump( $away_goals );
var_dump( $home_cards );
var_dump( $away_cards );

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

