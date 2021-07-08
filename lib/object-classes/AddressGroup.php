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

class AddressGroup
{

    //Todo: dynamic addressgroup make objects used

    use PathableName;
    use XmlConvertible;
    use AddressCommon;
    use ObjectWithDescription;

    private $isDynamic = FALSE;

    private $_hasFQDN = FALSE;

    /** @var AddressStore|null */
    public $owner = null;

    /** @var Address[]|AddressGroup[] $members */
    private $members = array();

    /** @var DOMElement */
    private $membersRoot = null;

    /** @var TagRuleContainer */
    public $tags;

    public $filter;


    /**
     * Constructor for AddressGroup. There is little chance that you will ever need that. Look at AddressStore if you want to create an AddressGroup
     * @param string $name
     * @param AddressStore|null $owner
     * @param bool $fromTemplateXml
     *
     */
    function __construct($name, $owner, $fromTemplateXml = FALSE)
    {
        $this->owner = $owner;

        if( $fromTemplateXml )
        {
            $doc = new DOMDocument();
            if( $this->owner->owner->version < 60 )
                $doc->loadXML(self::$templatexml, XML_PARSE_BIG_LINES);
            else
                $doc->loadXML(self::$templatexml_v6, XML_PARSE_BIG_LINES);

            $node = DH::findFirstElement('entry', $doc);

            $rootDoc = $this->owner->addressGroupRoot->ownerDocument;

            $this->xmlroot = $rootDoc->importNode($node, TRUE);
            $this->load_from_domxml($this->xmlroot);

            $this->name = $name;
            $this->xmlroot->setAttribute('name', $name);
        }

        $this->name = $name;

        $this->tags = new TagRuleContainer($this);
    }

    public function isDynamic()
    {
        return $this->isDynamic;
    }


    public function xml_convert_to_v6()
    {
        $newElement = $this->xmlroot->ownerDocument->createElement('static');
        $nodes = array();

        foreach( $this->xmlroot->childNodes as $node )
        {
            if( $node->nodeType != 1 )
                continue;

            $nodes[] = $node;
        }


        foreach( $nodes as $node )
        {
            $newElement->appendChild($node);
        }


        $this->xmlroot->appendChild($newElement);
    }

    /**
     * @ignore
     *
     */
    public function load_from_domxml($xml)
    {

        $this->xmlroot = $xml;

        $this->name = DH::findAttribute('name', $xml);
        if( $this->name === FALSE )
            derr("name not found\n");

        if( $this->owner->owner->version >= 60 )
        {
            $tagRoot = DH::findFirstElement('tag', $this->xmlroot);

            if( $tagRoot !== FALSE )
                $this->tags->load_from_domxml($tagRoot);


            $this->membersRoot = DH::findFirstElement('static', $xml);

            if( $this->membersRoot === FALSE )
            {
                $tmp = DH::findFirstElement('dynamic', $xml);
                if( $tmp === FALSE )
                {
                    $tmp2 = DH::firstChildElement($xml);
                    if( $tmp2 === FALSE )
                        mwarning('empty AddressGroup : ', $xml);
                    else
                        mwarning('unsupported AddressGroup type: ', $xml);
                }
                else
                {
                    $this->isDynamic = TRUE;
                    $tmp_filter = DH::findFirstElement('filter', $tmp);
                    $this->filter = $tmp_filter->nodeValue;


                    $patterns = array( "@'(.*?)'@", "@\"(.*?)\"@");
                    $tagFilter = $this->filter;
                    foreach( $patterns as $pattern)
                    {
                        $names = array();

                        $is_match = preg_match_all($pattern, $tagFilter, $names);
                        foreach( $names[1] as $key => $replaceTXT )
                        {
                            $pattern = $names[0][$key];
                            $replacements = "(tag has ".$replaceTXT.")";

                            $tagFilter = str_replace( $pattern, $replacements, $tagFilter );

                            $tag = $this->owner->owner->tagStore->find($replaceTXT);
                            if( $tag !== null )
                                $tag->addReference($this);
                            else
                            {
                                #Todo: what if TAG is in parent tagStore?
                                #stop throwing WARNING - as it could be that DAG filter is not based on TAG, e.g. VMware info
                                #mwarning( "TAG not found: ".$test." - for DAG: '".$this->name()."' in location: ".$this->owner->owner->name(), null, false );
                            }
                        }
                    }

                    $tmp_found_addresses = $this->owner->all($tagFilter);
                    foreach( $tmp_found_addresses as $address )
                    {
                        #print "object: ".$address->name()." add ref to ".$this->name()."\n";
                        $address->addReference($this);
                    }
                }
            }
            else
            {
                $membersIndex = array();
                foreach( $this->membersRoot->childNodes as $node )
                {
                    if( $node->nodeType != 1 ) continue;

                    $memberName = $node->textContent;

                    if( strlen($memberName) < 1 )
                        derr('found a member with empty name !', $node);

                    if( isset($membersIndex[$memberName]) )
                    {
                        mwarning("duplicated member named '{$memberName}' detected in address group '{$this->name}',  you should review your XML config file", $this->xmlroot);
                        continue;
                    }
                    $membersIndex[$memberName] = TRUE;

                    $f = $this->owner->findOrCreate($memberName, $this, TRUE);
                    $this->members[] = $f;

                    if( $f->isAddress() && $f->isType_FQDN() )
                        $this->_hasFQDN = true;
                }
            }
        }
        else
        {
            foreach( $xml->childNodes as $node )
            {
                if( $node->nodeType != 1 ) continue;

                $memberName = $node->textContent;

                if( strlen($memberName) < 1 )
                    derr('found a member with empty name !', $node);

                $f = $this->owner->findOrCreate($memberName, $this, TRUE);
                $this->members[] = $f;

                if( $f->isAddress() && $f->isType_FQDN() )
                    $this->_hasFQDN = true;
            }
        }

        $this->_load_description_from_domxml();
    }

