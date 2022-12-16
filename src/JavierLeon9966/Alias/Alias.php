<?php
namespace JavierLeon9966\Alias;
use JavierLeon9966\Alias\command\AliasCommand;
use JavierLeon9966\Alias\config\DatabaseConfig;
use libMarshal\exception\UnmarshalException;
use libMarshal\MarshalTrait;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\plugin\DisablePluginException;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\ConfigLoadException;
use pocketmine\utils\TextFormat;
use poggit\libasynql\{DataConnector, libasynql};
use Webmozart\PathUtil\Path;
class Alias extends PluginBase implements Listener{
    /** @var array<string, array{
     *     "Address"?: list<string>,
     *     "ClientRandomId"?: list<int>,
     *     "DeviceId"?: list<string>,
     *     "SelfSignedId"?: list<string>,
     *     "XUID"?: string
     *  }>
     */
	private array $players = [];
	private DataConnector $database;
	private static ?self $instance = null;
	private Config $config;
	public static function getInstance(): ?self{
		return self::$instance;
	}
	public function onEnable(): void{
		self::$instance = $this;
		try{
			$this->config = Config::unmarshal($this->getConfig()->getAll());
		}catch(UnmarshalException|ConfigLoadException $e){
			$this->getLogger()->error($e->getMessage());
			throw new DisablePluginException;
		}
		if(!class_exists(MarshalTrait::class)){
			$this->getLogger()->error('Virion \'libMarshal\' not found. Please download Alias from Poggit-CI.');
			throw new DisablePluginException;
		}
		if(!class_exists(libasynql::class)){
			$this->getLogger()->error('Virion \'libasynql\' not found. Please download Alias from Poggit-CI.');
			throw new DisablePluginException;
		}
		$databaseConfig = $this->config->database ?? new DatabaseConfig;
		$friendlyConfig = [
			'type' => $databaseConfig->type,
			'sqlite' => [
				'file' => $databaseConfig->sqlite->file
			],
			'mysql' => [
				'host' => $databaseConfig->mysql->host,
				'username' => $databaseConfig->mysql->username,
				'password' => $databaseConfig->mysql->password,
				'schema' => $databaseConfig->mysql->schema,
				'port' => $databaseConfig->mysql->port
			],
			'worker-limit' => $databaseConfig->workerLimit
		];
		$this->database = libasynql::create($this, $friendlyConfig, [
			'sqlite' => Path::join('sqlite', 'stmt.sql'),
			'mysql' => Path::join('mysql', 'stmt.sql')
		]);
		$this->database->executeGeneric('alias.init');
		$this->database->executeSelect('alias.load', [],
			function(array $players): void{
				/** @var list<array{'Username': string, 'Data': string}> $players */
				foreach($players as $player){
					$unserialized = unserialize($player['Data']);
					if(!is_array($unserialized)){
						throw new AssumptionFailedError;
					}
					/** @var array{
					 *     "Address"?: list<string>,
					 *     "ClientRandomId"?: list<int>,
					 *     "DeviceId"?: list<string>,
					 *     "SelfSignedId"?: list<string>,
					 *     "XUID"?: string
					 *  } $unserialized
					 */
					$this->players[$player['Username']] = $unserialized;
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
    /** @return array{
	 *     "Address"?: list<string>,
	 *     "ClientRandomId"?: list<string>,
	 *     "DeviceId"?: list<string>,
	 *     "SelfSignedId"?: list<string>,
	 *     "XUID"?: list<string>
	 *	 }
	 */
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

		$playerInfo = $player->getNetworkSession()->getPlayerInfo() ??
			throw new AssumptionFailedError('This shouldn\'t be null at this stage');

		/** @var array{
		 *     "ClientRandomId"?: int,
		 *     "DeviceId"?: string,
		 *     "SelfSignedId"?: string
		 *  } $extraData
		 */
		$extraData = $playerInfo->getExtraData();

		if(!in_array($address = $player->getNetworkSession()->getIp(), $this->players[$username]['Address'] ?? [], true)){
			$this->players[$username]['Address'][] = $address;
		}
		foreach(['ClientRandomId', 'DeviceId', 'SelfSignedId'] as $data){
			if(isset($extraData[$data]) && !in_array($extraData[$data], $this->players[$username][$data] ?? [], true)){
                /* @phpstan-ignore-next-line Phpstan bug */
				$this->players[$username][$data][] = $extraData[$data];
			}
		}
		if($player->isAuthenticated()){
			$this->players[$username]['XUID'] = $player->getXuid();
		}

		$this->saveDatabase($username);

		foreach(array_keys($this->getAliases($username)) as $data){
			if(in_array($data, $this->config->data, true)){
				if($this->config->alert){
					foreach($this->getServer()->getOnlinePlayers() as $user){
						if($user->hasPermission('alias.alerts')){
							$user->sendMessage(TextFormat::RED."'{$player->getName()}' has been detected using an alternative account");
						}
					}
				}
				if($this->config->mode === 'ban'){
					$player->kick($this->config->ban);
				}
				return;
			}
		}
	}
}
