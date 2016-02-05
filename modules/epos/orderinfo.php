<?php
define('__XE__', TRUE);
require '../../config/config.inc.php';
chdir('../../');

$strOrderInfo = $_POST['strOrderInfo'];
unset($_POST['strOrderInfo']);

$oContext = &Context::getInstance();
$oContext->init();

$oEposController = &getController('epos');
$oEposController->processOrderInfo($strOrderInfo);

$oContext->close();