    /**
     * @param string $newName
     */
    public function setName($newName)
    {
        $this->setRefName($newName);
        $this->xmlroot->setAttribute('name', $newName);
    }


    /**
     * @param Address|AddressGroup $h
     * ** This is for internal use only **
     *
     * @ignore
     */
    public function referencedObjectRenamed($h)
    {

        if( in_array($h, $this->members, TRUE) )
        {
            $this->rewriteXML();
        }
    }

    /**
     * Add a member to this group, it must be passed as an object
     * @param Address|AddressGroup $newObject Object to be added
     * @param bool $rewriteXml
     * @return bool
     */
    public function addMember($newObject, $rewriteXml = TRUE)
    {
        if( $this->isDynamic )
            derr('cannot be used on Dynamic Address Groups');

        if( !is_object($newObject) )
            derr("Only objects can be passed to this function");

        if( $this->name() == $newObject->name() )
        {
            mwarning("AddressGroup can not be added to itself!");
            return FALSE;
        }


        if( !in_array($newObject, $this->members, TRUE) )
        {
            $this->members[] = $newObject;
            $newObject->addReference($this);
            if( $rewriteXml && $this->owner !== null )
            {
                if( $this->owner->owner->version >= 60 )
                    DH::createElement($this->membersRoot, 'member', $newObject->name());
                else
                    DH::createElement($this->xmlroot, 'member', $newObject->name());
            }

            return TRUE;
        }

        return FALSE;
    }

    /**
     * Add a member to this group, it must be passed as an object
     * @param Address|AddressGroup $newObject Object to be added
     * @return bool
     */
    public function API_addMember($newObject)
    {
        $ret = $this->addMember($newObject);

        if( $ret )
        {
            $con = findConnector($this);
            $xpath = $this->getXPath();

            if( $this->owner->owner->version >= 60 )
                $xpath .= '/static';

            $con->sendSetRequest($xpath, "<member>{$newObject->name()}</member>");
        }

        return $ret;
    }

    /**
     * Removes a member from this group
     * @param Address|AddressGroup $objectToRemove Object to be removed
     * @param bool $rewriteXml
     * @return bool
     */
    public function removeMember($objectToRemove, $rewriteXml = TRUE)
    {
        if( $this->isDynamic )
            derr('cannot be used on Dynamic Address Groups');

        if( !is_object($objectToRemove) )
            derr("Only objects can be passed to this function");

        $pos = array_search($objectToRemove, $this->members, TRUE);

        if( $pos === FALSE )
            return FALSE;
        else
        {
            unset($this->members[$pos]);
            $objectToRemove->removeReference($this);
            if( $rewriteXml )
                $this->rewriteXML();
        }
        return TRUE;
    }

