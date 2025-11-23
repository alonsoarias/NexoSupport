<?php
// Definiciones globales para saneado y tipo de parámetros de entrada.
if (!defined('PARAM_RAW')) define('PARAM_RAW', 0);
if (!defined('PARAM_CLEAN')) define('PARAM_CLEAN', 1);
if (!defined('PARAM_INT')) define('PARAM_INT', 2);
if (!defined('PARAM_FLOAT')) define('PARAM_FLOAT', 3);
if (!defined('PARAM_ALPHA')) define('PARAM_ALPHA', 4);
if (!defined('PARAM_ALPHANUM')) define('PARAM_ALPHANUM', 5);
if (!defined('PARAM_BOOL')) define('PARAM_BOOL', 6);
if (!defined('PARAM_NOTAGS')) define('PARAM_NOTAGS', 7);
if (!defined('PARAM_TEXT')) define('PARAM_TEXT', 8);
if (!defined('PARAM_ALPHAEXT')) define('PARAM_ALPHAEXT', 9);
if (!defined('PARAM_ALPHANUMEXT')) define('PARAM_ALPHANUMEXT', 10);
if (!defined('PARAM_EMAIL')) define('PARAM_EMAIL', 11);
if (!defined('PARAM_URL')) define('PARAM_URL', 12);
if (!defined('PARAM_PATH')) define('PARAM_PATH', 13);
if (!defined('PARAM_SEQUENCE')) define('PARAM_SEQUENCE', 14);