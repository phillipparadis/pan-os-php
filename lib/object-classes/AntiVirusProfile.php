<?php


/**
 * @property $_ip4Map IP4Map cached ip start and end value for fast optimization
 */
class AntiVirusProfile extends SecurityProfile2
{
    use ReferenceableObject;
    use PathableName;
    use XmlConvertible;
    use ObjectWithDescription;

    /** @var string|null */
    protected $value;

    public $_all;

    /** @var SecurityProfileStore|null */
    public $owner;

    public $secprof_type;

    public $ftp = array();
    public $http = array();
    public $http2 = array();
    public $imap = array();
    public $pop3 = array();
    public $smb = array();
    public $smtp = array();

    public $threatException = array();
    public $additional = array();


    public $tmp_virus_prof_array = array('http', 'http2','smtp', 'imap', 'pop3', 'ftp', 'smb');


    /**
     * you should not need this one for normal use
     * @param string $name
     * @param SecurityProfileStore $owner
     * @param bool $fromXmlTemplate
     */
    function __construct($name, $owner, $fromXmlTemplate = FALSE)
    {
        $this->owner = $owner;

        if( $fromXmlTemplate )
        {
            $doc = new DOMDocument();
            $doc->loadXML(self::$templatexml, XML_PARSE_BIG_LINES);

            $node = DH::findFirstElementOrDie('entry', $doc);

            $rootDoc = $this->owner->securityProfileRoot->ownerDocument;
            $this->xmlroot = $rootDoc->importNode($node, TRUE);
            $this->load_from_domxml($this->xmlroot);

            $this->name = $name;
            $this->xmlroot->setAttribute('name', $name);
        }

        $this->name = $name;
    }

    /**
     * @param string $newName
     * @return bool
     */
    public function setName($newName)
    {
        $ret = $this->setRefName($newName);

        if( $this->xmlroot === null )
            return $ret;

        $this->xmlroot->setAttribute('name', $newName);

        return $ret;
    }

    /**
     * @param string $newName
     */
    public function API_setName($newName)
    {
        $c = findConnectorOrDie($this);
        $xpath = $this->getXPath();

        $this->setName($newName);

        if( $c->isAPI() )
            $c->sendRenameRequest($xpath, $newName);
    }

    /**
     * @param DOMElement $xml
     * @return bool TRUE if loaded ok, FALSE if not
     * @ignore
     */
    public function load_from_domxml(DOMElement $xml)
    {
        $this->secprof_type = "virus";
        $this->xmlroot = $xml;

        $this->name = DH::findAttribute('name', $xml);
        if( $this->name === FALSE )
            derr("Virus SecurityProfile name not found\n");

        #print "\nsecprofURL TMP: object named '".$this->name."' found\n";

        #$this->owner->_SecurityProfiles[$this->name] = $this;
        #$this->owner->_all[$this->name] = $this;
        #$this->owner->o[] = $this;


        //predefined URL category
        //$tmp_array[$this->secprof_type][$typeName]['allow']['URL category'] = all predefined URL category


        $tmp_decoder = DH::findFirstElement('decoder', $xml);
        if( $tmp_decoder !== FALSE )
        {
            $tmp_array = array();

            foreach( $tmp_decoder->childNodes as $tmp_entry )
            {
                if( $tmp_entry->nodeType != XML_ELEMENT_NODE )
                    continue;


                $appName = DH::findAttribute('name', $tmp_entry);
                if( $appName === FALSE )
                    derr("Virus SecurityProfile decoder name not found\n");

                $action = DH::findFirstElement('action', $tmp_entry);
                if( $action !== FALSE )
                {
                    $this->$appName['action'] = $action->textContent;
                }
                else
                {
                    $this->$appName['action'] = "----";
                }


                $action_wildfire = DH::findFirstElement('wildfire-action', $tmp_entry);
                if( $action_wildfire !== FALSE )
                {
                    $this->$appName['wildfire-action'] = $action_wildfire->textContent;
                }
                else
                {
                    $this->$appName['wildfire-action'] = "----";
                }

                $action_mlav_action = DH::findFirstElement('mlav-action', $tmp_entry);
                if( $action_mlav_action !== FALSE )
                {
                    $this->$appName['mlav-action'] = $action_mlav_action->textContent;
                }
                else
                {
                    $this->$appName['mlav-action'] = "----";
                }
            }
        }

        $tmp_threat_exception = DH::findFirstElement('threat-exception', $xml);
        if( $tmp_threat_exception !== FALSE )
        {
            $tmp_array[$this->secprof_type][$this->name]['threat-exception'] = array();
            foreach( $tmp_threat_exception->childNodes as $tmp_entry1 )
            {
                if( $tmp_entry1->nodeType != XML_ELEMENT_NODE )
                    continue;

                $tmp_name = DH::findAttribute('name', $tmp_entry1);
                if( $tmp_name === FALSE )
                    derr("VB severity name not found\n");

                $this->threatException[$tmp_name]['name'] = $tmp_name;

                $action = DH::findFirstElement('action', $tmp_entry1);
                if( $action !== FALSE )
                {
                    if( $action->nodeType != XML_ELEMENT_NODE )
                        continue;

                    $tmp_action = DH::firstChildElement($action);
                    $tmp_array[$this->secprof_type][$this->name]['threat-exception'][$tmp_name]['action'] = $tmp_action->nodeName;
                    $this->threatException[$tmp_name]['action'] = $tmp_action->nodeName;
                }
            }
        }

        $tmp_rule = DH::findFirstElement('mlav-engine-filebased-enabled', $xml);
        if( $tmp_rule !== FALSE )
        {
            $this->additional['mlav-engine-filebased-enabled'] = array();
            foreach( $tmp_rule->childNodes as $tmp_entry1 )
            {
                if ($tmp_entry1->nodeType != XML_ELEMENT_NODE)
                    continue;

                $name = DH::findAttribute("name", $tmp_entry1);
                $tmp_inline_policy_action = DH::findFirstElement("mlav-policy-action", $tmp_entry1);
                if( $tmp_inline_policy_action !== FALSE )
                    $this->additional['mlav-engine-filebased-enabled'][$name]['mlav-policy-action'] = $tmp_inline_policy_action->textContent;
            }
        }

        return TRUE;
    }

