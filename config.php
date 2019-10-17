<?php
//error_reporting(E_ALL | E_STRICT);
//@ini_set('display_errors', '1');

$CFG = new stdClass;
$CFG->dbtype = 'pgsql';
$CFG->dblibrary = 'native';
//$CFG->dbhost = 'destinyedu-moodle-rds-1cnmsnegllv-databasecluster-1ogch1irkeyio.cluster-cnblvmibsmpc.us-east-2.rds.amazonaws.com';
//$CFG->dbhost = 'moodle-3-6-cluster.cluster-cnblvmibsmpc.us-east-2.rds.amazonaws.com';
$CFG->dbhost = 'destinyedu-m371a-rds-1cnmsnegllv-databasecluster-cluster.cluster-cnblvmibsmpc.us-east-2.rds.amazonaws.com';
$CFG->dbname = 'moodle';
$CFG->dbuser = 'moodle';
$CFG->dbpass = 'MCW32020~!';
$CFG->prefix = 'mdl_';
$CFG->lang = 'en';
$CFG->dboptions = array(
  'dbpersist' => false,
  'dbsocket' => false,
  'dbport' => '',
  'dbhandlesoptions' => false,
  'dbcollation' => 'utf8mb4_unicode_ci',
);
// Hostname definition //
$hostname = 'www.destinyedu.com';
if ($hostname == '') {
  $hostwithprotocol = 'http://Desti-Publi-1GMT7EWQHCIDS-1944796525.us-east-2.elb.amazonaws.com';
}
else {
  $hostwithprotocol = 'https://' . strtolower($hostname);
}
$CFG->wwwroot = strtolower($hostwithprotocol);
$CFG->sslproxy = (substr($hostwithprotocol,0,5)=='https' ? true : false);
// Moodledata location //
$CFG->dataroot = '/var/www/moodle/data';
$CFG->tempdir = '/var/www/moodle/temp';
$CFG->cachedir = '/var/www/moodle/cache';
$CFG->localcachedir = '/var/www/moodle/local';
// Configure Session Cache
$SessionEndpoint = 'destinycachesession.ezjupp.cfg.use2.cache.amazonaws.com';
if ($SessionEndpoint != '') {
  $CFG->dbsessions = false;
  $CFG->session_handler_class = '\core\session\memcached';
  $CFG->session_memcached_save_path = $SessionEndpoint;
  $CFG->session_memcached_prefix = 'memc.sess.key.';
  $CFG->session_memcached_acquire_lock_timeout = 120;
  $CFG->session_memcached_lock_expire = 7200;
  $CFG->session_memcached_lock_retry_sleep = 150;
}
require_once(__DIR__ . '/lib/setup.php');
// END OF CONFIG //

//$CFG->debug = (E_ALL | E_STRICT);
//$CFG->debugdisplay = 1;
?>

