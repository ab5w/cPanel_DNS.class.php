<?php

class cPanel_DNS {

    private $whmusername;
    private $whmpassword;
    private $server;

    public function __construct($username,$password,$server) {

        $this->whmusername = $username;
        $this->whmpassword = $password;
        $this->server = $server;

    }

    private function api($query) {

        $curl = curl_init();
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,0);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,0);
            curl_setopt($curl, CURLOPT_HEADER,0);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
            $header[0] = "Authorization: Basic " . base64_encode($this->whmusername . ":" . $this->whmpassword) . "\n\r";
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
            curl_setopt($curl, CURLOPT_URL, "https://" . $this->server . ":2087/json-api/" . $query);

        $result = curl_exec($curl);

        $result = json_decode($result,true);

        curl_close($curl);

        return $result;

    }

    public function check($hostname) {

        $query = ("dumpzone?domain=" . $hostname);

        $result = $this->api($query);

        $zoneexist = $result['result'][0]['status'];

        if ($zoneexist == 0) {

            $zoneexist = "0";

        } else {

            $zoneexist = "1";

        }

        return $zoneexist;

    }

    public function forward_zone($ip,$hostname) {

        $query = ("adddns?domain=" . $hostname . "&ip=" . $ip . "&template=standard&trueowner=" . $this->whmusername);

        $result = $this->api($query);

        if ($result['result'][0]['status'] == 1) {

            $result = "added";

        } else {

            $result = "failed";

        }

        return $result;

    }

    private function DNS_add_reverse_zone_record($revip,$ptr,$hostname) {

        $query = ("addzonerecord?zone=" . $revip . ".in-addr.arpa&name=" . $ptr . "&ptrdname=" . $hostname ."&type=PTR");

        $result = $this->api($query);

        if ($result['result'][0]['status'] == 1) {

            $result = "added";

        } else {

            $result = "failed";

        }

        return $result;

    }

    private function DNS_edit_reverse_zone($revip,$ptr,$hostname,$line) {

        $query = ("editzonerecord?zone=" . $revip . ".in-addr.arpa&name=" . $ptr . "&ptrdname=" . $hostname . "&type=PTR&Line=" . $line);

        $result = $this->api($query);

        if ($result['result'][0]['status'] == 1) {

            $result = "added";

        } else {

            $result = "failed";

        }

        return $result;

    }

    public function reverse_zone($ip,$hostname) {

        $revipa = explode(".", $ip);
        $revip = $revipa[2] . '.' . $revipa[1] . '.' . $revipa[0];
        $ptr = $revipa[3];
        $ptrname = $revipa[3] . '.' . $revipa[2] . '.' .$revipa[1] . '.' . $revipa[0] . '.in-addr.arpa.';

        $query = ("dumpzone?domain=" . $revip . ".in-addr.arpa");

        $results = $this->api($query);

        $results = $results['result'][0]['record'];

        foreach ($results as $result) {

            //If a PTR already exists for that IP.
            if (array_key_exists('name', $result) and $result['name'] == $ptrname) {

                //If the PTR hostname is not set to the one you are adding.
                if ($result['ptrdname'] !== $hostname) {

                    //Grab the line number of the PTR (needed for editing).
                    $line = $result['Line'];
                    //Edit the PTR to set the hostname to the one you are adding.
                    $edit = $this->DNS_edit_reverse_zone($revip,$ptr,$hostname,$line);
                    //Say it's added.
                    $added = "true";

                //Else if it is already correct.
                } elseif ($result['ptrdname'] == $hostname) {

                    $edit = "added";

                    //Say it's added.
                    $added = "true";

                }

            }

        }

        //If the PTR doesn't exist.
        if ($added !== "true") {

            //Add the record.
            $add = $this->DNS_add_reverse_zone_record($revip,$ptr,$hostname);

            return $add;

        } else {

            return $edit;

        }

    }

    public function edit_reverse_zone($ip,$hostname) {

        $revipa = explode(".", $ip);
        $revip = $revipa[2] . '.' . $revipa[1] . '.' . $revipa[0];
        $ptr = $revipa[3];
        $ptrname = $revipa[3] . '.' . $revipa[2] . '.' .$revipa[1] . '.' . $revipa[0] . '.in-addr.arpa.';

        $query = ("dumpzone?domain=" . $revip . ".in-addr.arpa");

        $results = $this->api($query);

        $results = $results['result'][0]['record'];

        foreach ($results as $result) {

            //If a PTR already exists for that IP.
            if (array_key_exists('name', $result) and $result['name'] == $ptrname) {

                    //Grab the line number of the PTR (needed for editing).
                    $line = $result['Line'];
                    //Edit the PTR to set the hostname to the one you are adding.
                    $edit = $this->DNS_edit_reverse_zone($revip,$ptr,$hostname,$line);
                    //Say it's edited.
                    $edit = "edited";

            }

        }

        return $edit;

    }

    private function DNS_remove_zone_record($zone,$line) {

        $query = ("removezonerecord?zone=" . $zone . "&line=" . $line);

        $results = $this->api($query);

        if ($results['result'][0]['status'] == 1) {

            $result = "removed";

        } else {

            $result = "failed";

        }

        return $result;

    }

    public function remove_reverse($ip) {

        $revipa = explode(".", $ip);
        $revip = $revipa[2] . '.' . $revipa[1] . '.' . $revipa[0];

        $zone = $revip . ".in-addr.arpa";

        $query = ("dumpzone?domain=" . $revip . ".in-addr.arpa");

        $results = $this->api($query);

        $results = $results['result'][0]['record'];

        foreach ($results as $result) {

            if (array_key_exists('ptrdname', $result) and $result['ptrdname'] == $zone_delete) {

                $line = $result['Line'];
                $remove = $this->DNS_remove_zone_record($zone,$line);

                return $remove;

            }

        }

        if (empty($remove)) {

            $result = 'failed';
            return $result;

        }

    }

    public function remove_forward($zone) {

        $query = ("killdns?domain=" . $zone);

        $result = $this->api($query);

        if ($result['result'][0]['status'] == 1) {

            $result = "removed";

        } else {

            $result = "failed";

        }

        return $result;

    }

}