    /**
     * Removes a member from this group
     * @param Address|AddressGroup $objectToRemove Object to be removed
     * @return bool
     */
    public function API_removeMember($objectToRemove)
    {
        $ret = $this->removeMember($objectToRemove);

        if( $ret )
        {
            $con = findConnector($this);
            $xpath = $this->getXPath();

            if( $this->owner->owner->version >= 60 )
                $xpath .= '/static';

            $con->sendDeleteRequest($xpath . "/member[text()='{$objectToRemove->name()}']");

            return $ret;
        }

        return $ret;
    }

    /**
     * tag a member with its group membership name
     * @param bool $rewriteXml
     * @return bool
     */
    public function tagMember($newTag, $rewriteXml = TRUE)
    {
        foreach( $this->members() as $member )
        {
            if( $member->isGroup() )
                $member->tagMember($newTag);
            else
                $member->tags->addTag($newTag);
        }

        if( $rewriteXml )
            $this->rewriteXML();

        return TRUE;
    }

    /**
     * tag a member with its group membership name
     * @param bool $rewriteXml
     * @return bool
     */
    public function API_tagMember($newTag, $rewriteXml = TRUE)
    {
        $ret = $this->tagMember($newTag, $rewriteXml);

        if( $ret )
            $this->API_sync();

        return $ret;
    }

    /**
     * tag a member with its group membership name
     * @param bool $rewriteXml
     * @return bool
     */
    public function tagRuleandGroupMember($rule, $prefix, $rewriteXml = TRUE)
    {
        $tagStore = $rule->owner->owner->tagStore;

        $newTag = $tagStore->findOrCreate($prefix . $this->name());

        $rule->tags->addTag($newTag);

        foreach( $this->members() as $member )
        {
            if( $member->isGroup() )
                $member->tagRuleandGroupMember($rule, $prefix);
            elseif( !$member->isTmpAddr() )
            {
                $tagStore = $rule->owner->owner->tagStore;

                $newTagName = $prefix . $this->name();
                $newTag = $tagStore->findOrCreate($newTagName);

                $member->tags->addTag($newTag);
            }
        }

        if( $rewriteXml )
            $this->rewriteXML();

        return TRUE;
    }

    /**
     * tag a member with its group membership name
     * @param bool $rewriteXml
     * @return bool
     */
    public function API_tagRuleandGroupMember($rule, $prefix, $rewriteXml = TRUE)
    {
        $ret = $this->tagRuleandGroupMember($rule, $prefix, $rewriteXml);

        if( $ret )
            $this->API_sync();

        return $ret;
    }

    /**
     * Clear this Group from all its members
     *
     *
     */
    public function removeAll($rewriteXml = TRUE)
    {

        if( $this->isDynamic )
            derr('cannot be called on Dynamic Address Group');

        foreach( $this->members as $a )
        {
            $a->removeReference($this);
        }
        $this->members = array();


        if( $rewriteXml )
        {
            $this->rewriteXML();
        }

    }

    /**
     * @param Address|AddressGroup $old
     * @param Address|AddressGroup|null $new
     * @return bool
     */
    public function replaceReferencedObject($old, $new)
    {
        if( $old === null )
            derr("\$old cannot be null");

        $pos = array_search($old, $this->members, TRUE);


        if( $pos !== FALSE )
        {
            while( $pos !== FALSE )
            {
                unset($this->members[$pos]);
                $pos = array_search($old, $this->members, TRUE);
            }

            if( $new !== null && !$this->has($new) )
            {
                $this->members[] = $new;
                $new->addReference($this);
            }
            $old->removeReference($this);

            if( $new === null || $new->name() != $old->name() )
                $this->rewriteXML();

            return TRUE;
        }
        elseif( !$this->isDynamic() )
                mwarning("object is not part of this group: " . $old->toString());



        return FALSE;
    }

    public function API_replaceReferencedObject($old, $new)
    {
        $ret = $this->replaceReferencedObject($old, $new);

        if( $ret )
        {
            $this->API_sync();
        }

        return $ret;
    }

    /**
     * @param $obj Address|AddressGroup
     * @return bool
     */
    public function has($obj)
    {
        return array_search($obj, $this->members, TRUE) !== FALSE;
    }

