<?php


// Define the function to include typography.php file and capture its output
function get_typography_styles()
{
    ob_start();
    // Using get_template_directory() to ensure the correct path to typography.php
    include get_template_directory() . '/assets/assets/typography.php';  // Correct path to typography.php
    return ob_get_clean();
}

// Register custom functions for Twig
function add_to_twig($twig)
{
    $twig->addFunction(new \Twig\TwigFunction('site_url', 'site_url'));
    $twig->addFunction(new \Twig\TwigFunction('get_typography_styles', 'get_typography_styles'));
    return $twig;
}

add_filter('timber/twig', 'add_to_twig');

// Add custom variables to Twig (your other existing functions)
function add_custom_variables_to_twig($twig)
{
    // Add variables as needed
    $urls = [
        'tom_fittons_weekly_update' => site_url('/tom-fittons-weekly-update/'),
        'videos' => site_url('/jwtv/'),
        'donate' => site_url('/donate/make-a-contribution-2/'),
        'mission' => site_url('/about/#mission'),
        'lawsuits_and_legal_actions' => site_url('/amicus-briefs/'),
        'corruption_chronicles' => site_url('/corruption-chronicles/'),
        'podcasts' => site_url('/listennow/'),
        'petitions' => site_url('/petitions/'),
        'legal' => site_url('/about/#legal'),
        'open_records_law_resources' => site_url('/open-records-laws-and-resources/'),
        'press_releases' => site_url('/press-releases/'),
        'documents' => site_url('/documents/'),
        'get_text_alerts' => site_url('/petitions/thank-you/'),
        'team' => site_url('/about/#staff'),
        'international_program' => site_url('/public-education-the-international-program/'),
        'investigative_bulletin' => site_url('/investigative-bulletin/'),
        'shop' => 'https://shopjw.org/', // External link, doesn't need site_url()
        'careers' => site_url('/about/#careers'),
        'financial_disclosures' => site_url('/documents/categories/financial-disclosure/'),
        'in_the_news' => site_url('/in-the-news/'),
        'the_verdict' => site_url('/donate/the-verdict/'),
        'contact' => site_url('/contact/'),
        'home' => site_url('/'),
        'read' => site_url('/?taxonomy=category'),
        'watch' => site_url('/jwtv/'),

        'facebook_icon_image_path'  => [ site_url('wp-content/themes/judicial-watch/assets/assets/uploads/2024/04/icon-facebook-0001.png'), site_url('wp-content/themes/judicial-watch/assets/assets/uploads/2024/04/icon-facebook-0001@2x.png')],
        'youtube_icon_image_path'   => [ site_url('wp-content/themes/judicial-watch/assets/assets/uploads/2024/04/icon-youtube-0001.png'),  site_url('wp-content/themes/judicial-watch/assets/assets/uploads/2024/04/icon-youtube-0001@2x.png') ],
        'insta_icon_image_path'     => [ site_url('wp-content/themes/judicial-watch/assets/assets/uploads/2024/04/icon-instagram-0001.png'),site_url('wp-content/themes/judicial-watch/assets/assets/uploads/2024/04/icon-instagram-0001@2x.png')],
        'tsocial_icon_image_path'   => site_url('wp-content/themes/judicial-watch/assets/assets/uploads/2024/04/icon-social-truth.png'),
        'twitter_icon_image_path'   => [ site_url('wp-content/themes/judicial-watch/assets/assets/uploads/2024/04/icon-twitter-0001.png'),  site_url('wp-content/themes/judicial-watch/assets/assets/uploads/2024/04/icon-twitter-0001@2x.png')],

    ];
    foreach ($urls as $key => $url) {
        if (!empty($url)) {
            $twig->addGlobal($key, $url);
        }
    }
    return $twig;
}

add_filter('timber/twig', 'add_custom_variables_to_twig');

// Enable Twig Debugging
function enable_twig_debugging()
{
    $context = \Timber\Timber::get_context();
    if ($context instanceof \Timber\Timber) {
        $context->add_extension(new \Twig\Extension\DebugExtension());
    }
}

add_action('init', 'enable_twig_debugging');




