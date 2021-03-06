# DWGram API dump.
[![author shishcat](https://img.shields.io/badge/author-ShiSHcat8214-red)](https://shishc.at)
[![author-site pato05](https://img.shields.io/badge/author--site-pato05-red)](https://t.me/pato05)
![license AGPL](https://img.shields.io/badge/LICENSE-AGPL-green)
[![MadelineProto](https://img.shields.io/badge/framework--telegram-MadelineProto-yellow)](https://github.com/danog/MadelineProto)
[![AMP](https://img.shields.io/badge/framework--php-AMP-yellow)](https://github.com/amphp/)

Dump of the DWGram API sources.\
I made this project to get experience with the [Amp Framework](https://github.com/amphp).\
The idea of this project is to provide an easy to use API to get media and messages from Telegram channels (both private and public) and public groups.\
The code is very messy and full of bugs, I'd advice a rewrite.\
This is one of my first projects made during Italy quarantine: march 2020 - may 2020. After this project i started working on [ShiSHtransfer](https://github.com/shishcat/shishtransfer)

### ⚠️ Neither me or Pato05 assume any responsability. The software in this repository is provided without any warranty. If you decide to run this, you fully understand what it does and everything caused by it is completely your fault.

## Requirements
MySQL\
Atleast 4 Telegram accounts (possibily fake)\
systemd (raccomended, you can install it yourself on whatever you want but I give configs only for systemd)

## Installation 
⚠️ Neither me or Pato05 assume any responsability. The software in this repository is provided without any warranty. If you decide to run this, you fully understand what it does and everything caused by it is completely your fault.

1. You have to make the database and table yourself. Down this readme there's the DB guide.
2. Search for `SECRET` and change all the hardcoded stuff.
3. Go to `sessions` and login in all the accounts with the command `php index.php [accountnumber]`
4. Edit config/userbots.php to represent your account configuration, format is `account`=>`port where it's going to be hosted`,
5. `cd ../installer` and run the php installer and then the .sh scripts with sudo, you should figure out yourself how, because you have to understand fully what it does. If you need help, contact me here: @shishcat2 on Telegram
6. Make a custom systemd config for the load balancer and start it
7. Point a cronjob at `SECRET_SITEURL/SECRET_URL/?sprivtoken1=SECRET_TOKEN&action=midnight` every 4 hours or midnight
8. Point a cronjob at `SECRET_SITEURL/SECRET_URL/?sprivtoken1=SECRET_TOKEN&action=cron` every 15 minutes
9. You're ready.

## DB guide
Make a database called dwgram and a table called access_data as following:
![guide](https://support.iranianvacuums.com/attachment/936f666f80d3b2da3df64ecd34efb3ff.png)
