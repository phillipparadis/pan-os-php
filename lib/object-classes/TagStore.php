<?php

/**
 * © 2019 Palo Alto Networks, Inc.  All rights reserved.
 *
 * Licensed under SCRIPT SOFTWARE AGREEMENT, Palo Alto Networks, Inc., at https://www.paloaltonetworks.com/legal/script-software-license-1-0.pdf
 *
 */

/**
 * Class TagStore
 * @property Tag[] $o
 * @property VirtualSystem|DeviceGroup|PanoramaConf|PANConf|Container|DeviceCloud $owner
 * @method Tag[] getAll()
 */
class TagStore extends ObjStore
{
    /** @var null|TagStore */
    protected $parentCentralStore = null;

    public static $childn = 'Tag';


    public function __construct($owner)
    {
        $this->classn = &self::$childn;

        $this->owner = $owner;
        $this->o = array();

        if( isset($owner->parentDeviceGroup) && $owner->parentDeviceGroup !== null )
            $this->parentCentralStore = $owner->parentDeviceGroup->tagStore;
        elseif( isset($owner->parentContainer) && $owner->parentContainer !== null )
        {
            $this->parentCentralStore = $owner->parentContainer->tagStore;
        }
        else
            $this->findParentCentralStore();

    }

    /**
     * @param $name
     * @param null $ref
     * @param bool $nested
     * @return null|Tag
     */
    public function find($name, $ref = null, $nested = TRUE)
    {
        $f = $this->findByName($name, $ref);

        if( $f !== null )
            return $f;

        if( $nested && $this->parentCentralStore !== null )
            return $this->parentCentralStore->find($name, $ref, $nested);

        return null;
    }

    public function removeAllTags()
    {
        $this->removeAll();
        $this->rewriteXML();
    }

    /**
     * add a Tag to this store. Use at your own risk.
     * @param Tag $Obj
     * @param bool
     * @return bool
     */
    public function addTag(Tag $Obj, $rewriteXML = TRUE)
    {
        $ret = $this->add($Obj);
        if( $ret && $rewriteXML )
        {
            if( $this->xmlroot === null )
                $this->xmlroot = DH::findFirstElementOrCreate('tag', $this->owner->xmlroot);

            $this->xmlroot->appendChild($Obj->xmlroot);
        }
        return $ret;
    }

    /**
     * @param string $base
     * @param string $suffix
     * @param integer|string $startCount
     * @return string
     */
    public function findAvailableTagName($base, $suffix, $startCount = '')
    {
        $maxl = 31;
        $basel = strlen($base);
        $suffixl = strlen($suffix);
        $inc = $startCount;
        $basePlusSuffixL = $basel + $suffixl;

        while( TRUE )
        {
            $incl = strlen(strval($inc));

            if( $basePlusSuffixL + $incl > $maxl )
            {
                $newname = substr($base, 0, $basel - $suffixl - $incl) . $suffix . $inc;
            }
            else
                $newname = $base . $suffix . $inc;

            if( $this->find($newname) === null )
                return $newname;

            if( $startCount == '' )
                $startCount = 0;
            $inc++;
        }
    }


    /**
     * return tags in this store
     * @return Tag[]
     */
    public function tags()
    {
        return $this->o;
    }

    function createTag($name, $ref = null)
    {
        if( $this->find($name, null, FALSE) !== null )
            derr('Tag named "' . $name . '" already exists, cannot create');

        if( $this->xmlroot === null )
        {
            if( $this->owner->isDeviceGroup() || $this->owner->isVirtualSystem() || $this->owner->isContainer() || $this->owner->isDeviceCloud() )
                $this->xmlroot = DH::findFirstElementOrCreate('tag', $this->owner->xmlroot);
            else
                $this->xmlroot = DH::findFirstElementOrCreate('tag', $this->owner->sharedroot);
        }

        $newTag = new Tag($name, $this);
        $newTag->owner = null;

        $newTagRoot = DH::importXmlStringOrDie($this->owner->xmlroot->ownerDocument, Tag::$templatexml);
        $newTagRoot->setAttribute('name', $name);
        $newTag->load_from_domxml($newTagRoot);

        if( $ref !== null )
            $newTag->addReference($ref);

        $this->addTag($newTag);

        return $newTag;
    }

