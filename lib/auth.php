<?php

class auth
{

    public function login($user, $pass, $cardId)
    {
        if($user != null && $pass != null) {
            $username = $user;
            $password = $pass;
            $adServer = "ldap://plsts1-s0100.mcint.local";

            $ldap = ldap_connect($adServer);
            $ldaprdn = 'MCINT' . "\\" . $username;

            ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

            $bind = @ldap_bind($ldap, $ldaprdn, $password);

            //default columns
            $db = new database();
            $res = $db->queryOne("exec authorize_user '$user'");
            $db->close();

            if($bind && $res !== 'not authenticated'){
                $status =  json_encode(["status" => 1]);
            } else {
                $status =  json_encode(["status" => 0]);
            }
        } else if ($cardId != null) {
            $db = new database();

            $zoneId = $db->queryOne("exec authorize_user '$cardId'");

            if($zoneId !== 'not authenticated') {
                $status =  json_encode(["status" => 1]);
            } else {
                $status = json_encode(["status" => 0 ]);
            }
        }

      return $status;
    }
}
