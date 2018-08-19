<?php
header('Content-Type: application/json');
echo file_get_contents("https://nordvpn.com/wp-admin/admin-ajax.php?action=get_user_info_data");
