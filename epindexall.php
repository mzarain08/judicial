<?php

//$offset = 22710;

$out = shell_exec('wp elasticpress stats');
$offstr = get_string_between($out, 'Documents:', 'Index');
$offset = (int) trim($offstr);
$offset = $offset + 1088;

while($offset < 89600){
	usleep(100000);
	shell_exec('wp transient delete ep_wpcli_sync');
	$output = shell_exec('wp elasticpress index --per-page="10" --nobulk --offset='.$offset);

//	if(strpos($output, 'Killed')){
		usleep(10000);
		shell_exec('wp transient delete ep_wpcli_sync');
		
		$output1 = shell_exec('wp elasticpress stats');
		$offset_str = get_string_between($output1, 'Documents:', 'Index');
		$offset = (int) trim($offset_str);
		$offset = $offset + 1088;

//	}
	
	print_r($output);
}

function get_string_between($string, $start, $end){
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}
