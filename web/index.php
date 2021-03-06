<?php
function exitWithMessage($template = 'error', $retryAfter = 300) {
	header('HTTP/1.0 503 Service Temporarily Unavailable');
	header('Status: 503 Service Temporarily Unavailable');
	header("Retry-After: $retryAfter");
	readfile(__DIR__ . "/$template.html");
	exit;
}

function isCacheable() {
	return $_SERVER['REQUEST_METHOD'] == 'GET' && !array_key_exists('mlt', $_COOKIE);
}
class Cache {
	private $file;
	private $request;
	private $debug = false;

	public function __construct($requestUri, $cacheDir) {
		$hash = md5($requestUri);
		$this->file = new CacheFile("$cacheDir/$hash[0]/$hash[1]/$hash[2]/$hash");
		$this->request = $requestUri;
	}

	public function get() {
		if ( ! $this->file->exists()) {
			return null;
		}
		$ttl = $this->file->getRemainingTtl();
		if ($ttl <= 0) {
			$this->purge();
			return null;
		}
		$this->log("=== CACHE HIT");
		return array(
			'data' => $this->file->read(),
			'ttl' => $ttl,
		);
	}
	public function set($content, $ttl) {
		if ( ! $ttl) {
			return;
		}
		$this->file->write($content);
		$this->file->setTtl($ttl);
		$this->log("+++ CACHE MISS ($ttl)");
	}
	private function purge() {
		$this->file->delete();
		$this->log('--- CACHE PURGE');
	}
	private function log($msg) {
		if ($this->debug) {
			error_log("$msg - $this->request");
		}
	}
}
class CacheFile {
	private $name;

	public function __construct($name) {
		$this->name = $name;
	}
	public function exists() {
		return file_exists($this->name);
	}
	public function write($content) {
		$dir = dirname($this->name);
		if ( ! file_exists($dir)) {
			mkdir($dir, 0777, true);
		}
		file_put_contents($this->name, gzdeflate(ltrim($content)));
	}
	public function read() {
		$content = file_get_contents($this->name);
		if (empty($content)) {
			return $content;
		}
		return gzinflate($content);
	}
	public function delete() {
		unlink($this->name);
		unlink("$this->name.ttl");
	}
	public function setTtl($value) {
		file_put_contents("$this->name.ttl", $value);
	}
	public function getTtl() {
		return file_get_contents("$this->name.ttl");
	}
	public function getRemainingTtl() {
		$origTtl = $this->getTtl() + rand(0, 30) /* guard for race conditions */;
		return $origTtl - $this->getAge();
	}
	public function getAge() {
		return time() - filemtime($this->name);
	}
}

$isCacheable = isCacheable();
if ($isCacheable) {
	$requestUri = $_SERVER['REQUEST_URI'];
	if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
		$requestUri .= '.ajax';
	}
	$cache = new Cache($requestUri, __DIR__.'/../app/cache/simple_http_cache');
	if (null !== ($cachedContent = $cache->get())) {
		header("Cache-Control: public, max-age=".$cachedContent['ttl']);
		echo $cachedContent['data'];
		return;
	}
}

// uncomment to enter maintenance mode
// DO NOT remove next line - it is used by the auto-update command
//exitWithMessage('maintenance');

use Symfony\Component\ClassLoader\ApcClassLoader;
use Symfony\Component\HttpFoundation\Request;

// allow generated files (cache, logs) to be world-writable
umask(0000);

$rootDir = __DIR__.'/..';
$loader = require $rootDir.'/app/bootstrap.php.cache';

try {
	// Use APC for autoloading to improve performance
	$apcLoader = new ApcClassLoader('chitanka', $loader);
	$loader->unregister();
	$apcLoader->register(true);
} catch (\RuntimeException $e) {
	// APC not enabled
}

require $rootDir.'/app/AppKernel.php';
//require $rootDir.'/app/AppCache.php';

register_shutdown_function(function(){
	$error = error_get_last();
	if ($error['type'] == E_ERROR) {
		if (preg_match('/parameters\.yml.+does not exist/', $error['message'])) {
			header('Location: install.php');
			exit;
		}
		ob_clean();
		exitWithMessage('error');
	}
});

$kernel = new AppKernel('prod', false);
$kernel->loadClassCache();
//$kernel = new AppCache($kernel);

// When using the HttpCache, we need to call the method explicitly instead of relying on the configuration parameter
//Request::enableHttpMethodParameterOverride();
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
if ($isCacheable && $response->isOk()) {
	try {
		$cache->set($response->getContent(), $response->getTtl());
	} catch (\RuntimeException $e) {
	}
}
$response->send();
$kernel->terminate($request, $response);
