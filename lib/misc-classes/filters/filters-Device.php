<?php

// <editor-fold desc=" ***** Zone filters *****" defaultstate="collapsed" >

/*
RQuery::$defaultFilters['device']['name']['operators']['eq.nocase'] = array(
    'Function' => function (DeviceRQueryContext $context) {
        return strtolower($context->object->name()) == strtolower($context->value);
    },
    'arg' => TRUE,
    'ci' => array(
        'fString' => '(%PROP% grp.shared-group1)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['device']['name']['operators']['contains'] = array(
    'Function' => function (DeviceRQueryContext $context) {
        return strpos($context->object->name(), $context->value) !== FALSE;
    },
    'arg' => TRUE,
    'ci' => array(
        'fString' => '(%PROP% grp)',
        'input' => 'input/panorama-8.0.xml'
    )
);
*/
RQuery::$defaultFilters['device']['name']['operators']['eq'] = array(
    'Function' => function (DeviceRQueryContext $context) {
        return $context->object->name() == $context->value;
    },
    'arg' => TRUE,
    'ci' => array(
        'fString' => '(%PROP% grp.shared-group1)',
        'input' => 'input/panorama-8.0.xml'
    )
);
RQuery::$defaultFilters['device']['name']['operators']['regex'] = array(
    'Function' => function (DeviceRQueryContext $context) {
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
RQuery::$defaultFilters['device']['name']['operators']['is.in.file'] = array(
    'Function' => function (DeviceRQueryContext $context) {
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
RQuery::$defaultFilters['device']['templatestack']['operators']['has.member'] = array(
    'Function' => function (DeviceRQueryContext $context) {

        $object = $context->object;

        $class = get_class( $object );
        if( $class !== "TemplateStack" )
            return false;

        $used_templates = $context->object->templates;
        foreach( $used_templates as $template )
        {
            if( $template->name() == $context->value )
                return true;
        }
        return false;
    },
    'arg' => TRUE,
    'ci' => array(
        'fString' => '(%PROP% grp)',
        'input' => 'input/panorama-8.0.xml'
    )
);

RQuery::$defaultFilters['device']['manageddevice']['operators']['with-no-dg'] = array(
    'Function' => function (DeviceRQueryContext $context) {
        /** @var ManagedDevice $object */
        $object = $context->object;

        $class = get_class( $object );
        if( $class !== "ManagedDevice" )
            return false;

        $DG = $object->getDeviceGroup();
        if( $DG === "" )
            return TRUE;

        return false;
    },
    'arg' => false,
    'ci' => array(
        'fString' => '(%PROP% grp)',
        'input' => 'input/panorama-8.0.xml'
    )
);

// </editor-fold>