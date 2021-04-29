<?php


class ErrorReporter
{
    static function derr($msg, DOMNode $object = null)
    {
        if( $object !== null )
        {
            $class = get_class($object);
            if( $class == 'DOMNode' || $class == 'DOMElement' || is_subclass_of($object, 'DOMNode') )
            {
                $msg .= "\nXML line #" . $object->getLineNo() . ", XPATH: " . json_encode($object) . "\n" . DH::dom_to_xml($object, 0, TRUE, 3);
                //$msg .="\nXML line #".$object->getLineNo().", XPATH: ".DH::elementToPanXPath($object)."\n".DH::dom_to_xml($object,0,true,3);
            }
        }

        if( PH::$useExceptions )
        {
            $ex = new Exception($msg);
            throw $ex;
        }

        fwrite(STDERR, PH::boldText("\n* ** ERROR ** * ") . $msg . "\n\n");

        //debug_print_backtrace();

        $d = debug_backtrace();

        $skip = 0;

        fwrite(STDERR, " *** Backtrace ***\n");

        $count = 0;

        foreach( $d as $l )
        {
            if( $skip >= 0 )
            {
                fwrite(STDERR, "$count ****\n");
                if( isset($l['object']) && method_exists($l['object'], 'toString') )
                {
                    fwrite(STDERR, '   ' . $l['object']->toString() . "\n");
                }
                $file = '';
                if( isset($l['file']) )
                    $file = $l['file'];
                $line = '';
                if( isset($l['line']) )
                    $line = $l['line'];

                if( isset($l['object']) )
                    fwrite(STDERR, '       ' . PH::boldText($l['class'] . '::' . $l['function'] . "()") . " @\n           {$file} line {$line}\n");
                else
                    fwrite(STDERR, "       " . PH::boldText($l['function']) . "()\n       ::{$file} line {$line}\n");
            }
            $skip++;
            $count++;
        }

        echo "\n";

        exit(1);
    }
}