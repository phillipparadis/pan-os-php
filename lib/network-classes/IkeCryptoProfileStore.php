<?php

/**
 * © 2019 Palo Alto Networks, Inc.  All rights reserved.
 *
 * Licensed under SCRIPT SOFTWARE AGREEMENT, Palo Alto Networks, Inc., at https://www.paloaltonetworks.com/legal/script-software-license-1-0.pdf
 *
 */

/**
 * Class IkeCryptoProfilStore
 * @property $o IkeCryptoProfil[]
 * @property PANConf $owner
 */
class IkeCryptoProfileStore extends ObjStore
{
    public static $childn = 'IkeCryptoProfil';

    protected $fastMemToIndex = null;
    protected $fastNameToIndex = null;

    public function __construct($name, $owner)
    {
        $this->name = $name;
        $this->owner = $owner;
        $this->classn = &self::$childn;
    }

    /**
     * @return IkeCryptoProfil[]
     */
    public function ikeCryptoProfil()
    {
        return $this->o;
    }


    /**
     * Creates a new IkeCryptoProfil in this store. It will be placed at the end of the list.
     * @param string $name name of the new IkeCryptoProfil
     * @return IkeCryptoProfil
     */
    public function newIkeCryptoProfil($name)
    {
        $CryptoProfile = new IkeCryptoProfil($name, $this);
        $xmlElement = DH::importXmlStringOrDie($this->owner->xmlroot->ownerDocument, IkeCryptoProfil::$templatexml);

        $CryptoProfile->load_from_domxml($xmlElement);

        $CryptoProfile->owner = null;
        $CryptoProfile->setName($name);

        $this->addProfil($CryptoProfile);

        return $CryptoProfile;
    }


    /**
     * @param IkeCryptoProfil $CryptoProfile
     * @return bool
     */
    public function addProfil($CryptoProfile)
    {
        if( !is_object($CryptoProfile) )
            derr('this function only accepts IKEGateway class objects');

        if( $CryptoProfile->owner !== null )
            derr('Trying to add a gateway that has a owner already !');


        $ser = spl_object_hash($CryptoProfile);

        if( !isset($this->fastMemToIndex[$ser]) )
        {
            $CryptoProfile->owner = $this;

            if( $this->xmlroot === null )
                $this->createXmlRoot();

            $this->xmlroot->appendChild($CryptoProfile->xmlroot);
            $ret = $this->add($CryptoProfile);
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
            $xml = DH::findFirstElementOrCreate('crypto-profiles', $xml);

            $this->xmlroot = DH::findFirstElementOrCreate('ike-crypto-profiles', $xml);
        }
    }

    /**
     * @param $IKeCryptoProfileName string
     * @return null|IKECryptoProfil
     */
    public function findIKECryptoProfil($IKECryptoProfileName)
    {
        return $this->findByName($IKECryptoProfileName);
    }

}
