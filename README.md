# cPanel_DNS.class.php
PHP class to interact with cPanel's DNS functions via their API.

Usage:

    include 'cPanel_DNS.class.php';
    $dns = new cPanel_DNS($WHM_username,$WHM_password,$cPanel_server);

Check to see if a zone exists for a domain.

    $dns->check($domain);

Add a forward DNS zone.

    $dns->forward_zone($ip,$domain);

Add a reverse PTR.

    $dns->reverse_zone($ip,$domain);

Edit a reverse zone.

    $dns->edit_reverse_zone($ip,$domain);

Remove a reverse PTR entry.

    $dns->remove_reverse($ip);

Remove a forward zone.

    $dns->remove_forward($domain);
