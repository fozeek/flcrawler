<?php
	ini_set("auto_detect_line_endings", true);
			
	require 'crawler.php';

	$firstname = "thibault";
	$lastname = "dulon";
	$url = 'https://www.google.fr/search?hl=fr&q=' . $firstname . '+' . $lastname . '&oq=' . $firstname;

	$Crawler = new Crawler($url, 1, include 'config.php');

	$Crawler->run();
?>
