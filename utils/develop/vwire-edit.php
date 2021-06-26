@@ -0,0 +1,567 @@
<?php
/**
 * Created by PhpStorm.
 * User: swaschkut
 * Date: 4/19/16
 * Time: 9:12 AM
 */



set_include_path(dirname(__FILE__) . '/../' . PATH_SEPARATOR . get_include_path());
require_once dirname(__FILE__)."/../../lib/pan_php_framework.php";
require_once dirname(__FILE__)."/../../utils/common/actions.php";

require_once dirname(__FILE__)."/../../utils/lib/UTIL.php";


function display_usage_and_exit($shortMessage = false)
{
    global $argv;
    print PH::boldText("USAGE: ")."php ".basename(__FILE__)." in=inputfile.xml location=vsys1 ".
        "actions=action1:arg1 ['filter=(type is.group) or (name contains datacenter-)']\n";
    print "php ".basename(__FILE__)." help          : more help messages\n";


    if( !$shortMessage )
    {
        print PH::boldText("\nListing available arguments\n\n");

        global $supportedArguments;

        ksort($supportedArguments);
        foreach( $supportedArguments as &$arg )
        {
            print " - ".PH::boldText($arg['niceName']);
            if( isset( $arg['argDesc']))
                print '='.$arg['argDesc'];
            //."=";
            if( isset($arg['shortHelp']))
                print "\n     ".$arg['shortHelp'];
            print "\n\n";
        }

        print "\n\n";
    }

    exit(1);
}

function display_error_usage_exit($msg)
{
    fwrite(STDERR, PH::boldText("\n**ERROR** ").$msg."\n\n");
    display_usage_and_exit(true);
}


$supportedArguments = Array();
$supportedArguments['in'] = Array('niceName' => 'in', 'shortHelp' => 'input file or api. ie: in=config.xml  or in=api://192.168.1.1 or in=api://0018CAEC3@panorama.company.com', 'argDesc' => '[filename]|[api://IP]|[api://serial@IP]');
$supportedArguments['out'] = Array('niceName' => 'out', 'shortHelp' => 'output file to save config after changes. Only required when input is a file. ie: out=save-config.xml', 'argDesc' => '[filename]');
$supportedArguments['location'] = Array('niceName' => 'location', 'shortHelp' => 'specify if you want to limit your query to a VSYS. By default location=vsys1 for PANOS. ie: location=any or location=vsys2,vsys1', 'argDesc' => '=sub1[,sub2]');
$supportedArguments['actions'] = Array('niceName' => 'Actions', 'shortHelp' => 'action to apply on each rule matched by Filter. ie: actions=from-Add:net-Inside,netDMZ', 'argDesc' => 'action:arg1[,arg2]' );
$supportedArguments['debugapi'] = Array('niceName' => 'DebugAPI', 'shortHelp' => 'prints API calls when they happen');
$supportedArguments['help'] = Array('niceName' => 'help', 'shortHelp' => 'this message');
$supportedArguments['template'] = Array('niceName' => 'template', 'shortHelp' => 'Panorama template');
$supportedArguments['filter'] = Array('niceName' => 'Filter', 'shortHelp' => "filters objects based on a query. ie: 'filter=((from has external) or (source has privateNet1) and (to has external))'", 'argDesc' => '(field operator [value])');
$supportedArguments['loadpanoramapushedconfig'] = Array('niceName' => 'loadPanoramaPushedConfig', 'shortHelp' => 'load Panorama pushed config from the firewall to take in account panorama objects and rules' );
$supportedArguments['folder'] = Array('niceName' => 'folder', 'shortHelp' => 'specify the folder where the offline files should be saved');


$usageMsg = PH::boldText('USAGE: ') . "php " . basename(__FILE__) . " in=api:://[MGMT-IP] [cycleconnectedFirewalls] ";

if( !PH::$shadow_json )
{
    print "\n***********************************************\n";
    print "************ VWIRE-EDIT UTILITY  ****************\n\n";
}

$util = new UTIL("custom", $argv, __FILE__, $supportedArguments, $usageMsg);

$util->utilInit();

$util->utilActionFilter( "virtualwire" );



//Todo - how to transfer this part into UTIL CLASS
//Todo: location and template check needed
foreach( $util->objectsLocation as $location )
{

    if( $util->configType == 'panos')
    {

        #if( $location == 'shared' || $location == 'any'  ){
            $util->objectsToProcess[] = Array('store' => $util->pan->network->virtualWireStore, 'objects' => $util->pan->network->virtualWireStore->virtualWires());
            $locationFound = true;
        #}

        /*
        foreach ($pan->getVirtualSystems() as $sub)
        {
            if( ($location == 'any' || $location == 'all' || $location == $sub->name() && !isset($ruleStoresToProcess[$sub->name()]) ))
            {
                $objectsToProcess[] = Array('store' => $sub->importedInterfaces, 'objects' => $sub->importedInterfaces->getAllInterfaces());
                $locationFound = true;
            }
        }
        */
    }
    else
    {
        derr( "This script is not yet working with Panorama config" );



        /*
        if( $location == 'shared' || $location == 'any' )
        {

            $objectsToProcess[] = Array('store' => $pan->tagStore, 'objects' => $pan->tagStore->getall());
            $locationFound = true;
        }

        foreach( $pan->getDeviceGroups() as $sub )
        {
            if( ($location == 'any' || $location == 'all' || $location == $sub->name()) && !isset($ruleStoresToProcess[$sub->name().'%pre']) )
            {
                $objectsToProcess[] = Array('store' => $sub->tagStore, 'objects' => $sub->tagStore->getall() );
                $locationFound = true;
            }
        }
        */

    }

    if( !$locationFound )
    {
        $this->locationNotFound($location);
    }
}
// </editor-fold>

$util->GlobalInitAction($util->sub);



$util->time_to_process_objects();

$util->GlobalFinishAction();



PH::print_stdout( "" );
PH::print_stdout( "**** PROCESSING OF $util->totalObjectsProcessed OBJECTS DONE ****" );
PH::print_stdout( "" );

$util->stats();

##############################################

print "\n\n\n";

$util->save_our_work(TRUE);

if( PH::$shadow_json )
    print json_encode( PH::$JSON_OUT, JSON_PRETTY_PRINT );

##########################################
##########################################
if( !PH::$shadow_json )
{
    print "\n\n\n";

    #$util->save_our_work();

    print "\n\n************ END OF VWIRE-EDIT UTILITY ************\n";
    print     "**************************************************\n";
    print "\n\n";
}
