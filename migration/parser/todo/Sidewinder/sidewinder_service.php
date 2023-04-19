<?php


function get_services($source, $vsys, $template, $config_path)
{
    global $projectdb;

    $mcafee_config_file = file($config_path);

    $addAddress = array();

    $getMaxid = $projectdb->query("SELECT max(id) as maxid FROM services_groups_id;");
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

        if( (preg_match("/^service modify /i", $names_line2)) or (preg_match("/^service add /i", $names_line2)) or (preg_match("/^application modify /i", $names_line2)) or (preg_match("/^application add /i", $names_line2)) )
        {

            preg_match_all('`(\w+(=(([0-9+|\w+][\.|/|,|:|-]?)+|[\'|"].*?[\'|"]))?)`', $names_line2, $matches);

            $name = "";
            $tcp_ports = "";
            $description = "";
            $name_int = "";
            $udp_ports = "";
            $protocol = "";
            $port = "";

            foreach( $matches[0] as $key => $option )
            {
                $option = str_replace("'", "", $option);

                if( preg_match("/^name=/", $option) )
                {
                    $name_tmp = explode("=", $option);
                    $name_int = truncate_names(normalizeNames($name_tmp[1]));
                    $name = $name_int;

                }
                elseif( preg_match("/^udp_ports=/", $option) )
                {
                    $ipaddr_tmp = explode("=", $option);
                    if( $ipaddr_tmp[1] != "" )
                    {
                        $udp_ports = $ipaddr_tmp[1];
                        $protocol = "udp";
                        $port = $udp_ports;
                    }

                }
                elseif( preg_match("/^tcp_ports=/", $option) )
                {
                    $ipaddr_tmp = explode("=", $option);
                    if( $ipaddr_tmp[1] != "" )
                    {
                        $tcp_ports = $ipaddr_tmp[1];
                        $protocol = "tcp";
                        $port = $tcp_ports;
                    }
                }
                elseif( preg_match("/^protocol=/", $option) )
                {
                    $ipaddr_tmp = explode("=", $option);
                    $protocol = $ipaddr_tmp[1];
                    $port = "";
                    $port = $tcp_ports;
                }
                elseif( preg_match("/^description=/", $option) )
                {
                    $ipaddr_tmp = explode("=", $option);
                    $description = normalizeComments($ipaddr_tmp[1]);
                }

            }

            if( ($udp_ports != "") and ($tcp_ports != "") )
            {
                # Convert into Group
                $addGroup[] = "('$grplid','$name','$name_int','$source','$vsys','$description')";
                $newname = truncate_names("tcp-" . $name);
                $addMember[] = "('$grplid','$newname','$source','$vsys')";
                $addAddress[] = "('$newname','$newname',0,'$source','$vsys','tcp','$tcp_ports','$description')";
                $newname = truncate_names("udp-" . $name);
                $addMember[] = "('$grplid','$newname','$source','$vsys')";
                $addAddress[] = "('$newname','$newname',0,'$source','$vsys','udp','$udp_ports','$description')";
                $grplid++;
            }
            else
            {
                $addAddress[] = "('$name','$name_int',0,'$source','$vsys','$protocol','$port','$description')";
            }


        }
    }
    if( count($addAddress) > 0 )
    {
        $projectdb->query("INSERT INTO services (name_ext,name,checkit,source,vsys,protocol,dport,description) VALUES " . implode(",", $addAddress) . ";");
    }
    if( count($addGroup) > 0 )
    {
        $projectdb->query("INSERT INTO services_groups_id (id,name_ext,name,source,vsys,description) VALUES " . implode(",", $addGroup) . ";");
        if( count($addMember) > 0 )
        {
            $projectdb->query("INSERT INTO services_groups (lid,member,source,vsys) VALUES " . implode(",", $addMember) . ";");
        }
        unset($addAddress);
        unset($addGroup);
        unset($addMember);
    }

}

function get_servicesGroups($source, $vsys, $template, $config_path)
{
    global $projectdb;

    $mcafee_config_file = file($config_path);

    $addAddress = array();
    $addNewAddress = array();
    $addGroup = array();

    $getMaxid = $projectdb->query("SELECT max(id) as maxid FROM services_groups_id;");
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

        if( (preg_match("/^servicegroup add/i", $names_line2)) or (preg_match("/^appgroup add/i", $names_line2)) )
        {

            preg_match_all('`(\w+(=(([0-9+|\w+][\.|/|,|:|-]?)+|[\'|"].*?[\'|"]))?)`', $names_line2, $matches);

            $name = "";
            $description = "";
            $name_int = "";

            foreach( $matches[0] as $key => $option )
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
                    //$option=str_replace("'","",$option);
                    $ipaddr_tmp = explode("=", $option);
                    $members = explode(",", $ipaddr_tmp[1]);
                    foreach( $members as $keyy => $vvalue )
                    {

                        $vvalue = str_replace("application:", "", $vvalue);
                        $vvalue = str_replace("custom:", "", $vvalue);

                        $addAddress[] = "('$grplid','$vvalue','$source','$vsys')";
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

        }
    }

    if( count($addGroup) > 0 )
    {
        $projectdb->query("INSERT INTO services_groups_id (id,name_ext,name,source,vsys,description) VALUES " . implode(",", $addGroup) . ";");
        if( count($addAddress) > 0 )
        {
            $projectdb->query("INSERT INTO services_groups (lid,member,source,vsys) VALUES " . implode(",", $addAddress) . ";");
        }
        unset($addGroup);
        unset($addAddress);
    }

}
