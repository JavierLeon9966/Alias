<?php

declare(strict_types=1);

namespace JavierLeon9966\Alias;

use Closure;
use Generator;
use JavierLeon9966\Alias\command\AliasCommand;
use JavierLeon9966\Alias\config\DatabaseConfig;
use JavierLeon9966\Alias\database\Database;
use libMarshal\exception\GeneralMarshalException;
use libMarshal\exception\UnmarshalException;
use libMarshal\MarshalTrait;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\RequestChunkRadiusPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\SetLocalPlayerAsInitializedPacket;
use pocketmine\network\PacketHandlingException;
use pocketmine\player\Player;
use pocketmine\plugin\DisablePluginException;
use pocketmine\plugin\PluginBase;
use pocketmine\plugin\PluginException;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\ConfigLoadException;
use pocketmine\utils\TextFormat;
use JavierLeon9966\Alias\libs\_67be66c4e5bd5c18\poggit\libasynql\ConfigException;
use JavierLeon9966\Alias\libs\_67be66c4e5bd5c18\poggit\libasynql\DataConnector;
use JavierLeon9966\Alias\libs\_67be66c4e5bd5c18\poggit\libasynql\ExtensionMissingException;
use JavierLeon9966\Alias\libs\_67be66c4e5bd5c18\poggit\libasynql\libasynql;
use JavierLeon9966\Alias\libs\_67be66c4e5bd5c18\poggit\libasynql\SqlError;
use Ramsey\Uuid\Uuid;
use JavierLeon9966\Alias\libs\_67be66c4e5bd5c18\SOFe\AwaitGenerator\Await;
use JavierLeon9966\Alias\libs\_67be66c4e5bd5c18\SOFe\AwaitGenerator\Channel;
use JavierLeon9966\Alias\libs\_67be66c4e5bd5c18\SOFe\AwaitGenerator\Loading;
use JavierLeon9966\Alias\libs\_67be66c4e5bd5c18\SOFe\PmEvent\Events;
use stdClass;
use Symfony\Component\Filesystem\Path;
use Throwable;
use WeakReference;

final class Alias extends PluginBase implements Listener{
	private static DataConnector $connector;
	/** @var \SOFe\AwaitGenerator\Loading<Database> */
	private static Loading $database;
	private static Config $config;
	/**
	 * @var array{
	 *     Address?: Closure(string, string): Generator<mixed, Await::RESOLVE|null|Await::RESOLVE_MULTI|Await::REJECT|Await::ONCE|Await::ALL|Await::RACE|Generator, mixed, bool>,
	 *     ClientRandomId?: Closure(string, ?int): Generator<mixed, Await::RESOLVE|null|Await::RESOLVE_MULTI|Await::REJECT|Await::ONCE|Await::ALL|Await::RACE|Generator, mixed, bool>,
	 *     DeviceId?: Closure(string, ?string): Generator<mixed, Await::RESOLVE|null|Await::RESOLVE_MULTI|Await::REJECT|Await::ONCE|Await::ALL|Await::RACE|Generator, mixed, bool>,
	 *      SelfSignedId?: Closure(string, ?string): Generator<mixed, Await::RESOLVE|null|Await::RESOLVE_MULTI|Await::REJECT|Await::ONCE|Await::ALL|Await::RACE|Generator, mixed, bool>,
	 *     XUID?: Closure(string, ?string): Generator<mixed, Await::RESOLVE|null|Await::RESOLVE_MULTI|Await::REJECT|Await::ONCE|Await::ALL|Await::RACE|Generator, mixed, bool>
	 * } $checks
	 */
	private array $checks = [];
	/**
	 * @var array{
	 *     Address?: Closure(string, string): Generator<mixed, Await::RESOLVE|null|Await::RESOLVE_MULTI|Await::REJECT|Await::ONCE|Await::ALL|Await::RACE|Generator, mixed, void>,
	 *     ClientRandomId?: Closure(string, int): Generator<mixed, Await::RESOLVE|null|Await::RESOLVE_MULTI|Await::REJECT|Await::ONCE|Await::ALL|Await::RACE|Generator, mixed, void>,
	 *     DeviceId?: Closure(string, string): Generator<mixed, Await::RESOLVE|null|Await::RESOLVE_MULTI|Await::REJECT|Await::ONCE|Await::ALL|Await::RACE|Generator, mixed, void>,
	 *      SelfSignedId?: Closure(string, string): Generator<mixed, Await::RESOLVE|null|Await::RESOLVE_MULTI|Await::REJECT|Await::ONCE|Await::ALL|Await::RACE|Generator, mixed, void>,
	 *     XUID?: Closure(string, string): Generator<mixed, Await::RESOLVE|null|Await::RESOLVE_MULTI|Await::REJECT|Await::ONCE|Await::ALL|Await::RACE|Generator, mixed, void>
	 * } $saveData
	 */
	private array $saveData = [];

