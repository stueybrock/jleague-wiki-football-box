<?php

// Helper functions
require 'functions-strings.php';
require 'functions-locations.php';
require 'functions-wikipedia.php';

// Crawler
use Symfony\Component\Panther\Client;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\CssSelector\CssSelectorConverter;

require __DIR__ . '/vendor/autoload.php';

$client  = Client::createChromeClient();
$url     = 'https://www.jleague.co/match/j1/2023041502';
$crawler = $client->request( 'GET', $url );
$crawler = $client->waitFor( '.player-events__body' );

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
		// Player name
		list( $first_name, $last_name ) = explode( " ", $event->text() );

		// Minute
		$minute = $parentCrawler->filterXPath( $converter->toXPath( '.timeline-item__time' ) );
		$minute = rtrim( $minute->text(), '\'' );

		// Type
		$event_class = $parentCrawler->filterXPath( $converter->toXPath( '.match-event-icon' ) )->attr( 'class' );
		$event_type  = preg_replace( '/^match-event-icon match-event-icon--/', '', $event_class );

		return array(
			'player_first_name' => ucwords( strtolower( $first_name ) ),
			'player_last_name'  => ucwords( strtolower( $last_name ) ),
			'minute'            => $minute,
			'event_type'        => $event_type,
		);
	}
}

function build_event_string( $event ): string {
	$first_name = $event['player_first_name'];
	$last_name  = $event['player_last_name'];
	$event_type = $event['event_type'];

	$event_output = match ( $event_type ) {
		'yellow' => '{{yel|' . $event['minute'] . '}}',
		'penalty-goal' => '{{goal|' . $event['minute'] . '|pen.}}',
		'own-goal' => '{{goal|' . $event['minute'] . '|o.g.}}',
		'red' => '{{sent off|0|' . $event['minute'] . '}}',
		'yellow-red' => '{{sent off|2|' . $event['minute'] . '}}',
		default => '{{goal|' . $event['minute'] . '}}',
	};

	if ( empty( $first_name ) && empty( $last_name ) ) {
		$player_link = '';
	} else {
		$player_link = '[[' . $first_name . ' ' . $last_name . '|' . $last_name . ']] ';
	}

	return '*' . $player_link . $event_output;
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

// Merge goals and cards.
$home_events = array_merge( $home_goals, $home_cards );
$away_events = array_merge( $away_goals, $away_cards );

// Find minutes of each event.
$home_minutes = array_column( $home_events, 'minute' );
$away_minutes = array_column( $away_events, 'minute' );

// Sort the match events in chronological order.
array_multisort( $home_minutes, SORT_ASC, $home_events );
array_multisort( $away_minutes, SORT_ASC, $away_events );

$home_events_string = '';
foreach ( $home_events as $home_event ) {
	$home_events_string .= build_event_string( $home_event ) . "\n";
}

$away_events_string = '';
foreach ( $away_events as $away_event ) {
	$away_events_string .= build_event_string( $away_event ) . "\n";
}


// Meta
$meta = $crawler->filter( '.match-extra-info-item__value' )->each( function ( Crawler $node, $i ) {
	return $node->text();
} );

$stadium    = ucwords( strtolower( $meta[0] ) );
$attendance = $meta[2];
$referee    = ucwords( strtolower( $meta[3] ) );

// Location
$location = location_from_stadium_name( $meta[0] );

$football_box = array(
	'round'      => $round,
	'date'       => $date,
	'time'       => $time . " [[Japan Standard Time|JST]]",
	'team1'      => string_to_wiki_link( $team_one ),
	'score'      => $score,
	'team2'      => string_to_wiki_link( $team_two ),
	'report'     => $url,
	'goals1'     => $home_events_string,
	'goals2'     => $away_events_string,
	'stadium'    => string_to_wiki_link( $stadium ),
	'location'   => $location,
	'attendance' => $attendance,
	'referee'    => $referee,
	'result'     => '',
);

//var_dump( $football_box );

//echo "{{football box collapsible \n";
//foreach ( $football_box as $key => $item ) {
//	echo "|" . $key . "=" . $item . "\n";
//}
//echo "}}";
?>

{{football box collapsible
|round      = <?= $football_box['round'] . "\n" ?>
|date       = <?= $football_box['date'] . "\n" ?>
|time       = <?= $football_box['time'] . "\n" ?>
|team1      = <?= $football_box['team1'] . "\n" ?>
|score      = <?= $football_box['score'] . "\n" ?>
|report     = <?= $football_box['report'] . "\n" ?>
|team2      = <?= $football_box['team2'] . "\n" ?>
|goals1     =
<?= $football_box['goals1'] ?>
|goals2     =
<?= $football_box['goals2'] ?>
|stadium    = <?= $football_box['stadium'] . "\n" ?>
|location   = <?= $football_box['location'] . "\n" ?>
|attendance = <?= $football_box['attendance'] . "\n" ?>
|referee    = <?= $football_box['referee'] . "\n" ?>
|result     =
}}
