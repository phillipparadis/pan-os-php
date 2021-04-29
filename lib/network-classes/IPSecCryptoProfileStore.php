<?php

/**
 * © 2019 Palo Alto Networks, Inc.  All rights reserved.
 *
 * Licensed under SCRIPT SOFTWARE AGREEMENT, Palo Alto Networks, Inc., at https://www.paloaltonetworks.com/legal/script-software-license-1-0.pdf
 *
 */

/**
 * Class IPsecCryptoProfilStore
 * @property $o IPsecCryptoProfil[]
 * @property PANConf $owner
 */
class IPSecCryptoProfileStore extends ObjStore
{
    public static $childn = 'IPSecCryptoProfil';

    /** @var Service[]|ServiceGroup[] */
    protected $_all = array();

    protected $fastMemToIndex = null;
    protected $fastNameToIndex = null;

    public function __construct($name, $owner)
    {
        $this->name = $name;
        $this->owner = $owner;
        $this->classn = &self::$childn;
    }

    /**
     * @return IPSecCryptoProfil[]
     */
    public function ipsecCryptoProfil()
    {
        return $this->o;
    }

    /**
     * Creates a new IPsecCryptoProfil in this store. It will be placed at the end of the list.
     * @param string $name name of the new IPsecCryptoProfil
     * @return IPSecCryptoProfil
     */
    public function newIPsecCryptoProfil($name)
    {
        $CryptoProfile = new IPSecCryptoProfil($name, $this);
        $xmlElement = DH::importXmlStringOrDie($this->owner->xmlroot->ownerDocument, IPSecCryptoProfil::$templatexml);

        $CryptoProfile->load_from_domxml($xmlElement);

        $CryptoProfile->owner = null;
        $CryptoProfile->setName($name);

        $this->addProfil($CryptoProfile);

        $this->_all[$CryptoProfile->name()] = $CryptoProfile;

        return $CryptoProfile;
    }

    /**
     * @param IPSecCryptoProfil $CryptoProfile
     * @return bool
     */
    public function addProfil($CryptoProfile)
    {
        if( !is_object($CryptoProfile) )
            derr('this function only accepts IPsecCryptoProfile class objects');

        if( $CryptoProfile->owner !== null )
            derr('Trying to add a IPsecCryptoProfile that has a owner already !');


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
            derr('You cannot add a CryptoProfile that is already here :)');

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

            $this->xmlroot = DH::findFirstElementOrCreate('ipsec-crypto-profiles', $xml);
        }
    }


    /**
     * @param IPSecCryptoProfil $s
     * @param bool $cleanInMemory
     * @return bool
     */
    public function remove($s, $cleanInMemory = FALSE)
    {
        $class = get_class($s);

        $objectName = $s->name();


        if( !isset($this->_all[$objectName]) )
        {
            mdeb('Tried to remove an object that is not part of this store');
            return FALSE;
        }

        unset($this->_all[$objectName]);

        $s->owner = null;

        $this->xmlroot->removeChild($s->xmlroot);

        if( $cleanInMemory )
            $s->xmlroot = null;

        return TRUE;
    }

    /**
     * @param $IPSecCryptoProfileName string
     * @return null|IPsecTunnel
     */
    public function findIpsecCryptoProfil($IPSecCryptoProfileName)
    {
        return $this->findByName($IPSecCryptoProfileName);
    }
} 