    /**
     * Rewrite XML for this object, useful after a batch editing to save computing time
     *
     */
    public function rewriteXML()
    {
        if( $this->isDynamic() )
            derr('unsupported');

        if( $this->owner->owner->version >= 60 )
            DH::Hosts_to_xmlDom($this->membersRoot, $this->members, 'member', FALSE);
        else
            DH::Hosts_to_xmlDom($this->xmlroot, $this->members, 'member', FALSE);

    }

    /**
     * Counts how many members in this group
     * @return int
     *
     */
    public function count()
    {
        if( $this->isDynamic )
            mwarning('unsupported with Dynamic Address Groups');
        return count($this->members);
    }

    /**
     * returns the member list as an Array of objects (mix of Address, AddressGroups, Regions..)
     * @return Address[]|AddressGroup[]
     */
    public function members()
    {
        return $this->members;
    }

    /**
     * @param string $newName
     */
    public function API_setName($newName)
    {
        $c = findConnectorOrDie($this);
        $xpath = $this->getXPath();
        $c->sendRenameRequest($xpath, $newName);
        $this->setName($newName);
    }

    /**
     * @return string
     */
    public function &getXPath()
    {
        $str = $this->owner->getAddressGroupStoreXPath() . "/entry[@name='" . $this->name . "']";

        return $str;
    }

    public function isGroup()
    {
        return TRUE;
    }

    public function hasFQDN()
    {
        return $this->_hasFQDN;
    }

    /**
     * @param Address|AddressGroup $otherObject
     * @return bool
     */
    public function equals($otherObject)
    {
        if( !$otherObject->isGroup() )
            return FALSE;

        if( $otherObject->name != $this->name )
            return FALSE;


        return $this->sameValue($otherObject);
    }

    /**
     * Return true if other object is also a group and has same object name
     *  ( value in not taken in account, only object name !! )
     * @param AddressGroup $otherObject
     * @return bool
     */
    public function sameValue(AddressGroup $otherObject)
    {
        if( $this->isTmpAddr() && !$otherObject->isTmpAddr() )
            return FALSE;

        if( $otherObject->isTmpAddr() && !$this->isTmpAddr() )
            return FALSE;

        if( $otherObject->count() != $this->count() )
            return FALSE;

        $diff = $this->getValueDiff($otherObject);

        if( count($diff['plus']) + count($diff['minus']) != 0 )
            return FALSE;

        return TRUE;
    }


    public function &getValueDiff(AddressGroup $otherObject)
    {
        $result = array('minus' => array(), 'plus' => array());

        $localObjects = $this->members;
        $otherObjects = $otherObject->members;

        usort($localObjects, '__CmpObjName');
        usort($otherObjects, '__CmpObjName');

        $diff = array_udiff($otherObjects, $localObjects, '__CmpObjName');
        if( count($diff) != 0 )
            foreach( $diff as $d )
            {
                $result['minus'][] = $d;
            }

        $diff = array_udiff($localObjects, $otherObjects, '__CmpObjName');
        if( count($diff) != 0 )
            foreach( $diff as $d )
            {
                $result['plus'][] = $d;
            }

        return $result;
    }


    public function displayValueDiff(AddressGroup $otherObject, $indent = 0, $toString = FALSE)
    {
        $retString = '';

        $indent = str_pad(' ', $indent);


        $retString .= $indent . "Diff for between " . $this->toString() . " vs " . $otherObject->toString() . "\n";

        $diff = $this->getValueDiff($otherObject);

        if( count($diff['minus']) != 0 )
            foreach( $diff['minus'] as $d )
            {
                /** @var Address|AddressGroup $d */
                $retString .= $indent . " - {$d->name()}\n";
            }

        if( count($diff['plus']) != 0 )
            foreach( $diff['plus'] as $d )
            {
                $retString .= $indent . " + {$d->name()}\n";
            }

        if( $toString )
            return $retString;

        print $retString;
    }

