<?php

namespace models;

abstract class Config {
	
	public static $sources = array();
	public static $autoloader = array();
	public static $start_session = true;
	public static $keep_logs = true;
	public static $log_path = 'logs\\';
	public static $search;
	public static $db;
	public static $db_type = 'couchdb';
	public static $query_caching = true;
	public static $cache_path = 'cache\\';
	public static $query_cachetime = 60;
	public static $debug = false;
	public static $file_root;
	public static $src_path;
	public static $root_url;
	public static $bootstrap;
	public static $php_version;
	public static $default_host;
	public static $updates_buffer;
	public static $app_name;
	public static $theme;
	public static $script_path;
	public static $script_ext;
	public static $external_scripts;
	public static $main_view;
	public static $google_loader_scripts;
	public static $app;
		
}