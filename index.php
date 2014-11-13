<?php
	ini_set("auto_detect_line_endings", true);
			
	require 'crawler.php';

	$Crawler = new Crawler('https://www.google.fr/search?hl=fr&q=ludovic+nitoumbi&start=1', 1);

	$Crawler->run();
?>
