<?php
/**
 * © 2019 Palo Alto Networks, Inc.  All rights reserved.
 *
 * Licensed under SCRIPT SOFTWARE AGREEMENT, Palo Alto Networks, Inc., at https://www.paloaltonetworks.com/legal/script-software-license-1-0.pdf
 *
 */

echo "\n***********************************************\n";
echo "*********** " . basename(__FILE__) . " UTILITY **************\n\n";

set_include_path(dirname(__FILE__) . '/../' . PATH_SEPARATOR . get_include_path());
require_once dirname(__FILE__)."/../lib/pan_php_framework.php";

require_once dirname(__FILE__)."/../utils/lib/UTIL.php";
require_once("utils/lib/MERGER.php");


$supportedArguments = array();
$supportedArguments[] = array('niceName' => 'in', 'shortHelp' => 'input file or api. ie: in=config.xml  or in=api://192.168.1.1 or in=api://0018CAEC3@panorama.company.com', 'argDesc' => '[filename]|[api://IP]|[api://serial@IP]');
$supportedArguments[] = array('niceName' => 'out', 'shortHelp' => 'output file to save config after changes. Only required when input is a file. ie: out=save-config.xml', 'argDesc' => '[filename]');
$supportedArguments[] = array('niceName' => 'Location', 'shortHelp' => 'specify if you want to limit your query to a VSYS/DG. By default location=shared for Panorama, =vsys1 for PANOS', 'argDesc' => 'vsys1|shared|dg1');
$supportedArguments[] = array('niceName' => 'DupAlgorithm',
    'shortHelp' => "Specifies how to detect duplicates:\n" .
        "  - SameColor: objects with same TAG-color will be replaced by the one picked (default)\n" .
        "  - Identical: objects with same TAG-color and same name will be replaced by the one picked\n" .
        "  - WhereUsed: objects used exactly in the same location will be merged into 1 single object and all ports covered by these objects will be aggregated\n",
    'argDesc' => 'SameColor | Identical | WhereUsed');
$supportedArguments[] = array('niceName' => 'mergeCountLimit', 'shortHelp' => 'stop operations after X objects have been merged', 'argDesc' => '100');
$supportedArguments[] = array('niceName' => 'pickFilter', 'shortHelp' => 'specify a filter a pick which object will be kept while others will be replaced by this one', 'argDesc' => '(name regex /^g/)');
$supportedArguments[] = array('niceName' => 'excludeFilter', 'shortHelp' => 'specify a filter to exclude objects from merging process entirely', 'argDesc' => '(name regex /^g/)');
$supportedArguments[] = array('niceName' => 'allowMergingWithUpperLevel', 'shortHelp' => 'when this argument is specified, it instructs the script to also look for duplicates in upper level');
$supportedArguments[] = array('niceName' => 'help', 'shortHelp' => 'this message');
$supportedArguments[] = array('niceName' => 'exportCSV', 'shortHelp' => 'when this argument is specified, it instructs the script to print out the kept and removed objects per value');
$supportedArguments[] = array('niceName' => 'DebugAPI', 'shortHelp' => 'prints API calls when they happen');
$supportedArguments[] = array('niceName' => 'Git', 'shortHelp' => 'if argument git is used, git repository is created to track changes for input file');

$usageMsg = PH::boldText('USAGE: ') . "php " . basename(__FILE__) . " in=inputfile.xml [out=outputfile.xml] location=shared ['pickFilter=(name regex /^H-/)']\n" .
    "       php " . basename(__FILE__) . " in=api://192.169.50.10 location=shared ['pickFilter=(name regex /^H-/)']";

$PHP_FILE = __FILE__;
$utilType = "tag-merger";


$merger = new MERGER($utilType, $argv, $PHP_FILE, $supportedArguments, $usageMsg);


if( isset(PH::$args['mergecountlimit']) )
    $merger->mergeCountLimit = PH::$args['mergecountlimit'];





if( isset(PH::$args['dupalgorithm']) )
{
    $merger->dupAlg = strtolower(PH::$args['dupalgorithm']);
    if( $merger->dupAlg != 'samecolor' && $merger->dupAlg != 'whereused' && $merger->dupAlg != 'identical' )
        display_error_usage_exit('unsupported value for dupAlgorithm: ' . PH::$args['dupalgorithm']);
}
else
    $merger->dupAlg = 'identical';


$merger->tag_merging();


$merger->save_our_work( true );


if( isset(PH::$args['exportcsv']) )
{
    foreach( $merger->deletedObjects as $obj_index => $object_name )
    {
        if( !isset($object_name['kept']) )
            print_r($object_name);
        print $obj_index . "," . $object_name['kept'] . "," . $object_name['removed'] . "\n";
    }
}


echo "\n************* END OF SCRIPT " . basename(__FILE__) . " ************\n\n";