    function findOrCreate($name, $ref = null, $nested = TRUE)
    {
        $f = $this->find($name, $ref, $nested);

        if( $f !== null )
            return $f;

        return $this->createTag($name, $ref);
    }

    function API_createTag($name, $ref = null)
    {
        $newTag = $this->createTag($name, $ref);

        if( !$newTag->isTmp() )
        {
            $xpath = $this->getXPath();
            $con = findConnectorOrDie($this);
            $element = $newTag->getXmlText_inline();
            $con->sendSetRequest($xpath, $element);
        }

        return $newTag;
    }


    /**
     * @param Tag $tag
     *
     * @return bool  True if Zone was found and removed. False if not found.
     */
    public function removeTag(Tag $tag)
    {
        $ret = $this->remove($tag);

        if( $ret && !$tag->isTmp() && $this->xmlroot !== null )
        {
            $this->xmlroot->removeChild($tag->xmlroot);
        }

        return $ret;
    }

    /**
     * @param Tag $tag
     * @return bool
     */
    public function API_removeTag(Tag $tag)
    {
        $xpath = null;

        if( !$tag->isTmp() )
            $xpath = $tag->getXPath();

        $ret = $this->removeTag($tag);

        if( $ret && !$tag->isTmp() )
        {
            $con = findConnectorOrDie($this);
            $con->sendDeleteRequest($xpath);
        }

        return $ret;
    }

    public function &getXPath()
    {
        $str = '';

        if( $this->owner->isDeviceGroup() || $this->owner->isVirtualSystem() || $this->owner->isContainer() || $this->owner->isDeviceCloud() )
            $str = $this->owner->getXPath();
        elseif( $this->owner->isPanorama() || $this->owner->isFirewall() )
            $str = '/config/shared';
        else
            derr('unsupported');

        $str = $str . '/tag';

        return $str;
    }


    private function &getBaseXPath()
    {
        if( $this->owner->isPanorama() || $this->owner->isFirewall() )
        {
            $str = "/config/shared";
        }
        else
            $str = $this->owner->getXPath();


        return $str;
    }

    public function &getTagStoreXPath()
    {
        $path = $this->getBaseXPath() . '/tag';
        return $path;
    }

    public function rewriteXML()
    {
        if( count($this->o) > 0 )
        {
            if( $this->xmlroot === null )
                return;

            $this->xmlroot->parentNode->removeChild($this->xmlroot);
            $this->xmlroot = null;
        }

        if( $this->xmlroot === null )
        {
            if( count($this->o) > 0 )
                DH::findFirstElementOrCreate('tag', $this->owner->xmlroot);
        }

        DH::clearDomNodeChilds($this->xmlroot);
        foreach( $this->o as $o )
        {
            if( !$o->isTmp() )
                $this->xmlroot->appendChild($o->xmlroot);
        }
    }


    /**
     *
     * @ignore
     */
    protected function findParentCentralStore()
    {
        $this->parentCentralStore = null;

        $cur = $this->owner;
        while( isset($cur->owner) && $cur->owner !== null )
        {
            $ref = $cur->owner;
            if( isset($ref->tagStore) &&
                $ref->tagStore !== null )
            {
                $this->parentCentralStore = $ref->tagStore;
                //print $this->toString()." : found a parent central store: ".$parentCentralStore->toString()."\n";
                return;
            }
            $cur = $ref;
        }

    }

    /**
     * @return Tag[]
     */
    public function nestedPointOfView()
    {
        $current = $this;

        $objects = array();

        while( TRUE )
        {
            foreach( $current->o as $o )
            {
                if( !isset($objects[$o->name()]) )
                    $objects[$o->name()] = $o;
            }


            if( isset($current->owner->owner) && $current->owner->owner !== null && !$current->owner->owner->isFawkes() )
                $current = $current->owner->owner->tagStore;
            else
                break;
        }

        return $objects;
    }

}


