<?php
/**
 * Joomla Global Configuration
 *
 * This file has been modified by the Akeeba Backup Restoration Script, when restoring or transferring your site.
 * 
 * This comment is removed whe you save the Global Configuration from Joomla's interface and/or when a third party
 * extension modifies your site's Global Configuration.
 * 
 * You can find the contents of the original file the Restoration Script read from your site in the 
 * configuration.bak.php file, located in the same directory as this file here. 
 */
class JConfig
{
	public $MetaAuthor = true;
	public $MetaDesc = 'My Joomla CMS';
	public $MetaRights = '';
	public $MetaVersion = false;
	public $access = 1;
	public $asset_id = '1';
	public $behind_loadbalancer = false;
	public $cache_handler = 'file';
	public $cache_platformprefix = false;
	public $cachetime = 15;
	public $caching = 0;
	public $captcha = '0';
	public $cookie_domain = '';
	public $cookie_path = '';
	public $cors = false;
	public $cors_allow_headers = 'Content-Type,X-Joomla-Token';
	public $cors_allow_methods = '';
	public $cors_allow_origin = '*';
	public $db = 'joomla';
	public $dbencryption = false;
	public $dbprefix = 'jossm_';
	public $dbsslca = '';
	public $dbsslcert = '';
	public $dbsslcipher = '';
	public $dbsslkey = '';
	public $dbsslverifyservercert = false;
	public $dbtype = 'mysqli';
	public $debug = false;
	public $debug_lang = false;
	public $debug_lang_const = true;
	public $display_offline_message = 1;
	public $editor = 'tinymce';
	public $error_reporting = 'default';
	public $feed_email = 'none';
	public $feed_limit = 10;
	public $force_ssl = '0';
	public $fromname = 'My Joomla';
	public $frontediting = 1;
	public $gzip = true;
	public $helpurl = 'https://help.joomla.org/proxy?keyref=Help{major}{minor}:{keyref}&lang={langcode}';
	public $host = 'localhost';
	public $lifetime = 15;
	public $list_limit = 20;
	public $live_site = '';
	public $log_categories = '';
	public $log_category_mode = 0;
	public $log_deprecated = 0;
	public $log_everything = 0;
	public $log_path = 'C:/xampp/htdocs/joomla/administrator/logs';
	public $log_priorities = array (
'0' => 'all'
);
	public $mailer = 'mail';
	public $mailfrom = 'hristodar@gmail.com';
	public $mailonline = true;
	public $massmailoff = false;
	public $memcached_compress = false;
	public $memcached_persist = true;
	public $memcached_server_host = 'localhost';
	public $memcached_server_port = 11211;
	public $offline = false;
	public $offline_image = '';
	public $offline_message = 'This site is down for maintenance.<br />Please check back again soon.';
	public $offset = 'UTC';
	public $password = '';
	public $proxy_enable = false;
	public $proxy_host = '';
	public $proxy_pass = '';
	public $proxy_port = '';
	public $proxy_user = '';
	public $redis_persist = true;
	public $redis_server_auth = '';
	public $redis_server_db = 0;
	public $redis_server_host = 'localhost';
	public $redis_server_port = 6379;
	public $replyto = '';
	public $replytoname = '';
	public $robots = 'noindex, nofollow';
	public $secret = 'BFbO8U9CdMAOfqaSRVAN3KRYRN3CiOM3';
	public $sef = true;
	public $sef_rewrite = true;
	public $sef_suffix = false;
	public $sendmail = '/usr/sbin/sendmail';
	public $session_filesystem_path = '';
	public $session_handler = 'database';
	public $session_memcached_server_host = 'localhost';
	public $session_memcached_server_port = 11211;
	public $session_metadata = true;
	public $session_metadata_for_guest = true;
	public $session_redis_persist = 1;
	public $session_redis_server_auth = '';
	public $session_redis_server_db = 0;
	public $session_redis_server_host = 'localhost';
	public $session_redis_server_port = 6379;
	public $shared_session = false;
	public $sitename = 'My Joomla';
	public $sitename_pagetitles = 2;
	public $smtpauth = false;
	public $smtphost = 'localhost';
	public $smtppass = '';
	public $smtpport = 25;
	public $smtpsecure = 'none';
	public $smtpuser = '';
	public $tmp_path = 'C:/xampp/htdocs/joomla/tmp';
	public $unicodeslugs = true;
	public $user = 'root';
}
