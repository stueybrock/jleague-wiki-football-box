<?php

include ('vendor/autoload.php');

// $client = \Symfony\Component\Panther\Client::createChromeClient(__DIR__.'/../../drivers/chromedriver');
$client = \Symfony\Component\Panther\Client::createChromeClient(__DIR__.'/drivers/chromedriver');
$crawler = $client->request('GET', 'https://example.com');
$fullPageHtml = $crawler->html();
$pageH1 = $crawler->filter('h1')->text();