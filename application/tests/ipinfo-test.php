<?php

require_once('../model/IXmapsIpInfo.php');

$ii = new IXmapsIpInfo('fdfsd');
echo $ii->getCity();
echo "\n";
echo $ii->getASNum();
echo "\n";
echo $ii->getASName();