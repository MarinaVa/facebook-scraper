<?php

set_time_limit(0);
ini_set("memory_limit","1024M");

//require 'class/ExcelParser.php';
require 'class/FBParser.php';
require 'config/main.php';

//$file = $config['parse_list_file'];
//$searchData = ExcelParser::getData($file);

$searchData = [
    [
        'cellphone' => '434097560',
        'email' => 'rosestillthinking@gmail.com',
    ],
    [
        'cellphone' => '478059184',
        'email' => 'onyampila@live.com.au'
    ],
    [
        'cellphone' => '456255528',
        'email' => 'salman_batog@yahoo.com.au'
    ],
];

$fbParser = new FBParser($config);
$fbParser->process($searchData);