	/** @throws \pocketmine\plugin\DisablePluginException */
	protected function onEnable(): void{
		if(!trait_exists(MarshalTrait::class)){
			$this->getLogger()->error('Virion \'libMarshal\' not found. Please download Alias from Poggit-CI.');
			throw new DisablePluginException;
		}
		if(!class_exists(libasynql::class)){
			$this->getLogger()->error('Virion \'libasynql\' not found. Please download Alias from Poggit-CI.');
			throw new DisablePluginException;
		}
		if(!class_exists(Await::class)){
			$this->getLogger()->error('Virion \'await-generator\' not found. Please download Alias from Poggit-CI.');
			throw new DisablePluginException;
		}
		if(!class_exists(Events::class)){
			$this->getLogger()->error('Virion \'pmevents\' not found. Please download Alias from Poggit-CI.');
			throw new DisablePluginException;
		}
		try{
			self::$config = Config::unmarshal($this->getConfig()->getAll());
		}catch(GeneralMarshalException|UnmarshalException|ConfigLoadException $e){
			$this->getLogger()->error($e->getMessage());
			throw new DisablePluginException;
		}
		$databaseConfig = self::$config->database ?? new DatabaseConfig;
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
			'worker-limit' => $databaseConfig->type !== 'sqlite' ? $databaseConfig->workerLimit : 1
		];
		try{
			self::$connector = libasynql::create($this, $friendlyConfig, [
				'sqlite' => Path::join('sqlite', 'stmt.sql'),
				'mysql' => Path::join('mysql', 'stmt.sql')
			]);
		}catch(ConfigException|ExtensionMissingException|SqlError $e){
			$this->getLogger()->error($e->getMessage());
			throw new DisablePluginException();
		}
		$queries = new RawQueries(self::$connector);
		self::$database = new Loading(function() use($queries): Generator{
			return yield from Database::create($queries);
		});
		$checks = array_fill_keys(self::$config->data, true);
		if(isset($checks['Address'])){
			$this->checks['Address'] = function(string $username, string $address): Generator{
				$database = yield from self::$database->get();
				$players = yield from $database->getPlayersMatchingAddressesFrom($username, $address);
				return count($players) > 0;
			};
		}
		if(isset($checks['ClientRandomId'])){
			$this->checks['ClientRandomId'] = function(string $username, ?int $clientRandomId): Generator{
				$database = yield from self::$database->get();
				$players = yield from $database->getPlayersMatchingClientRandomIdsFrom($username, $clientRandomId !== null ? (string) $clientRandomId : null);
				return count($players) > 0;
			};
		}
		if(isset($checks['DeviceId'])){
			$this->checks['DeviceId'] = function(string $username, ?string $deviceId): Generator{
				$database = yield from self::$database->get();
				$players = yield from $database->getPlayersMatchingDeviceIdsFrom($username, $deviceId);
				return count($players) > 0;
			};
		}
		if(isset($checks['SelfSignedId'])){
			$this->checks['SelfSignedId'] = function(string $username, ?string $selfSignedId): Generator{
				$database = yield from self::$database->get();
				$players = yield from $database->getPlayersMatchingSelfSignedIdsFrom($username, $selfSignedId);
				return count($players) > 0;
			};
		}
		if(isset($checks['XUID'])){
			$this->checks['XUID'] = function(string $username, ?string $xuid): Generator{
				$database = yield from self::$database->get();
				$players = yield from $database->getPlayersMatchingXUIDFrom($username, $xuid);
				return count($players) > 0;
			};
		}
		$save = array_fill_keys(self::$config->save, true);
		if(isset($save['Address'])){
			$this->saveData['Address'] = function(string $username, string $address): Generator{
				$database = yield from self::$database->get();
				yield from $database->addAddress($username, $address);
			};
		}
		if(isset($save['ClientRandomId'])){
			$this->saveData['ClientRandomId'] = function(string $username, int $clientRandomId): Generator{
				$database = yield from self::$database->get();
				yield from $database->addClientRandomId($username, (string) $clientRandomId);
			};
		}
		if(isset($save['DeviceId'])){
			$this->saveData['DeviceId'] = function(string $username, string $deviceId): Generator{
				$database = yield from self::$database->get();
				yield from $database->addDeviceId($username, $deviceId);
			};
		}
		if(isset($save['SelfSignedId'])){
			$this->saveData['SelfSignedId'] = function(string $username, string $selfSignedId): Generator{
				$database = yield from self::$database->get();
				yield from $database->addSelfSignedId($username, $selfSignedId);
			};
		}
		if(isset($save['XUID'])){
			$this->saveData['XUID'] = function(string $username, string $xuid): Generator{
				$database = yield from self::$database->get();
				yield from $database->addXuid($username, $xuid);
			};
		}
		Await::f2c(function() use($queries): Generator{
			try{
				/** @var list<array{'Username': string, 'Data': string}> $rows */
				$rows = yield from $queries->loadOldPlayers();
			}catch(SqlError $e){
				$msg = strtolower($e->getMessage());
				if(str_contains($msg, 'no such table') || preg_match('/^table [^ ]+ doesn\'t exist$/i', $msg) === 1){
					return;
				}else{
					throw new AssumptionFailedError('This should never happen', 0, $e);
				}
			}
			Await::g2c($queries->deleteOldPlayers());
			if(count($rows) > 0){
				$this->getLogger()->notice("Old data has been detected. Migrating data...");
			}
			$gens = [];

			$savePlayer = function(array $data, string $username): Generator{
				$gens = [];
				$database = yield from self::$database->get();
				/** @var list<string> $addresses */
				$addresses = $data['Address'];
				foreach($addresses as $address){
					$gens[] = $database->addAddress($username, $address);
				}
				/** @var list<int> $clientRandomIds */
				$clientRandomIds = $data['ClientRandomId'] ?? [];
				foreach($clientRandomIds as $clientRandomId){
					$gens[] = $database->addClientRandomId($username, (string) $clientRandomId);
				}
				/** @var list<string> $deviceIds */
				$deviceIds = $data['DeviceId'] ?? [];
				foreach($deviceIds as $deviceId){
					$deviceId = str_replace('-', '', $deviceId);
					if(strlen($deviceId) !== 32 && strlen($deviceId) !== 36){
						$this->getLogger()->error("Data migration error: Expected a string with length 32 or 36 DeviceId from $username, got: $deviceId");
						continue;
					}
					$components = [
						substr($deviceId, 0, 8),
						substr($deviceId, 8, 4),
						substr($deviceId, 12, 4),
						substr($deviceId, 16, 4),
						substr($deviceId, 20),
					];

					if (!Uuid::isValid(implode('-', $components))) {
						$this->getLogger()->error("Data migration error: Expected a valid UUID DeviceId from $username, got: $deviceId");
						continue;
					}
					$gens[] = $database->addDeviceId($username, $deviceId);
				}
				/** @var list<string> $selfSignedIds */
				$selfSignedIds = $data['SelfSignedId'] ?? [];
				foreach($selfSignedIds as $selfSignedId){
					$selfSignedId = str_replace('-', '', $selfSignedId);
					if(strlen($selfSignedId) !== 36){
						$this->getLogger()->error("Data migration error: Expected a string with length 36 SelfSignedId from $username, got: $selfSignedId");
						continue;
					}
					$components = [
						substr($selfSignedId, 0, 8),
						substr($selfSignedId, 8, 4),
						substr($selfSignedId, 12, 4),
						substr($selfSignedId, 16, 4),
						substr($selfSignedId, 20),
					];

					if (!Uuid::isValid(implode('-', $components))) {
						$this->getLogger()->error("Data migration error: Expected a valid UUID SelfSignedId from $username, got: $selfSignedId");
						continue;
					}
					$gens[] = $database->addSelfSignedId($username, $selfSignedId);
				}
				/** @var ?string $xuid */
				$xuid = $data['XUID'] ?? null;
				if($xuid !== null){
					$gens[] = $database->addXuid($username, $xuid);
				}
				yield from Await::all($gens);
			};
			foreach($rows as ['Username' => $username, 'Data' => $data]){
				/** @phpstan-var array{
				 *     "Address": list<string>,
				 *     "ClientRandomId"?: list<array<array-key, mixed>|int|float|string|bool|null>,
				 *     "DeviceId"?: list<array<array-key, mixed>|int|float|string|bool|null>,
				 *     "SelfSignedId"?: list<array<array-key, mixed>|int|float|string|bool|null>,
				 *     "XUID"?: string
				 *  } $unSerialized
				 */
				$unSerialized = unserialize($data);
				$gens[] = $savePlayer($unSerialized, $username);
			}
			$results = yield from Await::all($gens);
			if(count($results) > 0){
				$this->getLogger()->notice("Migration process finished.");
			}
		});

