<?php
/**
 * ISC License
 *
 * Copyright (c) 2014-2018 Christophe Painchaud <shellescape _AT_ gmail.com>
 * Copyright (c) 2019, Palo Alto Networks Inc.
 *
 * Permission to use, copy, modify, and/or distribute this software for any
 * purpose with or without fee is hereby granted, provided that the above
 * copyright notice and this permission notice appear in all copies.
 *
 * THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
 * WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
 * ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
 * WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
 * ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
 * OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
 */



set_include_path(dirname(__FILE__) . '/../' . PATH_SEPARATOR . get_include_path());
require_once dirname(__FILE__)."/../lib/pan_php_framework.php";
require_once dirname(__FILE__)."/../utils/lib/UTIL.php";

PH::processCliArgs();

$supportedUTILTypes = array(
    "stats",
    "address", "service", "tag", "schedule", "application", "threat",
    "rule",
    "device", "securityprofile", "securityprofilegroup",
    "zone",  "interface", "virtualwire", "routing",
    "key-manager",
    "address-merger", "addressgroup-merger",
    "service-merger", "servicegroup-merger",
    "tag-merger",
    "rule-merger",
    "override-finder",
    "diff",
    "upload",
    "xml-issue",
    "appid-enabler",
    "config-size",
    "download-predefined",
    "register-ip-mgr",
    "userid-mgr",
    "xml-op-json",
    "bpa-generator"
    );
//Todo: API not supported scripts:
//custom
/*
 * csv-import

 * util get action filter
*/

//open
/*
 * checkpoint-exclude
 * grp-static-to-dynamic //very old; create action for pa_address-edit
 */

//Todo: more JSON support needed
/*
 * appid-enabler
 * override-finder
 * pan-diff
 * all object merger
 * upload
 * xmlissue
 * pan-config-size
 * bpa-generator
 * panXML_op_JSON
 * register-ip-mgr
 * userid-mgr
 */
$supportedArguments = array();
$usageMsg = PH::boldText('USAGE: ') . "php " . __FILE__ . " in=[filename]|[api://IP]|[api://serial@IP] type=address";

asort($supportedUTILTypes);
$typeUTIL = new UTIL("custom", $argv, __FILE__, $supportedArguments, $usageMsg);
$typeUTIL->supportedArguments['type'] = array('niceName' => 'type', 'shortHelp' => 'specify which type of PAN-OS-PHP UTIL script you like to use', 'argDesc' => implode("|", $supportedUTILTypes ));
$typeUTIL->supportedArguments['version'] = array('niceName' => 'version', 'shortHelp' => 'display actual installed PAN-OS-PHP framework version');


if( isset(PH::$args['version']) )
{
    PH::print_stdout( " - PAN-OS-PHP version: ".PH::frameworkVersion() . " [".PH::frameworkInstalledOS()."]" );
    PH::print_stdout( " - ".dirname(__FILE__) );
    PH::print_stdout( " - PHP version: " . phpversion() );

    PH::$JSON_TMP['version'] = PH::frameworkVersion();
    PH::$JSON_TMP['os'] = PH::frameworkInstalledOS();
    PH::$JSON_TMP['folder'] = dirname(__FILE__);
    PH::$JSON_TMP['php-version'] = phpversion();

    PH::print_stdout( PH::$JSON_TMP, false, 'pan-os-php' );
    PH::$JSON_TMP = array();
    if( PH::$shadow_json )
    {
        PH::$JSON_OUT['log'] = PH::$JSON_OUTlog;
        print json_encode( PH::$JSON_OUT, JSON_PRETTY_PRINT );
    }

    exit();
}
elseif( !isset(PH::$args['type']) )
{
    foreach( $supportedUTILTypes as $type )
        PH::$JSON_TMP[] = $type;
    PH::print_stdout( PH::$JSON_TMP, false, 'type' );

    $typeUTIL->display_error_usage_exit('"type" is missing from arguments');
}
elseif( isset(PH::$args['type']) )
{
    //find type argument
    $type = PH::$args['type'];

    //check if type argument is supported
    if( !in_array( $type, $supportedUTILTypes ) )
        $typeUTIL->display_usage_and_exit();


    //remove type argument from PHP $argv
    $array_key =  array_search("type=".$type, $argv,true)."\n";
    array_splice($argv, intval($array_key), 1);

    //set internal variables to empty array
    PH::$args = array();
    PH::$argv = array();

    PH::print_stdout("");
    PH::print_stdout("***********************************************");
    PH::print_stdout("*********** " . strtoupper( $type ) . " UTILITY **************");
    PH::print_stdout("***********************************************");
    PH::print_stdout("");

    if( $type == "rule" )
        $util = new RULEUTIL($type, $argv, __FILE__." type=".$type);

    elseif( $type == "stats" )
        $util = new STATSUTIL( $type, $argv, __FILE__." type=".$type);

    elseif( $type == "securityprofile" )
        $util = new SECURITYPROFILEUTIL($type, $argv, __FILE__." type=".$type);

    elseif( $type == "zone"
        || $type == "interface"
        || $type == "routing"
        || $type == "virtualwire"
    )
        $util = new NETWORKUTIL($type, $argv, __FILE__." type=".$type);

    elseif( $type == "device" )
        $util = new DEVICEUTIL($type, $argv, __FILE__." type=".$type);

    elseif( $type == "key-manager" )
        $util = new KEYMANGER($type, $argv, __FILE__." type=".$type);

    elseif( $type == "address-merger"
        || $type == "addressgroup-merger"
        || $type == "service-merger"
        || $type == "servicegroup-merger"
        || $type == "tag-merger"
    )
        $util = new MERGER($type, $argv, __FILE__." type=".$type);

    elseif( $type == "rule-merger" )
        $util = new RULEMERGER($type, $argv, __FILE__." type=".$type );

    elseif( $type == "override-finder" )
        $util = new OVERRIDEFINDER($type, $argv, __FILE__." type=".$type);
    elseif( $type == "diff" )
        $util = new DIFF($type, $argv, __FILE__." type=".$type);
    elseif( $type == "upload" )
        $util = new UPLOAD($type, $argv, __FILE__." type=".$type);
    elseif( $type == "xml-issue" )
        $util = new XMLISSUE($type, $argv, __FILE__." type=".$type);

    elseif( $type == "appid-enabler" )
        $util = new APPIDENABLER($type, $argv, __FILE__." type=".$type);
    elseif( $type == "config-size" )
        $util = new CONFIGSIZE($type, $argv, __FILE__." type=".$type);

    elseif( $type == "download-predefined" )
        $util = new PREDEFINED($type, $argv, __FILE__." type=".$type);

    elseif( $type == "register-ip-mgr" )
        $util = new REGISTERIP($type, $argv, __FILE__." type=".$type );

    elseif( $type == "userid-mgr" )
        $util = new USERIDMGR($type, $argv, __FILE__." type=".$type);

    elseif( $type == "xml-op-json" )
        $util = new XMLOPJSON($type, $argv, __FILE__." type=".$type );

    elseif( $type == "bpa-generator" )
        $util = new BPAGENERATOR($type, $argv, __FILE__." type=".$type);

    else
        $util = new UTIL($type, $argv, __FILE__." type=".$type." type=".$type);

    PH::print_stdout("");
    PH::print_stdout("***********************************************");
    PH::print_stdout("************* END OF SCRIPT " . strtoupper( $type ) . " ************" );
    PH::print_stdout("***********************************************");
    PH::print_stdout("");

}
