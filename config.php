<?php

return [
    
    'known_websites' => [
        'google' => function($url) {
            return (bool)preg_match('#google.(fr|com)#', $url);
        }
    ],

    'void_links' => [
        'google' => [
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
        ],
        'default' => [
        ]
    ]

];