<?php
namespace JavierLeon9966\Alias;
use JavierLeon9966\Alias\command\AliasCommand;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use poggit\libasynql\{DataConnector, libasynql};
use Symfony\Component\Filesystem\Path;
class Alias extends PluginBase implements Listener{
	private array $players = [];
	private DataConnector $database;
	private static ?self $instance = null;
	public static function getInstance(): ?self{
		return self::$instance;
	}
	public function onEnable(): void{
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
				$this->getLogger()->error("Option ($option) must be of type $expectedType, $type was given in config.yml");
				$this->getServer()->getPluginManager()->disablePlugin($this);
				return;
			}
		}
		if(!class_exists(libasynql::class)){
			$this->getLogger()->error('Virion \'libasynql\' not found. Please download Alias from Poggit-CI.');
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return;
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
			'sqlite' => Path::join('sqlite', 'stmt.sql'),
			'mysql' => Path::join('mysql', 'stmt.sql')
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
		$this->database->executeInsert('alias.register', [
			'username' => $username,
			'data' => serialize($this->players[$username])
		]);
	}
	public function getAliases(string $playerName): array{
		$playerName = strtolower($playerName);
		$matchingPlayers = [];
		$players = $this->players;
		$playerData = $players[$playerName] ?? [];
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
	 * @priority MONITOR
	 */
	public function onPlayerLogin(PlayerLoginEvent $event): void{
		$player = $event->getPlayer();
		$username = strtolower($player->getName());

		$extraData = $player->getNetworkSession()->getPlayerInfo()->getExtraData();

		if(!in_array($address = $player->getNetworkSession()->getIp(), $this->players[$username]['Address'] ?? [], true)){
			$this->players[$username]['Address'][] = $address;
		}
		foreach(['ClientRandomId', 'DeviceId', 'SelfSignedId'] as $data){
			if(isset($extraData[$data]) && !in_array($extraData[$data], $this->players[$username][$data] ?? [], true)){
				$this->players[$username][$data][] = $extraData[$data];
			}
		}
		if($player->isAuthenticated()){
			$this->players[$username]['XUID'] = $player->getXuid();
		}

		$this->saveDatabase($username);

		foreach(array_keys($this->getAliases($username)) as $data){
			if(in_array($data, $this->getConfig()->get('data', []), true)){
				if($this->getConfig()->get('alert', false)){
					foreach($this->getServer()->getOnlinePlayers() as $user){
						if($user->hasPermission('alias.alerts')){
							$user->sendMessage(TextFormat::RED."'{$player->getName()}' has been detected using an alternative account");
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
