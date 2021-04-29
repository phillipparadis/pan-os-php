<?php

/**
 * © 2019 Palo Alto Networks, Inc.  All rights reserved.
 *
 * Licensed under SCRIPT SOFTWARE AGREEMENT, Palo Alto Networks, Inc., at https://www.paloaltonetworks.com/legal/script-software-license-1-0.pdf
 *
 */



set_include_path(dirname(__FILE__) . '/../' . PATH_SEPARATOR . get_include_path());
require_once dirname(__FILE__)."/../lib/pan_php_framework.php";

require_once dirname(__FILE__)."/../utils/lib/UTIL.php";

if( !PH::$shadow_json )
{
    print "\n***********************************************\n";
    print "************ RULE-EDIT UTILITY ****************\n\n";
}

$util = new RULEUTIL("rule", $argv, __FILE__);

if( !PH::$shadow_json )
{
    print "\n\n************ END OF RULE-EDIT UTILITY ************\n";
    print     "**************************************************\n";
    print "\n\n";
}
