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
use pocketmine\event\EventPriority;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\RequestChunkRadiusPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\PacketHandlingException;
use pocketmine\player\Player;
use pocketmine\plugin\DisablePluginException;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\Binary;
use pocketmine\utils\ConfigLoadException;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;
use poggit\libasynql\ConfigException;
use poggit\libasynql\DataConnector;
use poggit\libasynql\ExtensionMissingException;
use poggit\libasynql\libasynql;
use poggit\libasynql\SqlDialect;
use poggit\libasynql\SqlError;
use Ramsey\Uuid\Uuid;
use SOFe\AwaitGenerator\Await;
use SOFe\AwaitGenerator\Channel;
use SOFe\AwaitGenerator\Loading;
use SOFe\PmEvent\Events;
use Symfony\Component\Filesystem\Path;
use Throwable;
use WeakReference;

final class Alias extends PluginBase implements Listener{
	use SingletonTrait{
		setInstance as private;
		reset as private;
	}

	private DataConnector $connector;
	/** @var \SOFe\AwaitGenerator\Loading<Database> */
	private Loading $database;
	private Config $config;
	/**
	 * @phpstan-var list<Closure(string $username, array{
	 *     Address: string,
	 *     ClientRandomId?: int,
	 *     DeviceId?: string,
	 *     XUID?: string,
	 *     SelfSignedId?: string
	 * } $data): Generator<mixed, 'all'|'once'|'race'|'reject'|'resolve'|array{'resolve'}|Generator<mixed, mixed, mixed, mixed>|null, mixed, bool>> $checks
	 * @var Closure[] $checks
	 */
	private array $checks = [];
	/**
	 * @phpstan-var list<Closure(string $username, array{
	 *     Address: string,
	 *     ClientRandomId?: int,
	 *     DeviceId?: string,
	 *     XUID?: string,
	 *     SelfSignedId?: string
	 * } $data): Generator<mixed, 'all'|'once'|'race'|'reject'|'resolve'|array{'resolve'}|Generator<mixed, mixed, mixed, mixed>|null, mixed, void>> $saveData
	 * @var Closure[] $saveData
	 */
	private array $saveData = [];

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
		self::$instance = $this;
		try{
			$this->config = Config::unmarshal($this->getConfig()->getAll());
		}catch(GeneralMarshalException|UnmarshalException|ConfigLoadException $e){
			$this->getLogger()->error($e->getMessage());
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
			'worker-limit' => $databaseConfig->type !== 'sqlite' ? $databaseConfig->workerLimit : 1
		];
		try{
			$this->connector = libasynql::create($this, $friendlyConfig, [
				'sqlite' => Path::join('sqlite', 'stmt.sql'),
				'mysql' => Path::join('mysql', 'stmt.sql')
			]);
		}catch(ConfigException|ExtensionMissingException|SqlError $e){
			$this->getLogger()->error($e->getMessage());
			throw new DisablePluginException();
		}
		$queries = new RawQueries($this->connector);
		$this->database = new Loading(function() use($queries): Generator{
			return yield from Database::create($queries);
		});
		$checks = array_fill_keys($this->config->data, true);
		if(isset($checks['Address'])){
			$this->checks[] = function(string $username, array $data): Generator{
				$database = $players = yield from $this->database->get();
				yield from $database->getPlayersMatchingAddressesFrom($username, $data['Address']);
				return count($players) > 0;
			};
		}
		if(isset($checks['ClientRandomId'])){
			$this->checks[] = function(string $username, array $data): Generator{
				$database = $players = yield from $this->database->get();
				yield from $database->getPlayersMatchingClientRandomIdsFrom($username, $data['ClientRandomId'] ?? null);
				return count($players) > 0;
			};
		}
		if(isset($checks['DeviceId'])){
			$this->checks[] = function(string $username, array $data): Generator{
				$database = $players = yield from $this->database->get();
				yield from $database->getPlayersMatchingDeviceIdsFrom($username, $data['DeviceId'] ?? null);
				return count($players) > 0;
			};
		}
		if(isset($checks['SelfSignedId'])){
			$this->checks[] = function(string $username, array $data): Generator{
				$database = $players = yield from $this->database->get();
				yield from $database->getPlayersMatchingSelfSignedIdsFrom($username, $data['SelfSignedId'] ?? null);
				return count($players) > 0;
			};
		}
		if(isset($checks['XUID'])){
			$this->checks[] = function(string $username, array $data): Generator{
				$database = $players = yield from $this->database->get();
				yield from $database->getPlayersMatchingXUIDFrom($username, $data['XUID'] ?? null);
				return count($players) > 0;
			};
		}
		$save = array_fill_keys($this->config->save, true);
		if(isset($save['Address'])){
			$this->saveData[] = function(string $username, array $data){
				$database = yield from $this->database->get();
				yield from $database->addAddress($username, $data['Address']);
			};
		}
		if(isset($save['ClientRandomId'])){
			$this->saveData[] = function(string $username, array $data): Generator{
				if(isset($data['ClientRandomId'])){
					$database = yield from $this->database->get();
					yield from $database->addClientRandomId($username, $data['ClientRandomId']);
				}
			};
		}
		if(isset($save['DeviceId'])){
			$this->saveData[] = function(string $username, array $data): Generator{
				if(isset($data['DeviceId'])){
					$database = yield from $this->database->get();
					yield from $database->addDeviceId($username, $data['DeviceId']);
				}
			};
		}
		if(isset($save['SelfSignedId'])){
			$this->saveData[] = function(string $username, array $data): Generator{
				if(isset($data['SelfSignedId'])){
					$database = yield from $this->database->get();
					yield from $database->addSelfSignedId($username, $data['SelfSignedId']);
				}
			};
		}
		if(isset($save['XUID'])){
			$this->saveData[] = function(string $username, array $data): Generator{
				if(isset($data['XUID'])){
					$database = yield from $this->database->get();
					yield from $database->addXuid($username, $data['XUID']);
				}
			};
		}
		//TODO: Add data encryption
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

