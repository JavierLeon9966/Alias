<?php
namespace JavierLeon9966\Alias;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\utils\{TextFormat, Config};
use pocketmine\Player;
use JavierLeon9966\Alias\command\AliasCommand;
class Alias extends PluginBase implements Listener{
	private $players = [];
	private static $instance = null;
	public static function getInstance(): ?self{
		return self::$instance;
	}
	public function onLoad(): void{
		self::$instance = $this;
		$this->saveDefaultConfig();
		$this->players = (new Config("{$this->getDataFolder()}players.json"))->getAll();
	}
	public function onEnable(): void{
		$this->getServer()->getCommandMap()->register('Alias', new AliasCommand($this));
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}
	public function onDisable(): void{
		$database = new Config("{$this->getDataFolder()}players.json");
		$database->setAll($this->players);
		$database->save();
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
					$player->kick((string)@$this->getConfig()->get('ban', 'You are banned'), false);
				}
				return;
			}
		}
	}
}