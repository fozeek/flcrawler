<?php
	class Crawler {
		private $_url = 'http://www.google.com';
		private $_depth = 1;
		private $_tmpAuthority = '';
		private $_isGoogle = true;

		private $_htmlPath = 'crawled_content/fichier_html';
		private $_emailPath = 'crawled_content/fichier_email';
		private $_phonePath = 'crawled_content/fichier_telephone';
		private $_jobPath = 'crawled_content/fichier_job';
		private $_linkPath = 'crawled_content/fichier_lien';
		private $_tmpLinkPath = 'crawled_content/tmp_fichier_lien';

		private $_emailPattern = '#[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}#U';
		private $_phonePattern = '#(0[1-9][-.\s]?(\d{2}[-.\s]?){3}\d{2})#';
		private $_jobPattern = '/<div id="headline" class="editable-item">(.*?)<\/div>/si';
		private $_linkPattern = '#<a href="([^"]*)">[^<]*</a>#U';
		private $_googleLinkPattern = '/<h3 class="r"><a href="(.*?)"/si';

		private $_uselessLinks = array(
			'',
			'#',
			'/search?', 
			'/intl/fr/about.html', 
			'/intl/fr/policies/', 
			'/preferences?hl=fr', 
			'http://www.google.fr/history/optout?hl=fr', 
			'/search?site=&amp;ie=UTF-8&amp;q=Phila%C3%A9+atterrisseur&amp;oi=ddle&amp;ct=philae-robotic-lander-lands-on-comet-67pchuryumovgerasimenko-5668009628663808-hp&amp;hl=fr',
			'/advanced_search?hl=fr&amp;authuser=0',
			'/language_tools?hl=fr&amp;authuser=0',
			'/intl/fr/ads/',
			'/services/',
			'https://www.google.fr/setprefdomain?prefdom=US&amp;sig=0_m-wd5ZG_N6uYXKRWjI1fGf8eyHM%3D'
		);
		
		public function __construct($url = "", $depth = 1) {
			if(!empty($url))
				$this->_url = $url;

			$this->_depth = $depth;
		}

		public function run(){
			for($cpt = 0; $cpt < $this->_depth; $cpt++){
				// on appelle une première fois la fonction avec l'url racine
				if(empty($this->_tmpAuthority))
					$this->crawl($this->_url);
				else
					$this->crawl($this->_tmpAuthority);

				// ensuite on ouvre le fichier de liens pour visiter les autres pages du site
				$tmp_file = $this->getOrCreateFile($this->_tmpLinkPath);

				file_put_contents($this->_tmpLinkPath, file_get_contents($this->_linkPath));
				file_put_contents($this->_linkPath, '');
						
				// on créé une boucle pour visiter chacun des liens
				// on stop cette boucle quand le curseur arrive à la fin du fichier
				$numero_de_ligne = 1;

				while(!feof($tmp_file)) {
				    // curl ne comprend que les liens absolus
				    // on formate donc nos liens relatifs en liens absolus
				    $url = fgets($tmp_file);
				    $page_suivante = '';

				    if(!in_array($url, $this->_uselessLinks)){
					    if($this->str_ends_with($url, '/'))
					    	substr($url, 0, strlen($url) - 1);
					    if(!$this->str_starts_with($url, 'http://') && !$this->str_starts_with($url, 'https://'))
					    	$page_suivante = $this->_url;
					    $page_suivante .= $url;

					    echo $numero_de_ligne . ' Analyse en cours, page : ' .  $page_suivante . '<br/>';
					    $numero_de_ligne++;
					          
					    //on se contente de rappeler la fonction crawl avec nos nouveaux liens
					    $this->crawl(trim($page_suivante));
					}
				}
				fclose ($tmp_file);
			}
		}
		
		private function crawl($url) {
			try {
				// initialisation de curl
		        $ch = curl_init($url);

		        if (FALSE === $ch)
        			throw new Exception('failed to initialize');

		        $fp_fichier_html_brut = $this->getOrCreateFile($this->_htmlPath);
		        
		        // définition des paramètres curl
		        curl_setopt($ch, CURLOPT_FILE, $fp_fichier_html_brut);
		        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
		        curl_setopt($ch, CURLOPT_HEADER, 0);
		        
		        // exécution de curl
		        $result = curl_exec($ch);
		        
		        // fermeture de la session curl

		        if (FALSE === $result)
        			throw new Exception(curl_error($ch), curl_errno($ch));
		    }
		     catch(Exception $e) {
			    trigger_error(sprintf('Curl failed with error #%d: %s', $e->getCode(), $e->getMessage()), E_USER_ERROR);
			}
	        
	        curl_close($ch);

	        // fermeture du fichier texte
	        fclose($fp_fichier_html_brut);

	        // passage du contenu du fichier à une variable pour analyse
    		$html_brut = file_get_contents($this->_htmlPath);

    		
	        $this->crawl_emails($html_brut);
	        $this->crawl_links($html_brut);
	        $this->crawl_phones($html_brut);
	        $this->crawl_jobs($html_brut);
		}

		private function crawl_links($html_brut){
			// extraction des liens
			if($this->_isGoogle)
				preg_match_all($this->_googleLinkPattern, $html_brut, $liens_extraits);
			else
	        	preg_match_all($this->_linkPattern, $html_brut, $liens_extraits);

	        if(!$this->_isGoogle)
		        $liens_extraits = $liens_extraits[1];
		    else
    			$liens_extraits = $liens_extraits[0];

	        $fp_fichier_liens = $this->getOrCreateFile($this->_linkPath, false);

			// on créé une boucle pour enregistrer tous les liens ds le fichier
            foreach ($liens_extraits as $element) {		
            	// on recharge le contenu dans la variable à chaque tour de boucle
            	// pour être à jour si le lien est present +sieurs x sur la même page
            	$gestion_doublons = file_get_contents($this->_linkPath);
            		
            	// on enlève les "" qui entourent les liens
            	if($this->_isGoogle){
	                $element = preg_replace('#<h3 class="r"><a href="#', '', $element);
	                $element = preg_replace('#/url\?q=#', '', $element);
	                $element = preg_replace('#&amp;sa=.+#', '', $element);
	            }
	            else {
	            	//$element = preg_replace('#href=".+"#', '', $element);
	                //$element = preg_replace('#"#', '', $element);
	            }
                $follow_url = $element;
                $follow_url .= "\r\n";
                    
                // creation d'un pattern pour la verification ds doublons
                $pattern = '#' . $follow_url . '#';

				// on verifie grace au pattern précédemment créé
				// que le lien qu'on vient de capturer n'est pas déjà ds le fichier
                if (!preg_match($pattern, $gestion_doublons))
                    fputs($fp_fichier_liens, $follow_url);
            }
	        $this->_isGoogle = false;
		
			//fermeture fu fichier contenant les liens
	        fclose($fp_fichier_liens);
		}

		private function crawl_emails($html_brut){
			// extraction des emails
			preg_match_all($this->_emailPattern, $html_brut, $adresses_mail);
        
	        // creation d'un fichier pour recevoir les mails
	        $fp_fichier_emails = $this->getOrCreateFile($this->_emailPath, false);
        
	        // on creer une boucle pour placer tous les mails de la page dans le fichier
	        foreach ($adresses_mail[0] as $element) {
	        	// on "nettoie" les mails en enlevant les guillemets et le "mailto:"
	        	// on passe donc de "mailto:addr@gmail.com" à addr@gmail.com
	        	$element = preg_replace('#"#', '', $element);
				$element = preg_replace('#mailto:#', '', $element);
				
				// on ajoute un retour chariot en fin de ligne pour avoir 1 mail/ligne
				$element .= "\r\n";
	        	fputs($fp_fichier_emails, $element);
	        }
        
        	fclose($fp_fichier_emails);
		}

		private function crawl_phones($html_brut){
			// extraction des emails
			preg_match_all($this->_phonePattern, $html_brut, $tels);
        
	        // creation d'un fichier pour recevoir les mails
	        $fp_fichier_phones = $this->getOrCreateFile($this->_phonePath, false);
        
	        // on creer une boucle pour placer tous les mails de la page dans le fichier
	        foreach ($tels[0] as $element) {				
				// on ajoute un retour chariot en fin de ligne pour avoir 1 mail/ligne
				$element .= "\r\n";
	        	fputs($fp_fichier_phones, $element);
	        }
        
        	fclose($fp_fichier_phones);
		}

		private function crawl_jobs($html_brut){
			// extraction des emails
			preg_match_all($this->_jobPattern, $html_brut, $tels);
        
	        // creation d'un fichier pour recevoir les mails
	        $fp_fichier_phones = $this->getOrCreateFile($this->_jobPath, false);
        
	        // on creer une boucle pour placer tous les mails de la page dans le fichier
	        foreach ($tels[0] as $element) {				
				// on ajoute un retour chariot en fin de ligne pour avoir 1 mail/ligne
				$element = strip_tags($element);
				$element .= "\r\n";
	        	fputs($fp_fichier_phones, $element);
	        }
        
        	fclose($fp_fichier_phones);
		}

		private function str_starts_with($haystack, $needle){
		    return strpos($haystack, $needle) === 0;
		}
		private function str_ends_with($haystack, $needle){
		    return strpos($haystack, $needle) + strlen($needle) === strlen($haystack);
		}

		private function getOrCreateFile($path, $erase = true){
			// création d'un fichier texte pour stocker le contenu crawlé
	        // effacement du fichier précédent si existe
	        if(file_exists($path) && $erase)
	        	unlink($path);

	        $file = fopen($path, 'a+');

	        return $file;
		}
	}