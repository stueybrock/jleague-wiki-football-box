<?php
function location_from_stadium_name( $stadium_name ) {
	return match ( $stadium_name ) {
		'Kashima Soccer Stadium' => '[[Kashima, Ibaraki|Kashima]]', // Kashima Antlers
		'NHK Spring Mitsuzawa Football Stadium' => '[[Yokohama]]', // Yokohama FC
		'DENKA BIG SWAN STADIUM' => '[[Niigata (city)|Niigata]]', // Albirex Niigata
		'Saitama Stadium 2002' => '[[Saitama (city)|Saitama]]', // Urawa Reds
		'Lemon Gas Stadium Hiratsuka' => '[[Hiratsuka]]', // Shonan Bellmare
		'Kawasaki Todoroki Stadium' => '[[Kawasaki, Kanagawa|Kawasaki]]', // Kawasaki Frontale
		'EKIMAE REAL ESTATE STADIUM' => '[[Tosu, Saga|Tosu]]', // Sagan Tosu
		'Ajinomoto Stadium' => '[[Chōfu]]', // FC Tokyo
		'SANGA STADIUM by KYOCERA' => '[[Kameoka, Kyoto|Kameoka]]', // Kyoto Sanga
		'Nissan Stadium' => '[[Yokohama]]', // Yokohama F. Marinos
		'Yodoko Sakura Stadium' => '[[Osaka]]', // Cerezo Osaka
		'NOEVIR Stadium Kobe' => '[[Kobe]]', // Vissel Kobe
		'EDION Stadium Hiroshima' => '[[Hiroshima]]', // Sanfrecce Hiroshima
		'Toyota Stadium' => '[[Toyota, Aichi|Toyota]]', // Nagoya Grampus
		'Panasonic Stadium Suita' => '[[Suita]]', // Gamba Osaka
		'BEST DENKI STADIUM' => '[[Fukuoka]]', // Avispa Fukuoka
		'SANKYO FRONTIER Kashiwa Stadium' => '[[Kashiwa]]', // Kashiwa Reysol
		'Sapporo dome' => '[[Sapporo]]', // Hokkaido Consadole Sapporo
		default => '',
	};
}
