<?php

/**
 * © 2019 Palo Alto Networks, Inc.  All rights reserved.
 *
 * Licensed under SCRIPT SOFTWARE AGREEMENT, Palo Alto Networks, Inc., at https://www.paloaltonetworks.com/legal/script-software-license-1-0.pdf
 *
 */

/**
 * Class IKEGatewayStore
 * @property $o IKEGateway[]
 * @property PANConf $owner
 */
class IKEGatewayStore extends ObjStore
{
    public static $childn = 'IKEGateway';

    protected $fastMemToIndex = null;
    protected $fastNameToIndex = null;

    public function __construct($name, $owner)
    {
        $this->name = $name;
        $this->owner = $owner;
        $this->classn = &self::$childn;
    }

    /**
     * @return IKEGateway[]
     */
    public function gateways()
    {
        return $this->o;
    }

    /**
     * Creates a new IKEGateway in this store. It will be placed at the end of the list.
     * @param string $name name of the new IKEGateway
     * @return IKEGateway
     */
    public function newIKEGateway($name, $ikev2 = FALSE)
    {
        $gateway = new IKEGateway($name, $this);
        if( $ikev2 )
            $xmlElement = DH::importXmlStringOrDie($this->owner->xmlroot->ownerDocument, IKEGateway::$templatexml_ikev2);
        else
            $xmlElement = DH::importXmlStringOrDie($this->owner->xmlroot->ownerDocument, IKEGateway::$templatexml);

        $gateway->load_from_domxml($xmlElement);

        $gateway->owner = null;
        $gateway->setName($name);

        $this->addGateway($gateway);

        return $gateway;
    }


    /**
     * @param IKEGateway $gateway
     * @return bool
     */
    public function addGateway($gateway)
    {
        if( !is_object($gateway) )
            derr('this function only accepts IKEGateway class objects');

        if( $gateway->owner !== null )
            derr('Trying to add a gateway that has a owner already !');


        $ser = spl_object_hash($gateway);

        if( !isset($this->fastMemToIndex[$ser]) )
        {
            $gateway->owner = $this;

            if( $this->xmlroot === null )
                $this->createXmlRoot();

            $this->xmlroot->appendChild($gateway->xmlroot);

            $ret = $this->add($gateway);

            return TRUE;
        }
        else
            derr('You cannot add a Gateway that is already here :)');

        return FALSE;
    }

    public function createXmlRoot()
    {
        if( $this->xmlroot === null )
        {
            //TODO: 20180331 why I need to create full path? why it is not set before???
            $xml = DH::findFirstElementOrCreate('devices', $this->owner->xmlroot);
            $xml = DH::findFirstElementOrCreate('entry', $xml);
            $xml = DH::findFirstElementOrCreate('network', $xml);
            $xml = DH::findFirstElementOrCreate('ike', $xml);

            $this->xmlroot = DH::findFirstElementOrCreate('gateway', $xml);
        }
    }

    /**
     * @param $IKEName string
     * @return null|IKEGateway
     */
    public function findIKEGateway($IKEName)
    {
        return $this->findByName($IKEName);
    }

}