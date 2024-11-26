<?php


$wpHome = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/';
define('WP_SITEURL', $wpHome);
define('WP_HOME',    $wpHome);
define('WP_ROOT', __DIR__);
#define('WP_CONTENT_DIR', __DIR__ . '/wp-content');
#define('WP_CONTENT_URL', $wpHome . 'wp-content');
define('SUPERCACHE_JW_MENU_TAG', '643543452438734288238034238472349545');

/**
 * Custom JW Variables
 */
// Deployer
define('DEPLOYER_USER', 'judicialwatch');
define('DEPLOYER_API_KEY', '453e5318ec0a29f3ec52c27207cae995c11ae694');

// Server environment
$serverEnv = 'production';
if (false !== strpos($wpHome, 'judicialwatch.org')
    || false !== strpos($wpHome, 'judicialwatch.wpengine.com')) {
    $serverEnv = 'production';
}
define('WP_SERVER_ENVIRONMENT', $serverEnv);
define('WP_TEST_CARDS', ['370000000000002', '6011000000000012', '3088000000000017', '38000000000006', '4007000000027', '4012888818888', '4111111111111111', '5424000000000015', '2223000010309703', '2223000010309711']);
// Payment processors
$authorizeNetKeys = [
    'transaction_key' => '38bL7YTp7v22ChVE',
    'id' => 'sn3a3V9uV'
];

if ('staging' === $serverEnv) {
    $authorizeNetKeys = [
        'transaction_key' => '9hX87TkV5Yk9L8DL',
        'id' => '68KxHc2eZ9T'
    ];
}
define('AUTHORIZENET_API_KEYS', $authorizeNetKeys);


# Database Configuration
define( 'DB_NAME', 'wp_judicialwatch' );
define( 'DB_USER', 'judicialwatch' );
define( 'DB_PASSWORD', 'cFfiqyV2nHW3hSXSGvVq' );
define( 'DB_HOST', 'cr1e0qtfkabu3gm.cjezfm3slk9b.us-east-2.rds.amazonaws.com' );
define( 'DB_HOST_SLAVE', 'cr1e0qtfkabu3gm.cjezfm3slk9b.us-east-2.rds.amazonaws.com' );
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', 'utf8_unicode_ci');
$table_prefix = 'jw_';

# Security Salts, Keys, Etc
define('AUTH_KEY',         'm}jOglSkvj3[+Jg{h(_-u_?VE2Au7j.z}-Ufjh(@J8~Nn=k6o5d7XgW-1_U0F||P');
define('SECURE_AUTH_KEY',  'CFVnr2]Z/Rfm #}vIVw;&N:x1J(J-w!I7hz+QQG*]%|<n?oc;{z=w_W@9L5_i{.C');
define('LOGGED_IN_KEY',    'Af*lYkGIJL%J6:aT5U]If;bmu=Y~-]#t9<qtDoz+A}ThDTwy~TZSftn85(PN4AJ*');
define('NONCE_KEY',        '0S!-6}?,$*F+2V9/Vp7rO1hR4tYTsZDn|p<a>1tX$dJ)BR-WsJQG2oCg3LL/-a1x');
define('AUTH_SALT',        '7eF;%+G]-iH3}J-csY1b(AkCrmVpLiME|Wy2n`MQ=mlWhM5,hFysZEk@<|-a_6O}');
define('SECURE_AUTH_SALT', 'Kj.7I5FS:5O-RRx~9F91z0$RNMu#6O<my^~kXQ.-H&L+40M:=$s5H3<>tyPu7|oC');
define('LOGGED_IN_SALT',   '(AkG&-bp4>T*s%eRQ)}0Qt+;Ug+(^;FR;Qhl!?w/~^!rI.aR5}Z/w-by]>{B|l3@');
define('NONCE_SALT',       'HWm-pK~~J;>rUQy;%+r;>G_ZMF9<e<r+E^,5qaCA3}=9:H@PM#h4^IF`4#@(B3Tn');


# Localized Language Stuff

define( 'WP_CACHE', TRUE );

define( 'WP_AUTO_UPDATE_CORE', false );

define( 'PWP_NAME', 'judicialwatch' );

define( 'FS_METHOD', 'direct' );

define( 'FS_CHMOD_DIR', 0775 );

define( 'FS_CHMOD_FILE', 0664 );

define( 'PWP_ROOT_DIR', '/nas/wp' );

define( 'WPE_APIKEY', 'daa28c870b62a9e61311356744d9107ccf171ce8' );

define( 'WPE_FOOTER_HTML', "" );

define( 'WPE_CLUSTER_ID', '96077' );

define( 'WPE_CLUSTER_TYPE', 'utility' );

define( 'WPE_ISP', false );

define( 'WPE_BPOD', false );

define( 'WPE_RO_FILESYSTEM', false );

define( 'WPE_LARGEFS_BUCKET', 'largefs.wpengine' );

define( 'WPE_CACHE_TYPE', 'generational' );

define( 'WPE_LBMASTER_IP', '' );

define( 'WPE_CDN_DISABLE_ALLOWED', false );

define( 'DISALLOW_FILE_EDIT', FALSE );

define( 'DISALLOW_FILE_MODS', FALSE );

define( 'DISABLE_WP_CRON', true );

define( 'WPE_FORCE_SSL_LOGIN', true );

define( 'FORCE_SSL_LOGIN', true );

/*SSLSTART*/ if ( isset($_SERVER['HTTP_X_WPE_SSL']) && $_SERVER['HTTP_X_WPE_SSL'] ) $_SERVER['HTTPS'] = 'on'; /*SSLEND*/

define( 'WPE_EXTERNAL_URL', false );

define( 'WP_POST_REVISIONS', FALSE );

define( 'WPE_WHITELABEL', 'wpengine' );

define( 'WP_TURN_OFF_ADMIN_BAR', false );

define( 'WPE_BETA_TESTER', false );

umask(0002);

$wpe_cdn_uris=array ( );

$wpe_no_cdn_uris=array ( );

$wpe_content_regexs=array ( );

$wpe_all_domains=array ( 0 => 'judicialwatch.wpengine.com', 1 => 'judicial.local', 2 => 'judicialwatch.org', );

$wpe_varnish_servers=array ( 0 => 'varnish-96077.wpestorage.net', );

$wpe_special_ips=array ( 0 => '18.219.18.233', 1 => '18.221.170.110', 2 => 'varnish-96077.wpestorage.net', 3 => '10.3.15.213', 4 => '52.15.81.192', );

$wpe_ec_servers=array ( );

$wpe_largefs=array ( );

$wpe_netdna_domains=array ( 0 =>  array ( 'zone' => '1a74073qz3kh1o9xve1eblkl', 'match' => 'judicialwatch.wpengine.com', 'secure' => false, 'dns_check' => '0', ), );

$wpe_netdna_domains_secure=array ( );

$wpe_netdna_push_domains=array ( );

$wpe_domain_mappings=array ( );

$memcached_servers=array ( 'default' =>  array ( 0 => 'localhost:11211', ), );

define( 'WPE_SFTP_PORT', 22 );
define('WPLANG','');

# WP Engine ID


define('PWP_DOMAIN_CONFIG', '' );

# WP Engine Settings






# That's It. Pencils down
if ( !defined('ABSPATH') )
        define('ABSPATH', dirname(__FILE__) . '/');
require_once(ABSPATH . 'wp-settings.php');