/*
function get_typography_styles() {
    ob_start();
    include 'assets/assets/typography.php';
    return ob_get_clean();
}



function add_to_twig( $twig ) {
    // Add site_url() to Twig context
    $twig->addFunction( new \Twig\TwigFunction('site_url', 'site_url') );
    $twig->addFunction( new \Twig\TwigFunction('get_typography_styles', 'get_typography_styles') );
    return $twig;
}
add_filter( 'twig', 'add_to_twig' );

function add_custom_variables_to_twig($twig) {
    $urls = [
        'tom_fittons_weekly_update' => site_url('/tom-fittons-weekly-update/'),
        'videos' => site_url('/jwtv/'),
        'donate' => site_url('/donate/make-a-contribution-2/'),
        'mission' => site_url('/about/#mission'),
        'lawsuits_and_legal_actions' => site_url('/amicus-briefs/'),
        'corruption_chronicles' => site_url('/corruption-chronicles/'),
        'podcasts' => site_url('/listennow/'),
        'petitions' => site_url('/petitions/'),
        'legal' => site_url('/about/#legal'),
        'open_records_law_resources' => site_url('/open-records-laws-and-resources/'),
        'press_releases' => site_url('/press-releases/'),
        'documents' => site_url('/documents/'),
        'get_text_alerts' => site_url('/petitions/thank-you/'),
        'team' => site_url('/about/#staff'),
        'international_program' => site_url('/public-education-the-international-program/'),
        'investigative_bulletin' => site_url('/investigative-bulletin/'),
        'shop' => 'https://shopjw.org/', // External link, doesn't need site_url()
        'careers' => site_url('/about/#careers'),
        'financial_disclosures' => site_url('/documents/categories/financial-disclosure/'),
        'in_the_news' => site_url('/in-the-news/'),
        'the_verdict' => site_url('/donate/the-verdict/'),
        'contact' => site_url('/contact/'),
        'home' => site_url('/'),
        'read' => site_url('/?taxonomy=category'),
        'watch' => site_url('/jwtv/'),

        'facebook_icon_image_path'  => [ site_url('wp-content/themes/judicial-watch/assets/assets/uploads/2024/04/icon-facebook-0001.png'), site_url('wp-content/themes/judicial-watch/assets/assets/uploads/2024/04/icon-facebook-0001@2x.png')],
        'youtube_icon_image_path'   => [ site_url('wp-content/themes/judicial-watch/assets/assets/uploads/2024/04/icon-youtube-0001.png'),  site_url('wp-content/themes/judicial-watch/assets/assets/uploads/2024/04/icon-youtube-0001@2x.png') ],
        'insta_icon_image_path'     => [ site_url('wp-content/themes/judicial-watch/assets/assets/uploads/2024/04/icon-instagram-0001.png'),site_url('wp-content/themes/judicial-watch/assets/assets/uploads/2024/04/icon-instagram-0001@2x.png')],
        'tsocial_icon_image_path'   => site_url('wp-content/themes/judicial-watch/assets/assets/uploads/2024/04/icon-social-truth.png'),
        'twitter_icon_image_path'   => [ site_url('wp-content/themes/judicial-watch/assets/assets/uploads/2024/04/icon-twitter-0001.png'),  site_url('wp-content/themes/judicial-watch/assets/assets/uploads/2024/04/icon-twitter-0001@2x.png')],

    ];

    foreach ($urls as $key => $url) {
        if (!empty($url)) {
            $twig->addGlobal($key, $url);
        }
    }


    $top_header_text = get_field('top_header_text','option');
    $top_header_link = get_field('top_header_link','option');


    if (!empty($top_header_text)) {
        $twig->addGlobal('top_header_text', $top_header_text);
    }
    if (!empty($top_header_link)) {
        $twig->addGlobal('top_header_link', $top_header_link);
    }
    return $twig;
}
add_filter('timber/twig', 'add_custom_variables_to_twig');

function enable_twig_debugging() {
    // Get the Timber context (an object)
    $context = \Timber\Timber::get_context();


    if ($context instanceof \Timber\Timber) {
        $context->add_extension(new \Twig\Extension\DebugExtension());
    }
}

add_action('init', 'enable_twig_debugging');

*/
