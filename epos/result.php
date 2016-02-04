<?php
define('__XE__', TRUE);
require '../../config/config.inc.php';
chdir('../../');

$strRsXML = $_POST['strRsXML'];
unset($_POST['strRsXML']);

$oContext = &Context::getInstance();
$oContext->init();

$oEposController = &getController('epos');
$oEposController->processResult($strRsXML);

$oContext->close();