			/** @phpstan-param array{
			 *     "Address": list<string>,
			 *     "ClientRandomId"?: list<mixed[]|int|float|string|bool|null>,
			 *     "DeviceId"?: list<mixed[]|int|float|string|bool|null>,
			 *     "SelfSignedId"?: list<mixed[]|int|float|string|bool|null>,
			 *     "XUID"?: string
			 *  } $data
			 */
			$savePlayer = function(array $data, string $username): Generator{
				$gens = [];
				$database = yield from $this->database->get();
				foreach($data['Address'] as $address){
					$gens[] = $database->addAddress($username, $address);
				}
				foreach($data['ClientRandomId'] ?? [] as $clientRandomId){
					if(!is_int($clientRandomId)){
						$this->getLogger()->error("Data migration error: Expected an integer ClientRandomId from $username, got: " . (is_scalar($clientRandomId) ? $clientRandomId : get_debug_type($clientRandomId)));
						continue;
					}
					$gens[] = $database->addClientRandomId($username, $clientRandomId);
				}
				foreach($data['DeviceId'] ?? [] as $deviceId){
					if(!is_string($deviceId)){
						$this->getLogger()->error("Data migration error: Expected a string DeviceId from $username, got: " . (is_scalar($deviceId) ? $deviceId : get_debug_type($deviceId)));
						continue;
					}
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
				// SelfSignedId is useless because it is just hash(username + client random id), but it should be verified by the server to prevent spoofing
				if(($xuid = $data['XUID'] ?? null) !== null){
					$gens[] = $database->addXuid($username, $xuid);
				}
				yield from Await::all($gens);
			};
			foreach($rows as ['Username' => $username, 'Data' => $data]){
				/** @phpstan-var array{
				 *     "Address": list<string>,
				 *     "ClientRandomId"?: list<mixed[]|int|float|string|bool|null>,
				 *     "DeviceId"?: list<mixed[]|int|float|string|bool|null>,
				 *     "SelfSignedId"?: list<mixed[]|int|float|string|bool|null>,
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
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}
	protected function onDisable(): void{
		if(isset($this->connector)){
			$this->connector->waitAll();
			$this->connector->close();
		}
	}

	/** @return Generator<mixed, 'all'|'once'|'race'|'reject'|'resolve'|array{'resolve'}|Generator<mixed, mixed, mixed, mixed>|null, mixed, \JavierLeon9966\Alias\database\Database> */
	public function getDatabase(): Generator{
		return yield from $this->database->get();
	}

	/**
	 * @priority MONITOR
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
			catches: [PacketHandlingException::class => static function(PacketHandlingException $e): void{
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
				$database = yield from $this->database->get();
				Await::g2c($database->addAddress($username, $data['Address']));
				if(isset($data['ClientRandomId'])){
					Await::g2c($database->addClientRandomId($username, $data['ClientRandomId']));
				}
				if(isset($data['DeviceId'])){
					Await::g2c($database->addDeviceId($username, $data['DeviceId']));
				}
				if(isset($data['XUID'])){
					Await::g2c($database->addXuid($username, $data['XUID']));
				}
				return;
			}

			if($this->config->alert){
				foreach($this->getServer()->getOnlinePlayers() as $user){
					if($user->hasPermission('alias.alerts')){
						$user->sendMessage(TextFormat::RED."'$username' has been detected using an alternative account");
					}
				}
			}
			if($this->config->mode === 'ban'){
				$weakPlayer->get()?->kick($this->config->ban);
			}

			foreach($this->saveData as $saveDatum){
				Await::g2c($saveDatum($username, $data));
			}
		});
	}

	/**
	 * @phpstan-param Channel<bool> $holdingChan
	 * @phpstan-return Generator<mixed, 'all'|'once'|'race'|'reject'|'resolve'|array{'resolve'}|Generator<mixed, mixed, mixed, mixed>|null, mixed, void>
	 */
	private function holdLoggedPlayer(Player $player, Channel $holdingChan): Generator{
		$chunkTraverser = Events::watch(
			$this,
			[DataPacketReceiveEvent::class],
			RequestChunkRadiusPacket::NETWORK_ID . "\0" . spl_object_hash($player),
			static fn(DataPacketReceiveEvent $event) => $event->getPacket()->pid() . "\0" . spl_object_hash($event->getOrigin()->getPlayer() ?? throw new AssumptionFailedError('Player should exist at this point'))
		);
        $quitTraverser = Events::watch(
            $this,
            [PlayerQuitEvent::class],
            spl_object_hash($player),
            static fn(PlayerQuitEvent $event) => spl_object_hash($event->getPlayer())
        );
		unset($player);

		try{
			[, $event] = yield from Await::safeRace([$chunkTraverser->next($_), $quitTraverser->next($_)]);
            if(!$event instanceof DataPacketReceiveEvent){
                yield from $holdingChan->receive();
                return;
            }
			$packet = $event->getPacket();
			$session = $event->getOrigin();
			$event->cancel();
		}catch(Throwable $e){
            throw new AssumptionFailedError('This should never happen', 0, $e);
        }finally{
			yield from $quitTraverser->interrupt();
		}

		try{
			[$which,] = yield from Await::safeRace([$holdingChan->receive(), $chunkTraverser->next($_)]);
			if($which === 1){
				throw new PacketHandlingException('There shouldn\'t be a RequestChunkRadiusPacket after another');
			}
			$serializer = PacketSerializer::encoder();
			$packet->encode($serializer);
			$session->handleDataPacket($packet, $serializer->getBuffer());
		}catch(Throwable $e){
			throw new AssumptionFailedError('This should never happen', 0, $e);
        }
	}

	/**
	 * @phpstan-param array{
	 *     Address: string,
	 *     ClientRandomId?: int,
	 *     DeviceId?: string,
	 *     XUID?: string
	 * } $data
	 * @phpstan-return Generator<mixed, 'all'|'once'|'race'|'reject'|'resolve'|array{'resolve'}|Generator<mixed, mixed, mixed, mixed>|null, mixed, bool>
	 */
	private function isPlayerDetected(string $username, array $data): Generator{
		/**
		 * @phpstan-var Channel<bool> $detectionChan
		 */
		$detectionChan = new Channel();
		Await::f2c(function() use ($data, $username, $detectionChan): Generator{
			yield from Await::all(array_map(static function(Closure $check) use ($data, $detectionChan, $username): Generator{
				if(yield from $check($username, $data)){
					$detectionChan->trySend(true);
				}
			}, $this->checks));
			$detectionChan->trySend(false);
		});
		return yield from $detectionChan->receive();
	}
}