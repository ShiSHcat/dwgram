<?php
require __DIR__."/../../config/userbots.php";
$stop = "";
$start = "";
$restart = "";
$enable = "";
foreach ($servers as $k=>$v) {
    $sname = \str_replace(".madeline", "", $k);
    $serv = <<<SERVICE
[Unit]
Description=dwgr$sname
After=network.target
StartLimitIntervalSec=0

[Service]
Restart=on-failure
RestartSec=5s
User=shishcat
WorkingDirectory=SECRET_DIRNAME/dwgram
ExecStart=/usr/bin/php SECRET_DIRNAME/dwgram/index.php $k $v

[Install]
WantedBy=multi-user.target
SERVICE;
    \file_put_contents("/etc/systemd/system/dwgram$sname.service", $serv);
    $stop.="sudo systemctl stop dwgram$sname\n";
    $start.="sudo systemctl start dwgram$sname\n";
    $restart.="sudo systemctl restart dwgram$sname\n";
    $enable.="sudo systemctl enable dwgram$sname\n";
}
\file_put_contents("start.sh", $start);
\file_put_contents("stop.sh", $stop);
\file_put_contents("restart.sh", $restart);
\file_put_contents("enable.sh", $enable);
