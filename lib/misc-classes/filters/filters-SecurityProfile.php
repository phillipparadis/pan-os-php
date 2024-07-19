<?php

// <editor-fold desc=" ***** SecProf filters *****" defaultstate="collapsed" >
RQuery::$defaultFilters['securityprofile']['refcount']['operators']['>,<,=,!'] = array(
    'eval' => '$object->countReferences() !operator! !value!',
    'arg' => TRUE,
    'ci' => array(
        'fString' => '(%PROP% 1)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['securityprofile']['object']['operators']['is.unused'] = array(
    'Function' => function (SecurityProfileRQueryContext $context) {
        if( get_class($context->object ) == "PredefinedSecurityProfileURL" )
            return null;
        return $context->object->countReferences() == 0;
    },
    'arg' => FALSE,
    'ci' => array(
        'fString' => '(%PROP%)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['securityprofile']['name']['operators']['is.in.file'] = array(
    'Function' => function (SecurityProfileRQueryContext $context) {
        $object = $context->object;

        if( !isset($context->cachedList) )
        {
            $text = file_get_contents($context->value);

            if( $text === FALSE )
                derr("cannot open file '{$context->value}");

            $lines = explode("\n", $text);
            foreach( $lines as $line )
            {
                $line = trim($line);
                if( strlen($line) == 0 )
                    continue;
                $list[$line] = TRUE;
            }

            $context->cachedList = &$list;
        }
        else
            $list = &$context->cachedList;

        return isset($list[$object->name()]);
    },
    'arg' => TRUE
);
RQuery::$defaultFilters['securityprofile']['object']['operators']['is.tmp'] = array(
    'Function' => function (SecurityProfileRQueryContext $context) {
        return $context->object->isTmp();
    },
    'arg' => FALSE,
    'ci' => array(
        'fString' => '(%PROP%)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['securityprofile']['name']['operators']['eq'] = array(
    'Function' => function (SecurityProfileRQueryContext $context) {
        return $context->object->name() == $context->value;
    },
    'arg' => TRUE,
    'ci' => array(
        'fString' => '(%PROP% grp.shared-group1)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['securityprofile']['name']['operators']['eq.nocase'] = array(
    'Function' => function (SecurityProfileRQueryContext $context) {
        return strtolower($context->object->name()) == strtolower($context->value);
    },
    'arg' => TRUE,
    'ci' => array(
        'fString' => '(%PROP% grp.shared-group1)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['securityprofile']['name']['operators']['contains'] = array(
    'Function' => function (SecurityProfileRQueryContext $context) {
        return strpos($context->object->name(), $context->value) !== FALSE;
    },
    'arg' => TRUE,
    'ci' => array(
        'fString' => '(%PROP% grp)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['securityprofile']['name']['operators']['regex'] = array(
    'Function' => function (SecurityProfileRQueryContext $context) {
        $object = $context->object;
        $value = $context->value;

        if( strlen($value) > 0 && $value[0] == '%' )
        {
            $value = substr($value, 1);
            if( !isset($context->nestedQueries[$value]) )
                derr("regular expression filter makes reference to unknown string alias '{$value}'");

            $value = $context->nestedQueries[$value];
        }

        $matching = preg_match($value, $object->name());
        if( $matching === FALSE )
            derr("regular expression error on '{$value}'");
        if( $matching === 1 )
            return TRUE;
        return FALSE;
    },
    'arg' => TRUE,
    'ci' => array(
        'fString' => '(%PROP% /-group/)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['securityprofile']['location']['operators']['is'] = array(
    'Function' => function (SecurityProfileRQueryContext $context) {
        if( is_object($context->object->owner->owner) )
            $owner = $context->object->owner->owner;
        else
            return FALSE;

        if( strtolower($context->value) == 'shared' )
        {
            if( $owner->isPanorama() )
                return TRUE;
            if( $owner->isFirewall() )
                return TRUE;
            return FALSE;
        }
        if( strtolower($context->value) == strtolower($owner->name()) )
            return TRUE;

        return FALSE;
    },
    'arg' => TRUE,
    'ci' => array(
        'fString' => '(%PROP% shared )',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['securityprofile']['location']['operators']['regex'] = array(
    'Function' => function (SecurityProfileRQueryContext $context) {
        $name = $context->object->getLocationString();
        $matching = preg_match($context->value, $name);
        if( $matching === FALSE )
            derr("regular expression error on '{$context->value}'");
        if( $matching === 1 )
            return TRUE;
        return FALSE;
    },
    'arg' => TRUE,
    'ci' => array(
        'fString' => '(%PROP% /shared/)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['securityprofile']['location']['operators']['is.child.of'] = array(
    'Function' => function (SecurityProfileRQueryContext $context) {
        $secprof_location = $context->object->getLocationString();

        $sub = $context->object->owner;
        while( get_class($sub) == "SecurityProfileStore" || get_class($sub) == "DeviceGroup" || get_class($sub) == "VirtualSystem" )
            $sub = $sub->owner;

        if( get_class($sub) == "PANConf" )
            derr("filter location is.child.of is not working against a firewall configuration");

        if( strtolower($context->value) == 'shared' )
            return TRUE;

        $DG = $sub->findDeviceGroup($context->value);
        if( $DG == null )
        {
            PH::print_stdout( "ERROR: location '$context->value' was not found. Here is a list of available ones:" );
            PH::print_stdout( " - shared" );
            foreach( $sub->getDeviceGroups() as $sub1 )
            {
                PH::print_stdout( " - " . $sub1->name() . "" );
            }
            PH::print_stdout();
            exit(1);
        }

        $childDeviceGroups = $DG->childDeviceGroups(TRUE);

        if( strtolower($context->value) == strtolower($secprof_location) )
            return TRUE;

        foreach( $childDeviceGroups as $childDeviceGroup )
        {
            if( $childDeviceGroup->name() == $secprof_location )
                return TRUE;
        }

        return FALSE;
    },
    'arg' => TRUE,
    'help' => 'returns TRUE if object location (shared/device-group/vsys name) matches / is child the one specified in argument',
    'ci' => array(
        'fString' => '(%PROP%  Datacenter-Firewalls)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['securityprofile']['location']['operators']['is.parent.of'] = array(
    'Function' => function (SecurityProfileRQueryContext $context) {
        $secprof_location = $context->object->getLocationString();

        $sub = $context->object->owner;
        while( get_class($sub) == "SecurityProfileStore" || get_class($sub) == "DeviceGroup" || get_class($sub) == "VirtualSystem" )
            $sub = $sub->owner;

        if( get_class($sub) == "PANConf" )
        {
            PH::print_stdout( "ERROR: filter location is.child.of is not working against a firewall configuration");
            return FALSE;
        }

        if( strtolower($context->value) == 'shared' )
            return TRUE;

        $DG = $sub->findDeviceGroup($context->value);
        if( $DG == null )
        {
            PH::print_stdout( "ERROR: location '$context->value' was not found. Here is a list of available ones:" );
            PH::print_stdout( " - shared" );
            foreach( $sub->getDeviceGroups() as $sub1 )
            {
                PH::print_stdout( " - " . $sub1->name() . "" );
            }
            PH::print_stdout();
            exit(1);
        }

        $parentDeviceGroups = $DG->parentDeviceGroups();

        if( strtolower($context->value) == strtolower($secprof_location) )
            return TRUE;

        if( $secprof_location == 'shared' )
            return TRUE;

        foreach( $parentDeviceGroups as $childDeviceGroup )
        {
            if( $childDeviceGroup->name() == $secprof_location )
                return TRUE;
        }

        return FALSE;
    },
    'arg' => TRUE,
    'help' => 'returns TRUE if object location (shared/device-group/vsys name) matches / is parent the one specified in argument',
    'ci' => array(
        'fString' => '(%PROP%  Datacenter-Firewalls)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['securityprofile']['reflocation']['operators']['is'] = array(
    'Function' => function (SecurityProfileRQueryContext $context) {
        $object = $context->object;
        $owner = $context->object->owner->owner;

        $reflocation_array = $object->getReferencesLocation();

        if( strtolower($context->value) == 'shared' )
        {
            if( $owner->isPanorama() )
                return TRUE;
            if( $owner->isFirewall() )
                return TRUE;
            return FALSE;
        }

        if( $owner->isPanorama() )
        {
            $DG = $owner->findDeviceGroup($context->value);
            if( $DG == null )
            {
                $test = new UTIL("custom", array(), 0,"");
                $test->configType = "panorama";
                $test->locationNotFound($context->value, null, $owner);
            }
        }

        foreach( $reflocation_array as $reflocation )
        {
            #if( strtolower($reflocation) == strtolower($owner->name()) )
            if( strtolower($reflocation) == strtolower($context->value) )
                return TRUE;
        }


        return FALSE;
    },
    'arg' => TRUE,
    'ci' => array(
        'fString' => '(%PROP% shared )',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['securityprofile']['reflocation']['operators']['is.only'] = array(
    'Function' => function (SecurityProfileRQueryContext $context) {
        $owner = $context->object->owner->owner;
        $reflocations = $context->object->getReferencesLocation();

        $reftypes = $context->object->getReferencesType();
        $refstore = $context->object->getReferencesStore();

        if( strtolower($context->value) == 'shared' )
        {
            if( $owner->isPanorama() )
                return TRUE;
            if( $owner->isFirewall() )
                return TRUE;
            return null;
        }

        $return = FALSE;
        foreach( $reflocations as $reflocation )
        {
            if( strtolower($reflocation) == strtolower($context->value) )
                $return = TRUE;
        }

        if( count($reflocations) == 1 && $return )
            return TRUE;

        return NULL;

    },
    'arg' => TRUE,
    'ci' => array(
        'fString' => '(%PROP% shared )',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['securityprofile']['refstore']['operators']['is'] = array(
    'Function' => function (SecurityProfileRQueryContext $context) {
        $value = $context->value;
        $value = strtolower($value);

        $context->object->ReferencesStoreValidation($value);

        $refstore = $context->object->getReferencesStore();

        if( array_key_exists($value, $refstore) )
            return TRUE;

        return null;

    },
    'arg' => TRUE,
    'ci' => array(
        'fString' => '(%PROP% rulestore )',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['securityprofile']['reftype']['operators']['is'] = array(
    'Function' => function (SecurityProfileRQueryContext $context) {
        $value = $context->value;
        $value = strtolower($value);

        $context->object->ReferencesTypeValidation($value);

        $reftype = $context->object->getReferencesType();

        if( array_key_exists($value, $reftype) )
            return TRUE;

        return null;

    },
    'arg' => TRUE,
    'ci' => array(
        'fString' => '(%PROP% securityrule )',
        'input' => 'input/panorama-8.0.xml'
    )
);

RQuery::$defaultFilters['securityprofile']['alert']['operators']['has'] = array(
    'Function' => function (SecurityProfileRQueryContext $context) {
        $object = $context->object;
        $value = $context->value;
        $value = strtolower($value);

        if( array_key_exists($value, $object->alert) )
            return TRUE;

        return null;

    },
    'arg' => TRUE,
    'ci' => array(
        'fString' => '(%PROP% securityrule )',
        'input' => 'input/panorama-8.0.xml'
    )
);

RQuery::$defaultFilters['securityprofile']['block']['operators']['has'] = array(
    'Function' => function (SecurityProfileRQueryContext $context) {
        $object = $context->object;
        $value = $context->value;
        $value = strtolower($value);

        if( array_key_exists($value, $object->block) )
            return TRUE;

        return FALSE;

    },
    'arg' => TRUE,
    'ci' => array(
        'fString' => '(%PROP% securityrule )',
        'input' => 'input/panorama-8.0.xml'
    )
);

RQuery::$defaultFilters['securityprofile']['allow']['operators']['has'] = array(
    'Function' => function (SecurityProfileRQueryContext $context) {
        $object = $context->object;
        $value = $context->value;
        $value = strtolower($value);

        if( array_key_exists($value, $object->allow) )
            return TRUE;

        return FALSE;

    },
    'arg' => TRUE,
    'ci' => array(
        'fString' => '(%PROP% securityrule )',
        'input' => 'input/panorama-8.0.xml'
    )
);

RQuery::$defaultFilters['securityprofile']['continue']['operators']['has'] = array(
    'Function' => function (SecurityProfileRQueryContext $context) {
        $object = $context->object;
        $value = $context->value;
        $value = strtolower($value);

        if( array_key_exists($value, $object->continue) )
            return TRUE;

        return FALSE;

    },
    'arg' => TRUE,
    'ci' => array(
        'fString' => '(%PROP% securityrule )',
        'input' => 'input/panorama-8.0.xml'
    )
);

RQuery::$defaultFilters['securityprofile']['override']['operators']['has'] = array(
    'Function' => function (SecurityProfileRQueryContext $context) {
        $object = $context->object;
        $value = $context->value;
        $value = strtolower($value);

        if( array_key_exists($value, $object->override) )
            return TRUE;

        return FALSE;

    },
    'arg' => TRUE,
    'ci' => array(
        'fString' => '(%PROP% securityrule )',
        'input' => 'input/panorama-8.0.xml'
    )
);

RQuery::$defaultFilters['securityprofile']['exception']['operators']['has'] = array(
    'Function' => function (SecurityProfileRQueryContext $context) {
        $object = $context->object;
        $value = $context->value;

        if( $object->secprof_type == 'virus' || $object->secprof_type == 'spyware' || $object->secprof_type == 'vulnerability' )
        {
            if( !empty( $object->threatException ) )
            {
                foreach( $object->threatException as $threatname => $threat )
                {
                    if( $threatname == $value )
                        return TRUE;
                }
            }
        }

        return FALSE;

    },
    'arg' => TRUE,
    'ci' => array(
        'fString' => '(%PROP% securityrule )',
        'input' => 'input/panorama-8.0.xml'
    )
);


RQuery::$defaultFilters['securityprofile']['exception']['operators']['is.set'] = array(
    'Function' => function (SecurityProfileRQueryContext $context) {
        $object = $context->object;

        if( $object->secprof_type == 'virus' || $object->secprof_type == 'spyware' || $object->secprof_type == 'vulnerability' )
        {
            if( !empty( $object->threatException ) )
                return TRUE;
        }

        return FALSE;

    },
    'ci' => array(
        'fString' => '(%PROP% securityrule )',
        'input' => 'input/panorama-8.0.xml'
    )
);

RQuery::$defaultFilters['securityprofile']['action']['operators']['eq'] = array(
    'Function' => function (SecurityProfileRQueryContext $context) {
        /** @var ThreatPolicySpyware|ThreatPolicyVulnerability $object */
        $object = $context->object;
        $value = $context->value;

        #if( $object->secprof_type == 'virus' || $object->secprof_type == 'spyware' || $object->secprof_type == 'vulnerability' )
        if( $object->secprof_type == 'spyware' || $object->secprof_type == 'vulnerability' )
        {
            if( !empty( $object->rules_obj ) )
            {
                foreach( $object->rules_obj as $rulename => $rule )
                {
                    if( $rule->action == $value )
                        return TRUE;
                }
            }
        }

        return FALSE;

    },
    'arg' => TRUE,
    'deprecated' => 'this filter "action eq XYZ" is deprecated, you should use "filter=(threat-rule has.from.query subquery1) subquery1=(action eq XYZ)" instead!',
    'ci' => array(
        'fString' => '(%PROP% reset-both )',
        'input' => 'input/panorama-8.0.xml'
    )
);

RQuery::$defaultFilters['securityprofile']['packet-capture']['operators']['eq'] = array(
    'Function' => function (SecurityProfileRQueryContext $context) {
        $object = $context->object;
        $value = $context->value;

        #if( $object->secprof_type == 'virus' || $object->secprof_type == 'spyware' || $object->secprof_type == 'vulnerability' )
        if( $object->secprof_type == 'spyware' || $object->secprof_type == 'vulnerability' )
        {
            if( !empty( $object->rules_obj ) )
            {
                foreach( $object->rules_obj as $rulename => $rule )
                {
                    if( $rule->packetCapture == $value )
                        return TRUE;
                }
            }
        }
        return FALSE;
    },
    'arg' => TRUE,
    'deprecated' => 'this filter "packet-capture eq XYZ" is deprecated, you should use "filter=(threat-rule has.from.query subquery1) subquery1=(packet-capture eq XYZ)" instead!',
    'ci' => array(
        'fString' => '(%PROP% single-packet )',
        'input' => 'input/panorama-8.0.xml'
    )
);

RQuery::$defaultFilters['securityprofile']['severity']['operators']['eq'] = array(
    'Function' => function (SecurityProfileRQueryContext $context) {
        $object = $context->object;
        $value = $context->value;

        #if( $object->secprof_type == 'virus' || $object->secprof_type == 'spyware' || $object->secprof_type == 'vulnerability' )
        if( $object->secprof_type == 'spyware' || $object->secprof_type == 'vulnerability' )
        {
            if( !empty( $object->rules_obj ) )
            {
                foreach( $object->rules_obj as $rulename => $rule )
                {
                    if( in_array( $value, $rule->severity) )
                        return TRUE;
                }
            }
        }
        return FALSE;
    },
    'arg' => TRUE,
    'deprecated' => 'this filter "severity eq XYZ" is deprecated, you should use "filter=(threat-rule has.from.query subquery1) subquery1=(severity eq XYZ)" instead!',
    'ci' => array(
        'fString' => '(%PROP% critical )',
        'input' => 'input/panorama-8.0.xml'
    )
);

RQuery::$defaultFilters['securityprofile']['category']['operators']['eq'] = array(
    'Function' => function (SecurityProfileRQueryContext $context) {
        $object = $context->object;
        $value = $context->value;

        #if( $object->secprof_type == 'virus' || $object->secprof_type == 'spyware' || $object->secprof_type == 'vulnerability' )
        if( $object->secprof_type == 'spyware' || $object->secprof_type == 'vulnerability' )
        {
            if( !empty( $object->rules_obj ) )
            {
                foreach( $object->rules_obj as $rulename => $rule )
                {
                    if( $rule->category == $value )
                        return TRUE;
                }
            }
        }
        return FALSE;
    },
    'arg' => TRUE,
    'ci' => array(
        'fString' => '(%PROP% brute-force )',
        'deprecated' => 'this filter "category eq XYZ" is deprecated, you should use "filter=(threat-rule has.from.query subquery1) subquery1=(category eq XYZ)" instead!',
        'input' => 'input/panorama-8.0.xml'
    )
);

RQuery::$defaultFilters['securityprofile']['host']['operators']['eq'] = array(
    'Function' => function (SecurityProfileRQueryContext $context) {
        $object = $context->object;
        $value = $context->value;

        #if( $object->secprof_type == 'virus' || $object->secprof_type == 'spyware' || $object->secprof_type == 'vulnerability' )
        if( $object->secprof_type == 'spyware' || $object->secprof_type == 'vulnerability' )
        {
            if( !empty( $object->rules_obj ) )
            {
                foreach( $object->rules_obj as $rulename => $rule )
                {
                    if( $rule->host == $value )
                        return TRUE;
                }
            }
        }
        return FALSE;
    },
    'arg' => TRUE,
    'deprecated' => 'this filter "host eq XYZ" is deprecated, you should use "filter=(threat-rule has.from.query subquery1) subquery1=(host eq XYZ)" instead!',
    'ci' => array(
        'fString' => '(%PROP% client )',
        'input' => 'input/panorama-8.0.xml'
    )
);

RQuery::$defaultFilters['securityprofile']['excempt-ip.count']['operators']['>,<,=,!'] = array(
    'Function' => function (SecurityProfileRQueryContext $context) {
        /** @var VulnerabilityProfile $object */
        $object = $context->object;
        $value = $context->value;
        $operator = $context->operator;

        if( $operator == '=' )
            $operator = '==';

        foreach( $object->threatException as $exception )
        {
            if( isset($exception['exempt-ip']) )
            {
                $operator_string = count($exception['exempt-ip'])." ".$operator." ".$value;
                if( eval("return $operator_string;" ) )
                    return true;
                else
                    return false;
            }
        }
    },
    'arg' => TRUE,
    'ci' => array(
        'fString' => '(%PROP% rulestore )',
        'input' => 'input/panorama-8.0.xml'
    )
);

RQuery::$defaultFilters['securityprofile']['cloud-inline-analysis']['operators']['is.enabled'] = array(
    'Function' => function (SecurityProfileRQueryContext $context) {
        /** @var VulnerabilityProfile|AntiSpywareProfile $object */
        $object = $context->object;

        if( $object->secprof_type == 'spyware' || $object->secprof_type == 'vulnerability' )
        {
            if( $object->cloud_inline_analysis_enabled )
                return TRUE;
        }
        return FALSE;
    },
    'arg' => false,
    'ci' => array(
        'fString' => '(%PROP% client )',
        'input' => 'input/panorama-8.0.xml'
    )
);

RQuery::$defaultFilters['securityprofile']['threat-rule']['operators']['has.from.query'] = array(
    'Function' => function (SecurityProfileRQueryContext $context) {
        if( $context->object->secprof_type !== 'spyware' && $context->object->secprof_type !== 'vulnerability' )
            return FALSE;

        if( count($context->object->rules_obj) == 0 )
            return FALSE;

        if( $context->value === null || !isset($context->nestedQueries[$context->value]) )
            derr("cannot find nested query called '{$context->value}'");

        $errorMessage = '';

        if( !isset($context->cachedSubRQuery) )
        {
            $rQuery = new RQuery('threat-rule');
            if( $rQuery->parseFromString($context->nestedQueries[$context->value], $errorMessage) === FALSE )
                derr('nested query execution error : ' . $errorMessage);
            $context->cachedSubRQuery = $rQuery;
        }
        else
            $rQuery = $context->cachedSubRQuery;

        foreach( $context->object->rules_obj as $member )
        {
            if( $rQuery->matchSingleObject(array('object' => $member, 'nestedQueries' => &$context->nestedQueries)) )
                return TRUE;
        }

        return FALSE;
    },
    'arg' => TRUE,
    'help' => 'example: \'filter=(threat-rule has.from.query subquery1)\' \'subquery1=(action eq alert)\'',
);
// </editor-fold>