<?php


function get_address_SIDEWINDER($source, $vsys, $template, $config_path)
{
    global $projectdb;

    $mcafee_config_file = file($config_path);

    $addAddress = array();

    foreach( $mcafee_config_file as $line2 => $names_line2 )
    {

        if( preg_match("/^ipaddr add/i", $names_line2) )
        {
            preg_match_all('`(\w+(=(([0-9+|\w+][\.|/|,|:|-]?)+|[\'|"].*?[\'|"]))?)`', $names_line2, $matches);
            $name = "";
            $ipaddr = "";
            $description = "";
            $name_int = "";
            $addr_type = "ip-netmask";
            $cidr = 32;

            foreach( $matches[0] as $key => $option )
            {
                $option = str_replace("'", "", $option);
                if( preg_match("/^name=/", $option) )
                {
                    $name_tmp = explode("=", $option);
                    $name_int = truncate_names(normalizeNames($name_tmp[1]));
                    $name = $name_int;

                }
                elseif( preg_match("/^ipaddr=/", $option) )
                {
                    $ipaddr_tmp = explode("=", $option);
                    $ipaddr = $ipaddr_tmp[1];
                    $ip_version = ip_version($ipaddr);
                    if( $ip_version == "noip" )
                    {
                        $ip_version = "v4";
                    }
                }
                elseif( preg_match("/^description=/", $option) )
                {
                    $ipaddr_tmp = explode("=", $option);
                    $description = normalizeComments($ipaddr_tmp[1]);

                }

            }
            $addAddress[] = "('$name','$name_int',0,'$source','$vsys','$addr_type',1,'$ipaddr','$cidr','$description')";
        }
        elseif( preg_match("/^host add/i", $names_line2) )
        {

            preg_match_all('`(\w+(=(([0-9+|\w+][\.|/|,|:|-]?)+|[\'|"].*?[\'|"]))?)`', $names_line2, $matches);
            $name = "";
            $ipaddr = "";
            $description = "";
            $name_int = "";
            $addr_type = "ip-netmask";
            $dnsmode = "";
            $hostname = "";
            $ipaddress = "";
            $cidr = "32";
            $ip_version = "v4";

            foreach( $matches[0] as $key => $option )
            {
                $option = str_replace("'", "", $option);
                if( preg_match("/^name=/", $option) )
                {
                    $name_tmp = explode("=", $option);
                    $name = $name_tmp[1];
                    $name_int = truncate_names(normalizeNames($name_tmp[1]));
                }
                elseif( preg_match("/^ipaddr=/", $option) )
                {
                    $ipaddr_tmp = explode("=", $option);
                    $ipaddr = $ipaddr_tmp[1];
                    $ip_version = ip_version($ipaddr);
                    if( $ip_version == "noip" )
                    {
                        $ip_version = "v4";
                    }
                    $cidr = 32;
                }
                elseif( preg_match("/^description=/", $option) )
                {
                    $ipaddr_tmp = explode("=", $option);
                    $description = normalizeComments($ipaddr_tmp[1]);

                }
                elseif( preg_match("/^dns_mode=/", $option) )
                {
                    $ipaddr_tmp = explode("=", $option);
                    $dnsmode = $ipaddr_tmp[1];
                }
                elseif( preg_match("/^host=/", $option) )
                {
                    $ipaddr_tmp = explode("=", $option);
                    $hostname = $ipaddr_tmp[1];
                }
                elseif( preg_match("/^ipaddrs=/", $option) )
                {
                    $ipaddr_tmp = explode("=", $option);
                    $ipaddress = $ipaddr_tmp[1];
                    $ip_version = ip_version($ipaddress);
                    if( $ip_version == "noip" )
                    {
                        $ip_version = "v4";
                    }
                }

            }
            if( (($dnsmode == "dns") or ($dnsmode == "dns_manual_ttl")) and ($ipaddress == "") )
            {
                $addr_type = "fqdn";
                $ipaddress = $hostname;
                $cidr = "";
            }
            $addAddress[] = "('$name','$name_int',0,'$source','$vsys','$addr_type',1,'$ipaddress','$cidr','$description')";
        }
        elseif( preg_match("/^iprange add/i", $names_line2) )
        {

            preg_match_all('`(\w+(=(([0-9+|\w+][\.|/|,|:|-]?)+|[\'|"].*?[\'|"]))?)`', $names_line2, $matches);

            $name = "";
            $ipaddr = "";
            $description = "";
            $name_int = "";
            $cidr = "";
            $addr_type = "ip-range";
            $begin = "";
            $end = "";

            foreach( $matches[0] as $key => $option )
            {
                $option = str_replace("'", "", $option);
                if( preg_match("/^name=/", $option) )
                {
                    $name_tmp = explode("=", $option);
                    $name_int = truncate_names(normalizeNames($name_tmp[1]));
                    $name = $name_int;
                }
                elseif( preg_match("/^begin=/", $option) )
                {
                    $ipaddr_tmp = explode("=", $option);
                    $begin = $ipaddr_tmp[1];
                    $ip_version = ip_version($begin);
                    if( $ip_version == "noip" )
                    {
                        $ip_version = "v4";
                    }
                }
                elseif( preg_match("/^end=/", $option) )
                {
                    $ipaddr_tmp = explode("=", $option);
                    $end = $ipaddr_tmp[1];
                }
                elseif( preg_match("/^description=/", $option) )
                {
                    $ipaddr_tmp = explode("=", $option);
                    $description = normalizeComments($ipaddr_tmp[1]);
                }

            }
            $ipaddr = $begin . "-" . $end;
            $addAddress[] = "('$name','$name_int',0,'$source','$vsys','$addr_type',1,'$ipaddr','$cidr','$description')";
        }
        elseif( preg_match("/^subnet add/i", $names_line2) )
        {

            preg_match_all('`(\w+(=(([0-9+|\w+][\.|/|,|:|-]?)+|[\'|"].*?[\'|"]))?)`', $names_line2, $matches);

            $name = "";
            $ipaddr = "";
            $description = "";
            $name_int = "";
            $cidr = "";
            $addr_type = "ip-netmask";
            $begin = "";
            $end = "";

            foreach( $matches[0] as $key => $option )
            {
                $option = str_replace("'", "", $option);
                if( preg_match("/^name=/", $option) )
                {
                    $name_tmp = explode("=", $option);
                    $name_int = truncate_names(normalizeNames($name_tmp[1]));
                    $name = $name_int;
                }
                elseif( preg_match("/^subnet=/", $option) )
                {
                    $ipaddr_tmp = explode("=", $option);
                    $begin = $ipaddr_tmp[1];
                    $ip_version = ip_version($begin);
                    if( $ip_version == "noip" )
                    {
                        $ip_version = "v4";
                    }
                }
                elseif( preg_match("/^bits=/", $option) )
                {
                    $ipaddr_tmp = explode("=", $option);
                    $end = $ipaddr_tmp[1];
                }
                elseif( preg_match("/^description=/", $option) )
                {
                    $ipaddr_tmp = explode("=", $option);
                    $description = normalizeComments($ipaddr_tmp[1]);
                }

            }
            $addAddress[] = "('$name','$name_int',0,'$source','$vsys','$addr_type',1,'$begin','$end','$description')";
        }
    }
    if( count($addAddress) > 0 )
    {
        $projectdb->query("INSERT INTO address (name_ext,name,checkit,source,vsys,type,$ip_version,ipaddress,cidr,description) VALUES " . implode(",", $addAddress) . ";");
        #print "INSERT INTO address (name_ext,name,checkit,source,vsys,type,$ip_version,ipaddress,cidr,description) VALUES " . implode(",", $addAddress) . ";";
    }

}

