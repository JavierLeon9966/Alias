<?php
namespace JavierLeon9966\Alias;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\utils\TextFormat;
use pocketmine\Player;
use JavierLeon9966\Alias\command\AliasCommand;
use poggit\libasynql\libasynql;
class Alias extends PluginBase implements Listener{
	private $players = [];
	private $database = null;
	private static $instance = null;
	public static function getInstance(): ?self{
		return self::$instance;
	}
	public function onLoad(): void{
		self::$instance = $this;
		$this->saveDefaultConfig();
	}
	public function onEnable(): void{
		$databaseConfig = (array)$this->getConfig()->get('database', []);
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
				foreach($players as $data){
					$this->players[$data['Username']] = unserialize($data['Data']);
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
			function(array $data) use($username): void{
				$this->database->executeChange(count($data['Data']) > 0 ? 'alias.save' : 'alias.register', [
					'username' => $username,
					'data' => serialize($this->players[$username])
				]);
			}
		);
	}
	public function getAliases(string $playerName): array{
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
			$username = TextFormat::clean($packet->username);
			if(!is_array($this->players[$username] ?? null)){
				$this->players[$username] = [];
			}
			if(!is_array($this->players[$username]['Address'] ?? null)){
				$this->players[$username]['Address'] = [];
			}
			if(!in_array($player->getAddress(), $this->players[$username]['Address'], true)){
				$this->players[$username]['Address'][] = $player->getAddress();
			}
			foreach(['ClientRandomId', 'DeviceId', 'SelfSignedId'] as $datum){
				if(!is_array($this->players[$username][$datum] ?? null)){
					$this->players[$username][$datum] = [];
				}
				if(!in_array($packet->clientData[$datum], $this->players[$username][$datum], true)){
					$this->players[$username][$datum][] = $packet->clientData[$datum];
				}
			}
		}
		$this->saveDatabase($username);
	}

	/**
	 * @ignoreCancelled true
	 * @priority MONITOR
	 */
	public function onPlayerLogin(PlayerLoginEvent $event): void{
		$player = $event->getPlayer();
		$username = $player->getName();
		if($player->isAuthenticated()){
			$this->players[$username]['XUID'] = $player->getXuid();
		}
		$this->saveDatabase($username);
		foreach(array_keys($this->getAliases($player)) as $data){
			if(in_array($data, (array)$this->getConfig()->get('data', []), true)){
				if($this->getConfig()->get('alert', false)){
					foreach($this->getServer()->getOnlinePlayers() as $user){
						if($user->hasPermission('alias.alerts')){
							$user->sendMessage(TextFormat::YELLOW.'[Alias] '.TextFormat::RED."'{$player->getName()}' has been detected using an alternative account");
						}
					}
				}
				if($this->getConfig()->get('mode', 'none') === 'ban'){
					$player->kick(@"{$this->getConfig()->get('ban', 'You are banned')}", false);
				}
				return;
			}
		}
	}
}
