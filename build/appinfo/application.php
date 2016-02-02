<?php

namespace OCA\OJSXC\AppInfo;

use OCA\OJSXC\Controller\HttpBindController;
use OCA\OJSXC\Db\MessageMapper;
use OCA\OJSXC\Db\StanzaMapper;
use OCA\OJSXC\StanzaHandlers\IQ;
use OCA\OJSXC\StanzaHandlers\Message;
use OCP\AppFramework\App;
use OCA\OJSXC\ILock;
use OCA\OJSXC\DbLock;
use OCA\OJSXC\MemLock;
use OCP\ICache;

class Application extends App {

	private static $config = [];

	public function __construct(array $urlParams=array()){
		parent::__construct('ojsxc', $urlParams);
		$container = $this->getContainer();

		/** @var $config \OCP\IConfig */
		$configManager = $container->query('OCP\IConfig');
		self::$config['polling'] = $configManager->getSystemValue('ojsxc.polling',
			['sleep_time' => 1, 'max_cycles' => 10]);
		self::$config['use_memcache'] = $configManager->getSystemValue('ojsxc.use_memcache',
			['locking' => false]);

		$container->registerService('HttpBindController', function($c){
			return new HttpBindController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('UserId'),
				$c->query('OCP\ISession'),
				$c->query('StanzaMapper'),
				$c->query('IQHandler'),
				$c->query('MessageHandler'),
				$c->query('Host'),
				$this->getLock(),
				file_get_contents("php://input"),
				self::$config['polling']['sleep_time'],
				self::$config['polling']['max_cycles']
			);
		});

		/**
		 * Database Layer
		 */
		$container->registerService('MessageMapper', function($c) {
			return new MessageMapper(
				$c->query('ServerContainer')->getDb(),
				$c->query('Host')
			);
		});

		$container->registerService('StanzaMapper', function($c) {
			return new StanzaMapper(
				$c->query('ServerContainer')->getDb(),
				$c->query('Host')
			);
		});

		/**
		 * XMPP Stanza Handlers
		 */
		$container->registerService('IQHandler', function($c) {
			return new IQ(
				$c->query('UserId'),
				$c->query('Host'),
				$c->query('OCP\IUserManager')
			);
		});

		$container->registerService('MessageHandler', function($c) {
			return new Message(
				$c->query('UserId'),
				$c->query('Host'),
				$c->query('MessageMapper')
			);
		});

		/**
		 * Config values
		 */
		$container->registerService('Host', function($c){
			return $c->query('Request')->getServerHost();
		});

	}

	/**
	 * @return ILock
	 */
	private function getLock() {
		$c = $this->getContainer();
		if (self::$config['use_memcache']['locking'] === true) {
			$cache = $c->getServer()->getMemCacheFactory();

			if ($cache->isAvailable()) {
				$memcache = $cache->create('ojsxc');
				return new MemLock(
					$c->query('UserId'),
					$memcache
				);
			} else {
				$c->getServer()->getLogger()->warning('OJSXC is configured to use memcache as backend for locking, but no memcache is available.');
			}
		}

		// default
		return new DbLock(
			$c->query('UserId'),
			$c->query('OCP\IDb'),
			$c->query('OCP\IConfig')
		);

	}
	
}