/**
 * @param $source
 * @param $vsys
 * @param $template
 * @param $config_path
 */
function get_addressGroups($source, $vsys, $template, $config_path)
{
    global $projectdb;

    $mcafee_config_file = file($config_path);

    $addAddress = array();
    $addNewAddress = array();
    $addGroup = array();

    $getMaxid = $projectdb->query("SELECT max(id) as maxid FROM address_groups_id;");
    if( $getMaxid->num_rows > 0 )
    {
        $getMaxidData = $getMaxid->fetch_assoc();
        $grplid = $getMaxidData['maxid'] + 1;
    }
    else
    {
        $grplid = 1;
    }

    foreach( $mcafee_config_file as $line2 => $names_line2 )
    {

        if( preg_match("/^netgroup add/i", $names_line2) )
        {
            try
            {
                $matches = preg_split("/'[^']*'(*SKIP)(*F)|\x20/", $names_line2);
                $name = "";
                $ipaddr = "";
                $description = "";
                $name_int = "";
                $addr_type = "ip-netmask";
                $cidr = 32;

                foreach( $matches as $key => $option )
                {
                    $option = str_replace("'", "", $option);
                    if( preg_match("/^name=/", $option) )
                    {
                        $name_tmp = explode("=", $option);
                        $name_int = truncate_names(normalizeNames($name_tmp[1]));
                        $name = $name_int;
                    }
                    elseif( preg_match("/^members=/", $option) )
                    {
                        $option = str_replace("'", "", $option);
                        $ipaddr_tmp = explode("=", $option);
                        $members = explode(",", $ipaddr_tmp[1]);
                        if( count($members) > 0 )
                        {
                            foreach( $members as $keyy => $vvalue )
                            {
                                $members2 = explode(":", $vvalue);
                                $kkkey = $members2[0];
                                if( !isset($members2[1]) )
                                {
                                    continue;
                                }
                                $vvvalue = $members2[1];
                                if( $kkkey == "ipaddr" )
                                {
                                    $ipversion = ip_version($vvvalue);
                                    if( ($ipversion == "v4") or ($ipversion == "v6") )
                                    {
                                        $getAddr = $projectdb->query("SELECT name FROM address WHERE ipaddress='$vvvalue' AND source='$source' AND vsys='$vsys' LIMIT 1;");
                                        if( $getAddr->num_rows == 1 )
                                        {
                                            $getAddrData = $getAddr->fetch_assoc();
                                            $vvvalue = $getAddrData['name'];
                                            $addAddress[] = "('$grplid','$vvvalue','$source','$vsys')";
                                        }
                                        else
                                        {
                                            $name1 = "H-" . $vvvalue;
                                            $addNewAddress[] = "('$name1','$name1',0,'$source','$vsys','ip-netmask',1,'$vvvalue','32')";
                                            $addAddress[] = "('$grplid','$name1','$source','$vsys')";
                                        }
                                    }
                                    else
                                    {
                                        $getAddr = $projectdb->query("SELECT name FROM address WHERE name='$vvvalue' AND source='$source' AND vsys='$vsys' LIMIT 1;");
                                        if( $getAddr->num_rows == 1 )
                                        {
                                            $addAddress[] = "('$grplid','$vvvalue','$source','$vsys')";
                                        }
                                    }
                                }
                                elseif( $kkkey == "host" )
                                {
                                    $getAddr = $projectdb->query("SELECT name FROM address WHERE name='$vvvalue' AND source='$source' AND vsys='$vsys' LIMIT 1;");
                                    if( $getAddr->num_rows == 1 )
                                    {
                                        $addAddress[] = "('$grplid','$vvvalue','$source','$vsys')";
                                    }
                                    else
                                    {
                                        $hip_version = ip_version($vvvalue);
                                        if( $hip_version == "noip" )
                                        {
                                            $name1 = truncate_names(normalizeNames($vvvalue));
                                            $addNewAddress[] = "('$name1','$name1',0,'$source','$vsys','fqdn',1,'$vvvalue','')";
                                            $addAddress[] = "('$grplid','$name1','$source','$vsys')";
                                        }
                                        else
                                        {
                                            $name1 = $vvvalue;
                                            if( $hip_version == "v4" )
                                            {
                                                $hcidr = "32";
                                            }
                                            else
                                            {
                                                $hcidr = "128";
                                            }
                                            $addNewAddress[] = "('$name1','$name1',0,'$source','$vsys','ip-netmask',1,'$vvvalue','$hcidr')";
                                            $addAddress[] = "('$grplid','$name1','$source','$vsys')";
                                        }

                                    }
                                }
                                elseif( $kkkey == "geolocation" )
                                {

                                }
                                elseif( $kkkey == "domain" )
                                {

                                }
                                else
                                {
                                    //echo $vvvalue . "-" .$kkkey."\n";
                                    $addAddress[] = "('$grplid','$vvvalue','$source','$vsys')";
                                }
                            }
                        }

                    }
                    elseif( preg_match("/^description=/", $option) )
                    {
                        $ipaddr_tmp = explode("=", $option);
                        $description = normalizeComments($ipaddr_tmp[1]);
                    }
                }
                $addGroup[] = "('$grplid','$name','$name_int','$source','$vsys','$description')";
                $grplid++;
            } catch(Exception $e)
            {
                echo 'Caught exception: ', $e->getMessage(), "$matches \n";
            }

        }

    }
    if( count($addGroup) > 0 )
    {
        $projectdb->query("INSERT INTO address_groups_id (id,name_ext,name,source,vsys,description) VALUES " . implode(",", $addGroup) . ";");
        if( count($addAddress) > 0 )
        {
            $projectdb->query("INSERT INTO address_groups (lid,member,source,vsys) VALUES " . implode(",", $addAddress) . ";");
        }
        if( count($addNewAddress) > 0 )
        {
            $unique = array_unique($addNewAddress);
            $projectdb->query("INSERT INTO address (name_ext,name,checkit,source,vsys,type,v4,ipaddress,cidr) VALUES " . implode(",", $unique) . ";");
        }
        unset($addGroup);
        unset($addAddress);
        unset($addNewAddress);
    }

}
