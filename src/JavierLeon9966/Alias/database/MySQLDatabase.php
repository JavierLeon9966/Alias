<?php

namespace JavierLeon9966\Alias\database;

use Generator;
use JavierLeon9966\Alias\RawQueries;
use SOFe\AwaitGenerator\Await;
use SOFe\AwaitGenerator\Loading;
use SOFe\AwaitGenerator\Mutex;

final class MySQLDatabase implements Database{
	private Mutex $addressMu, $clientRandomIdMu, $deviceIdMu, $xuidMu;

	public function __construct(private RawQueries $queries){
		$this->addressMu = new Mutex();
		$this->clientRandomIdMu = new Mutex();
		$this->deviceIdMu = new Mutex();
		$this->xuidMu = new Mutex();

		$load = new Loading(fn() => yield from $this->queries->initKnownPlayers());
		Await::g2c($this->addressMu->runClosure(function() use($load): Generator{
			yield from $load->get();
			yield from $this->queries->initAddress();
		}));
		Await::g2c($this->clientRandomIdMu->runClosure(function() use($load): Generator{
			yield from $load->get();
			yield from $this->queries->initClientRandomId();
		}));
		Await::g2c($this->deviceIdMu->runClosure(function() use($load): Generator{
			yield from $load->get();
			yield from $this->queries->initDeviceId();
		}));
		Await::g2c($this->xuidMu->runClosure(function() use($load): Generator{
			yield from $load->get();
			yield from $this->queries->initXuid();
		}));
	}
	/**
	 * @phpstan-return Generator<mixed, 'all'|'once'|'race'|'reject'|'resolve'|array{'resolve'}|Generator<mixed, mixed, mixed, mixed>|null, mixed, void>
	 */
	public function addKnownPlayer(string $username): Generator{
		$load = new Loading(fn() => yield from $this->queries->addKnownPlayer($username));
		Await::g2c($this->addressMu->runClosure(static fn() => yield from $load->get()));
		Await::g2c($this->clientRandomIdMu->runClosure(static fn() => yield from $load->get()));
		Await::g2c($this->deviceIdMu->runClosure(static fn() => yield from $load->get()));
		Await::g2c($this->xuidMu->runClosure(static fn() => yield from $load->get()));
		yield from $load->get();
	}

	/**
	 * @phpstan-return Generator<mixed, 'all'|'once'|'race'|'reject'|'resolve'|array{'resolve'}|Generator<mixed, mixed, mixed, mixed>|null, mixed, void>
	 */
	public function addAddress(string $username, string $address): Generator{
		yield from $this->addressMu->run($this->queries->addAddress($username, $address));
	}

	/**
	 * @phpstan-return Generator<mixed, 'all'|'once'|'race'|'reject'|'resolve'|array{'resolve'}|Generator<mixed, mixed, mixed, mixed>|null, mixed, void>
	 */
	public function addClientRandomId(string $username, int $clientRandomId): Generator{
		yield from $this->clientRandomIdMu->run($this->queries->addClientRandomId($username, $clientRandomId));
	}

	/**
	 * @phpstan-return Generator<mixed, 'all'|'once'|'race'|'reject'|'resolve'|array{'resolve'}|Generator<mixed, mixed, mixed, mixed>|null, mixed, void>
	 */
	public function addDeviceId(string $username, string $deviceId): Generator{
		yield from $this->deviceIdMu->run($this->queries->addDeviceId($username, $deviceId));
	}


	/**
	 * @phpstan-return Generator<mixed, 'all'|'once'|'race'|'reject'|'resolve'|array{'resolve'}|Generator<mixed, mixed, mixed, mixed>|null, mixed, void>
	 */
	public function addXuid(string $username, string $xuid): Generator{
		yield from $this->xuidMu->run($this->queries->addXuid($username, $xuid));
	}

	/**
	 * @phpstan-return Generator<mixed, 'all'|'once'|'race'|'reject'|'resolve'|array{'resolve'}|Generator<mixed, mixed, mixed, mixed>|null, mixed, list<string>>
	 */
	public function getPlayersMatchingAddressesFrom(string $username, ?string $extraAddress = null): Generator{
		/**
		 * @phpstan-var array{Username: string} $rows
		 */
		$rows = yield from $this->addressMu->run($this->queries->getAltAddress($username, $extraAddress));
		return array_column($rows, 'Username');
	}

	/**
	 * @phpstan-return Generator<mixed, 'all'|'once'|'race'|'reject'|'resolve'|array{'resolve'}|Generator<mixed, mixed, mixed, mixed>|null, mixed, list<string>>
	 */
	public function getPlayersMatchingClientRandomIdsFrom(string $username, ?int $extraClientRandomId = null): Generator{
		/**
		 * @phpstan-var array{Username: string} $rows
		 */
		$rows = yield from $this->clientRandomIdMu->run($this->queries->getAltClientRandomId($username, $extraClientRandomId));
		return array_column($rows, 'Username');
	}


	/**
	 * @phpstan-return Generator<mixed, 'all'|'once'|'race'|'reject'|'resolve'|array{'resolve'}|Generator<mixed, mixed, mixed, mixed>|null, mixed, list<string>>
	 */
	public function getPlayersMatchingDeviceIdsFrom(string $username, ?string $extraDeviceId = null): Generator{
		/**
		 * @phpstan-var array{Username: string} $rows
		 */
		$rows = yield from $this->deviceIdMu->run($this->queries->getAltDeviceId($username, $extraDeviceId));
		return array_column($rows, 'Username');
	}

	/**
	 * @phpstan-return Generator<mixed, 'all'|'once'|'race'|'reject'|'resolve'|array{'resolve'}|Generator<mixed, mixed, mixed, mixed>|null, mixed, list<string>>
	 */
	public function getPlayersMatchingXUIDFrom(string $username, ?string $extraXuid = null): Generator{
		/**
		 * @phpstan-var array{Username: string} $rows
		 */
		$rows = yield from $this->xuidMu->run($this->queries->getAltXuid($username, $extraXuid));
		return array_column($rows, 'Username');
	}
}