    /**
     * @param bool $keepGroupsInList keep groups in the the list on top of just expanding them
     * @return Address[]|AddressGroup[] list of all member objects, if some of them are groups, they are exploded and their members inserted
     */
    public function & expand($keepGroupsInList = FALSE)
    {
        $ret = array();

        foreach( $this->members as $object )
        {
            $serial = spl_object_hash($object);
            if( $object->isGroup() )
            {
                if( $this->name() == $object->name() )
                {
                    mwarning("addressgroup with name: " . $this->name() . " is added as subgroup to itself, you should review your XML config file");
                    continue;
                }

                /** @var AddressGroup $object */
                $tmpList = $object->expand();
                $ret = array_merge($ret, $tmpList);
                unset($tmpList);
                if( $keepGroupsInList )
                    $ret[$serial] = $object;

            }
            else
                $ret[$serial] = $object;
        }

        return $ret;
    }

    /**
     * @param Address|AddressGroup $object
     * @return bool
     */
    public function hasObjectRecursive($object)
    {
        if( $object === null )
            derr('cannot work with null objects');

        if( count( $this->members() ) == 0 )
            return false;

        foreach( $this->members as $o )
        {
            if( $o === $object )
                return TRUE;
            if( $o->isGroup() )
                if( $o->hasObjectRecursive($object) ) return TRUE;
        }

        return FALSE;
    }

    public function API_delete()
    {
        return $this->owner->API_remove($this);
    }


    /**
     * return 0 if not match, 1 if this object is fully included in $network, 2 if this object is partially matched by $ref.
     * @param string|IP4Map $network ie: 192.168.0.2/24, 192.168.0.2,192.168.0.2-192.168.0.4
     * @return int
     */
    public function includedInIP4Network($network)
    {
        if( is_object($network) )
            $netStartEnd = $network;
        else
            $netStartEnd = IP4Map::mapFromText($network);

        if( count($this->members) == 0 )
            return 1;

        $result = -1;

        foreach( $this->members as $o )
        {
            $localResult = $o->includedInIP4Network($netStartEnd);
            if( $localResult == 1 )
            {
                if( $result == 2 )
                    continue;
                if( $result == -1 )
                    $result = 1;
                else if( $result == 0 )
                    return 2;
            }
            elseif( $localResult == 2 )
            {
                return 2;
            }
            elseif( $localResult == 0 )
            {
                if( $result == -1 )
                    $result = 0;
                else if( $result == 1 )
                    return 2;
            }
        }

        return $result;
    }


    /**
     * return 0 if not match, 1 if $network is fully included in this object, 2 if $network is partially matched by this object.
     * @param $network string|IP4Map ie: 192.168.0.2/24, 192.168.0.2,192.168.0.2-192.168.0.4
     * @return int
     */
    public function includesIP4Network($network)
    {
        if( is_object($network) )
            $netStartEnd = $network;
        else
            $netStartEnd = IP4Map::mapFromText($network);

        if( count($this->members) == 0 )
            return 0;

        $result = -1;

        foreach( $this->members as $o )
        {
            $localResult = $o->includesIP4Network($netStartEnd);
            if( $localResult == 1 )
            {
                return 1;
            }
            elseif( $localResult == 2 )
            {
                $result = 2;
            }
            elseif( $localResult == 0 )
            {
                if( $result == -1 )
                    $result = 0;
            }
        }

        return $result;
    }

    /**
     * @return IP4Map
     */
    public function getIP4Mapping()
    {
        $mapObject = new IP4Map();

        if( $this->isDynamic() )
        {
            $mapObject->unresolved[$this->name] = $this;
            return $mapObject;
        }

        foreach( $this->members as $member )
        {
            if( $member->isTmpAddr() && !$member->nameIsValidRuleIPEntry() )
            {
                $mapObject->unresolved[$member->name()] = $member;
                continue;
            }
            elseif( $member->isAddress() )
            {
                if( $member->type() == 'fqdn' )
                {
                    $mapObject->unresolved[$member->name()] = $member;
                }
                else
                {
                    $localMap = $member->getIP4Mapping();
                    $mapObject->addMap($localMap, TRUE);
                }
            }
            elseif( $member->isGroup() )
            {
                $localMap = $member->getIP4Mapping();
                $mapObject->addMap($localMap, TRUE);
            }
            else
                derr('unsupported type of objects ' . $member->toString());
        }
        $mapObject->sortAndRecalculate();

        return $mapObject;
    }

