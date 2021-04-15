<?php
namespace JavierLeon9966\Alias;
use JavierLeon9966\Alias\command\AliasCommand;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\utils\TextFormat;
use poggit\libasynql\libasynql;
class Alias extends PluginBase implements Listener{
	private $cache = [];
	private $database = null;
	private $players = [];
	private static $instance = null;
	public static function getInstance(): ?self{
		return self::$instance;
	}
	public function onLoad(): void{
		self::$instance = $this;
		$this->saveDefaultConfig();
		foreach([
			'database' => 'array',
			'database.type' => 'string',
			'database.sqlite' => 'array',
			'database.sqlite.file' => 'string',
			'database.mysql' => 'array',
			'database.mysql.host' => 'string',
			'database.mysql.username' => 'string',
			'database.mysql.password' => 'string',
			'database.mysql.schema' => 'string',
			'database.mysql.port' => 'integer',
			'database.worker-limit' => 'integer',
			'alert' => 'boolean',
			'ban' => 'string',
			'mode' => 'string',
			'data' => 'array'
		] as $option => $expectedType){
			if(($type = gettype($this->getConfig()->getNested($option))) != $expectedType){
				throw new \TypeError("Option ($option) must be of type $expectedType, $type was given in config.yml");
			}
		}
	}
	public function onEnable(): void{
		if(!class_exists(libasynql::class)){
			throw new \Error('Virion \'libasynql\' not found. Please download Alias from Poggit-CI.');
		}
		$databaseConfig = $this->getConfig()->get('database', []);
		$friendlyConfig = [
			'type' => $databaseConfig['type'] ?? 'sqlite3',
			'sqlite' => [
				'file' => $databaseConfig['sqlite']['file'] ?? 'players.sqlite'
			],
			'mysql' => [
				'host' => $databaseConfig['mysql']['host'] ?? '127.0.0.1',
				'username' => $databaseConfig['mysql']['username'] ?? 'Alias',
				'password' => $databaseConfig['mysql']['password'] ?? 'mypassword123',
				'schema' => $databaseConfig['mysql']['schema'] ?? 'Alias',
				'port' => $databaseConfig['mysql']['port'] ?? 3306
			],
			'worker-limit' => $databaseConfig['worker-limit'] ?? 1
		];
		$this->database = libasynql::create($this, $friendlyConfig, [
			'sqlite' => 'stmt.sql',
			'mysql' => 'stmt.sql'
		]);
		$this->database->executeGeneric('alias.init');
		$this->database->executeSelect('alias.load', [],
			function(array $players): void{
				foreach($players as $player){
					$this->players[$player['Username']] = unserialize($player['Data']);
				}
			}
		);

		$this->getServer()->getCommandMap()->register('Alias', new AliasCommand($this));
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}
	public function onDisable(): void{
		if(isset($this->database)){
			$this->database->close();
		}
	}
	private function saveDatabase(string $username): void{
		$this->database->executeSelect('alias.search', ['username' => $username],
			function(array $rows) use($username): void{
				$values = [
					'username' => $username,
					'data' => serialize($this->players[$username])
				];
				if(count($rows) > 0) $this->database->executeChange('alias.save', $values);
				else $this->database->executeInsert('alias.register', $values);
			}
		);
	}
	public function getAliases(string $playerName): array{
		$matchingPlayers = [];
		$players = $this->players;
		$playerData = $players[strtolower($playerName)] ?? [];
		unset($players[$playerName]);
		foreach($players as $name => $data){
			foreach(['Address', 'ClientRandomId', 'DeviceId', 'SelfSignedId', 'XUID'] as $key){
				foreach((array)($data[$key] ?? []) as $datum){
					if(in_array($datum, (array)($playerData[$key] ?? []), true)){
						$matchingPlayers[$key][] = $name;
						continue 2;
					}
				}
			}
		}
		return $matchingPlayers;
	}

	/**
	 * @ignoreCancelled true
	 * @priority MONITOR
	 */
	public function onDataPacketReceive(DataPacketReceiveEvent $event): void{
		$player = $event->getPlayer();
		$packet = $event->getPacket();
		if($packet instanceof LoginPacket){
			if(!Player::isValidUserName($packet->username)){
				return;
			}
			$this->cache[strtolower(TextFormat::clean($packet->username))] = [
				'Address' => $player->getAddress(),
				'ClientRandomId' => $packet->clientData['ClientRandomId'],
				'DeviceId' => $packet->clientData['DeviceId'],
				'SelfSignedId' => $packet->clientData['SelfSignedId']
			];
		}
	}

	/**
	 * @ignoreCancelled true
	 * @priority MONITOR
	 */
	public function onPlayerLogin(PlayerLoginEvent $event): void{
		$player = $event->getPlayer();
		$username = $player->getLowerCaseName();
		if(isset($this->cache[$username])){
			foreach(['Address', 'ClientRandomId', 'DeviceId', 'SelfSignedId'] as $datum){
				if(!in_array($this->cache[$username][$datum] ?? null, $this->players[$username][$datum] ?? [], true)){
					$this->players[$username][$datum][] = $this->cache[$username][$datum];
				}
			}
		}
		unset($this->cache[$username]);
		if($player->isAuthenticated()){
			$this->players[$username]['XUID'] = $player->getXuid();
		}
		$this->saveDatabase($username);
		foreach(array_keys($this->getAliases($username)) as $data){
			if(in_array($data, $this->getConfig()->get('data', []), true)){
				if($this->getConfig()->get('alert', false)){
					foreach($this->getServer()->getOnlinePlayers() as $user){
						if($user->hasPermission('alias.alerts')){
							$user->sendMessage(TextFormat::YELLOW.'[Alias] '.TextFormat::RED."'{$player->getName()}' has been detected using an alternative account");
						}
					}
				}
				if($this->getConfig()->get('mode', 'none') == 'ban'){
					$player->kick($this->getConfig()->get('ban', 'You are banned'), false);
				}
				return;
			}
		}
	}
}