    public function display()
    {
        PH::print_stdout(  "     * " . get_class($this) . " '" . $this->name() . "'    ");
        PH::$JSON_TMP['sub']['object'][$this->name()]['name'] = $this->name();
        PH::$JSON_TMP['sub']['object'][$this->name()]['type'] = get_class($this);

        //Todo: continue for print out

        foreach( $this->tmp_virus_prof_array as $key => $type )
        {
            PH::print_stdout(  "       o " . PH::boldText($type) );
            //was not set in specific config files
            if( isset( $this->$type['action'] ) )
            {
                PH::print_stdout(  "          - action:          '" . $this->$type['action'] . "'");
                PH::$JSON_TMP['sub']['object'][$this->name()]['decoder'][$type]['action'] = $this->$type['action'];
            }

            if( isset( $this->$type['wildfire-action'] ) )
            {
                PH::print_stdout(  "          - wildfire-action: '" . $this->$type['wildfire-action'] . "'" );
                PH::$JSON_TMP['sub']['object'][$this->name()]['decoder'][$type]['wildfire-action'] = $this->$type['wildfire-action'];
            }

            if( isset( $this->$type['mlav-action'] ) )
            {
                PH::print_stdout(  "          - mlav-action: '" . $this->$type['mlav-action'] . "'" );
                PH::$JSON_TMP['sub']['object'][$this->name()]['decoder'][$type]['mlav-action'] = $this->$type['mlav-action'];
            }
        }

        PH::print_stdout();

        if( !empty( $this->threatException ) )
        {
            PH::print_stdout("        - threat-exception:" );

            foreach( $this->threatException as $threatname => $threat )
            {
                PH::$JSON_TMP['sub']['object'][$this->name()]['threat-exception'][$threatname]['name'] = $threat['name'];

                $string = "             '" . $threat['name'] . "'";
                if( isset( $threat['action'] ) )
                {
                    $string .= "  - action : ".$threat['action'];
                    PH::$JSON_TMP['sub']['object'][$this->name()]['threat-exception'][$threatname]['action'] = $threat['action'];
                }

                PH::print_stdout(  $string );
            }
        }

        if( !empty( $this->additional['mlav-engine-filebased-enabled'] ) )
        {
            if( !empty( $this->additional['mlav-engine-filebased-enabled'] ) )
            {
                PH::print_stdout("        ----------------------------------------");
                PH::print_stdout("        - mlav-engine-filebased-enabled: ");

                foreach ($this->additional['mlav-engine-filebased-enabled'] as $name => $threat)
                    PH::print_stdout("          * " . $name . " - mlav-policy-action :" . $this->additional['mlav-engine-filebased-enabled'][$name]['mlav-policy-action']);
            }
        }
    }

    public function av_action_best_practice()
    {
        $bestpractise = FALSE;

        if( $this->secprof_type != 'virus' )
            return null;

        if( isset($this->tmp_virus_prof_array) )
        {
            foreach( $this->tmp_virus_prof_array as $key => $type )
            {
                if( $type == "ftp" || $type == "http" || $type == "http2" || $type == "smb" )
                {
                    if( $this->$type['action'] == "reset-both" || $this->$type['action'] == "default" )
                        $bestpractise = TRUE;
                    else
                        return False;
                }
                else
                {
                    if( $this->$type['action'] == "reset-both" )
                        $bestpractise = TRUE;
                    else
                        return FALSE;
                }
            }
        }

        return $bestpractise;
    }

    public function av_wildfireaction_best_practice()
    {
        $bestpractise = FALSE;

        if( $this->secprof_type != 'virus' )
            return null;

        if( isset($this->tmp_virus_prof_array) )
        {
            foreach( $this->tmp_virus_prof_array as $key => $type )
            {
                if( $type == "ftp" || $type == "http" || $type == "http2" || $type == "smb" )
                {
                    if( $this->$type['wildfire-action'] == "reset-both" || $this->$type['wildfire-action'] == "default" )
                        $bestpractise = TRUE;
                    else
                        return False;
                }
                else
                {
                    if( $this->$type['wildfire-action'] == "reset-both" )
                        $bestpractise = TRUE;
                    else
                        return False;
                }
            }
        }

        return $bestpractise;
    }

    public function av_mlavaction_best_practice()
    {
        $bestpractise = FALSE;

        if( $this->secprof_type != 'virus' )
            return null;

        if( isset($this->tmp_virus_prof_array) )
        {
            foreach( $this->tmp_virus_prof_array as $key => $type )
            {
                if( isset( $this->$type['mlav-action'] ) )
                {
                    if( $type == "ftp" || $type == "http" || $type == "http2" || $type == "smb" )
                    {
                        if( $this->$type['mlav-action'] == "reset-both" || $this->$type['mlav-action'] == "default" )
                            $bestpractise = TRUE;
                        else
                            return False;
                    }
                    else
                    {
                        if( $this->$type['mlav-action'] == "reset-both" )
                            $bestpractise = TRUE;
                        else
                            return False;
                    }
                }
            }
        }

        return $bestpractise;
    }

    static $templatexml = '<entry name="**temporarynamechangeme**"></entry>';

}