    public function getFullMapping()
    {
        $result = array('unresolved' => array());
        $mapObject = new IP4Map();

        foreach( $this->members as $member )
        {
            if( $member->isTmpAddr() && !$member->nameIsValidRuleIPEntry() )
            {
                $result['unresolved'][spl_object_hash($member)] = $member;
                continue;
            }
            elseif( $member->isAddress() )
            {
                if( $member->type() == 'fqdn' )
                {
                    $result['unresolved'][spl_object_hash($member)] = $member;
                }
                else
                {
                    $localMap = $member->getIP4Mapping();
                    $mapObject->addMap($localMap, TRUE);
                }
            }
            elseif( $member->isGroup() )
            {
                if( $member->isDynamic() )
                    $result['unresolved'][spl_object_hash($member)] = $member;
                else
                {
                    $localMap = $member->getFullMapping();
                    $mapObject->addMap($localMap['ip4'], TRUE);
                    foreach( $localMap['unresolved'] as $unresolvedEntry )
                        $result['unresolved'][spl_object_hash($unresolvedEntry)] = $unresolvedEntry;
                }
            }
            else
                derr('unsupported type of objects ' . $member->toString());
        }
        $mapObject->sortAndRecalculate();

        $result['ip4'] = $mapObject;

        return $result;
    }

    public function hasGroupinGroup()
    {
        $is_group = FALSE;
        foreach( $this->members() as $member )
        {
            if( $member->isGroup() )
                $is_group = TRUE;
        }

        return $is_group;
    }

    public function getGroupNamerecursive($group_name_array)
    {
        foreach( $this->members() as $member )
        {
            if( $member->isGroup() )
            {
                $group_name_array[] = $member->name();
                $group_name_array = $member->getGroupNamerecursive($group_name_array);
            }
        }
        return $group_name_array;
    }


    public function replaceByMembersAndDelete($padding = "", $isAPI = FALSE, $rewriteXml = TRUE, $forceAny = FALSE)
    {
        if( !$this->isGroup() )
        {
            echo $padding . " - SKIPPED : it's not a group\n";
            return;
        }

        if( $this->owner === null )
        {
            echo $padding . " -  SKIPPED : object was previously removed\n";
            return;
        }

        $objectRefs = $this->getReferences();
        $clearForAction = TRUE;
        foreach( $objectRefs as $objectRef )
        {
            $class = get_class($objectRef);
            if( $class != 'AddressRuleContainer' && $class != 'AddressGroup' )
            {
                $clearForAction = FALSE;
                echo "- SKIPPED : it's used in unsupported class $class\n";
                return;
            }
        }
        if( $clearForAction )
        {
            foreach( $objectRefs as $objectRef )
            {
                $class = get_class($objectRef);
                /** @var AddressRuleContainer|AddressGroup $objectRef */

                if( $objectRef->owner === null )
                {
                    echo $padding . "  - SKIPPED because object already removed ({$objectRef->toString()})\n";
                    continue;
                }

                echo $padding . "  - adding members in {$objectRef->toString()}\n";

                if( $class == 'AddressRuleContainer' )
                {
                    /** @var AddressRuleContainer $objectRef */
                    foreach( $this->members() as $objectMember )
                    {
                        if( $isAPI )
                            $objectRef->API_add($objectMember);
                        else
                            $objectRef->addObject($objectMember);

                        echo $padding . "     -> {$objectMember->toString()}\n";
                    }
                    if( $isAPI )
                        $objectRef->API_remove($this, $forceAny);
                    else
                        $objectRef->remove($this, $rewriteXml, $forceAny);
                }
                elseif( $class == 'AddressGroup' )
                {
                    /** @var AddressGroup $objectRef */
                    foreach( $this->members() as $objectMember )
                    {
                        if( $isAPI )
                            $objectRef->API_addMember($objectMember);
                        else
                            $objectRef->addMember($objectMember);
                        echo $padding . "     -> {$objectMember->toString()}\n";
                    }
                    if( $isAPI )
                        $objectRef->API_removeMember($this);
                    else
                        $objectRef->removeMember($this, $rewriteXml);
                }
                else
                {
                    derr('unsupported class');
                }

            }

            if( $isAPI )
                $this->owner->API_remove($this, TRUE);
            else
                $this->owner->remove($this, TRUE);
        }
    }


    static protected $templatexml = '<entry name="**temporarynamechangeme**"></entry>';
    static protected $templatexml_v6 = '<entry name="**temporarynamechangeme**"><static></static></entry>';
    static protected $templatexmlroot = null;
}