		$this->getServer()->getCommandMap()->register('Alias', new AliasCommand($this));
		try{
			$this->getServer()->getPluginManager()->registerEvents($this, $this);
		}catch(PluginException $e){
			throw new AssumptionFailedError('This should never happen', 0, $e);
		}
	}
	protected function onDisable(): void{
		if(isset(self::$connector)){
			self::$connector->waitAll();
			self::$connector->close();
		}
	}

	/** @return Generator<mixed, Await::RESOLVE|null|Await::RESOLVE_MULTI|Await::REJECT|Await::ONCE|Await::ALL|Await::RACE|Generator, mixed, \JavierLeon9966\Alias\database\Database> */
	public static function getDatabase(): Generator{
		return yield from self::$database->get();
	}

	/**
	 * @priority MONITOR
	 *
	 * @throws \pocketmine\network\PacketHandlingException
	 */
	public function onPlayerPreLogin(PlayerPreLoginEvent $event): void{
		$playerInfo = $event->getPlayerInfo();
		/**
		 * @phpstan-var array{ClientRandomId: int, DeviceId: string, SelfSignedId: string} $extraData
		 */
		$extraData = $playerInfo->getExtraData();
		$deviceId = str_replace('-', '', $extraData['DeviceId']);
		if(strlen($deviceId) !== 32 && strlen($deviceId) !== 36){
			throw new PacketHandlingException('Invalid UUID string from DeviceId in ClientData');
		}
		$components = [
			substr($deviceId, 0, 8),
			substr($deviceId, 8, 4),
			substr($deviceId, 12, 4),
			substr($deviceId, 16, 4),
			substr($deviceId, 20),
		];

		if (!Uuid::isValid(implode('-', $components))) {
			throw new PacketHandlingException('Invalid UUID string from DeviceId in ClientData');
		}
		if (!Uuid::isValid($extraData['SelfSignedId'])) {
			throw new PacketHandlingException('Invalid UUID string from SelfSignedId in ClientData');
		}
		//TODO: Verify SelfSignedId with name and ClientRandomId
	}

	/**
	 * @priority MONITOR
	 */
	public function onPlayerLogin(PlayerLoginEvent $event): void{
		$player = $event->getPlayer();
		/**
		 * @phpstan-var Channel<bool> $holdingChan
		 */
		$holdingChan = new Channel();
		Await::g2c(
			$this->holdLoggedPlayer($player, $holdingChan),
			catches: ['' => static function(Throwable $e): void{
				throw $e;
			}]
		);

		$username = $player->getName();
		/** @var array{ClientRandomId?: int, DeviceId?: string, SelfSignedId?: string} $clientData */
		$clientData = $player->getPlayerInfo()->getExtraData();
		/**
		 * @phpstan-var array{
		 *     Address: string,
		 *     ClientRandomId?: int,
		 *     DeviceId?: string,
		 *     SelfSignedId?: string,
		 *     XUID?: string
		 * } $data
		 */
		$data = [];
		$data['Address'] = $player->getNetworkSession()->getIp();
		if(($clientRandomId = $clientData['ClientRandomId'] ?? null) !== null){
			$data['ClientRandomId'] = $clientRandomId;
		}
		if(($deviceId = $clientData['DeviceId'] ?? null) !== null){
			$data['DeviceId'] = $deviceId;
		}
		if(($selfSignedId = $clientData['SelfSignedId'] ?? null) !== null){
			$data['SelfSignedId'] = $selfSignedId;
		}
		if(($xuid = $player->getXuid()) !== ''){
			$data['XUID'] = $xuid;
		}

		/**
		 * @phpstan-var WeakReference<Player> $weakPlayer
		 */
		$weakPlayer = WeakReference::create($player);
		Await::f2c(function() use($data, $holdingChan, $username, $weakPlayer): Generator{
			$detected = yield from $this->isPlayerDetected($username, $data);
			$holdingChan->sendWithoutWait(true);
			if(!$detected){
				$database = yield from self::$database->get();
				Await::g2c($database->addAddress($username, $data['Address']));
				if(isset($data['ClientRandomId'])){
					Await::g2c($database->addClientRandomId($username, (string) $data['ClientRandomId']));
				}
				if(isset($data['DeviceId'])){
					Await::g2c($database->addDeviceId($username, $data['DeviceId']));
				}
				if(isset($data['XUID'])){
					Await::g2c($database->addXuid($username, $data['XUID']));
				}
				return;
			}

			if(self::$config->alert){
				foreach($this->getServer()->getOnlinePlayers() as $user){
					if($user->hasPermission('alias.alerts')){
						$user->sendMessage(TextFormat::RED."'$username' has been detected using an alternative account");
					}
				}
			}
			if(self::$config->mode === 'ban'){
				$weakPlayer->get()?->kick(self::$config->ban);
			}

			foreach($data as $index => $datum){
				if(isset($this->saveData[$index])){
					Await::g2c($this->saveData[$index]($username, $datum));
				}
			}
		});
	}

	/**
	 * @phpstan-param Channel<bool> $holdingChan
	 * @phpstan-return Generator<mixed, Await::RESOLVE|null|Await::RESOLVE_MULTI|Await::REJECT|Await::ONCE|Await::ALL|Await::RACE|Generator, mixed, void>
	 */
	private function holdLoggedPlayer(Player $player, Channel $holdingChan): Generator{
		$initAndQuitTraverser = Events::watch(
			$this,
			[DataPacketReceiveEvent::class, PlayerQuitEvent::class],
			spl_object_hash($player),
			static function(DataPacketReceiveEvent|PlayerQuitEvent $event): string{
				if($event instanceof PlayerQuitEvent){
					return spl_object_hash($event->getPlayer());
				}
				$player = $event->getOrigin()->getPlayer();
				if($event->getPacket() instanceof SetLocalPlayerAsInitializedPacket){
					if($player === null){
						throw new AssumptionFailedError('Player should exist at this point');
					}
					return spl_object_hash($player);
				}
				return spl_object_hash(new stdClass());
			}
		);

		try{
			yield from $initAndQuitTraverser->next($event);
			if(!$event instanceof DataPacketReceiveEvent){
				Await::g2c($holdingChan->receive());
				return;
			}
			$packet = $event->getPacket();
			$session = $event->getOrigin();
			$event->cancel();

			[$which,] = yield from Await::safeRace([$holdingChan->receive(), $initAndQuitTraverser->next($event)]);
			if($which === 1){
				Await::g2c($holdingChan->receive());
				if($event instanceof DataPacketReceiveEvent){
					throw new PacketHandlingException('There shouldn\'t be a SetLocalPlayerAsInitializedPacket after another');
				}
			}
			if(!$session->isConnected()){
				return;
			}
			$serializer = PacketSerializer::encoder();
			$packet->encode($serializer);
			$session->handleDataPacket($packet, $serializer->getBuffer());
		}catch(Throwable $e){
			throw new AssumptionFailedError('This should never happen', 0, $e);
		}finally{
			yield from $initAndQuitTraverser->interrupt();
		}
	}

	/**
	 * @phpstan-param array{
	 *     Address: string,
	 *     ClientRandomId?: int,
	 *     DeviceId?: string,
	 *     XUID?: string
	 * } $data
	 * @phpstan-return Generator<mixed, Await::RESOLVE|null|Await::RESOLVE_MULTI|Await::REJECT|Await::ONCE|Await::ALL|Await::RACE|Generator, mixed, bool>
	 */
	private function isPlayerDetected(string $username, array $data): Generator{
		/**
		 * @phpstan-var Channel<bool> $detectionChan
		 */
		$detectionChan = new Channel();
		Await::f2c(function() use ($data, $username, $detectionChan): Generator{
			$gens = [];
			foreach($this->checks as $index => $check){
				$gens[] = (function() use ($data, $detectionChan, $index, $username, $check): Generator{
					if(yield from $check($username, $data[$index] ?? null)){
						$detectionChan->trySend(true);
					}
				})();
			}
			yield from Await::all($gens);
			$detectionChan->trySend(false);
		});
		return yield from $detectionChan->receive();
	}
}