<?php

namespace JavierLeon9966\Alias\database;

use Generator;
use JavierLeon9966\Alias\RawQueries;
use SOFe\AwaitGenerator\Await;

final class SQLiteDatabase implements Database{

	public function __construct(private RawQueries $queries){
		Await::g2c($this->queries->initKnownPlayers());
		Await::g2c($this->queries->initAddress());
		Await::g2c($this->queries->initClientRandomId());
		Await::g2c($this->queries->initDeviceId());
		Await::g2c($this->queries->initXuid());
	}

	/**
	 * @phpstan-return Generator<mixed, 'all'|'once'|'race'|'reject'|'resolve'|array{'resolve'}|Generator<mixed, mixed, mixed, mixed>|null, mixed, void>
	 */
	public function addKnownPlayer(string $username): Generator{
		yield from $this->queries->addKnownPlayer($username);
	}

	/**
	 * @phpstan-return Generator<mixed, 'all'|'once'|'race'|'reject'|'resolve'|array{'resolve'}|Generator<mixed, mixed, mixed, mixed>|null, mixed, void>
	 */
	public function addAddress(string $username, string $address): Generator{
		yield from $this->queries->addAddress($username, $address);
	}

	/**
	 * @phpstan-return Generator<mixed, 'all'|'once'|'race'|'reject'|'resolve'|array{'resolve'}|Generator<mixed, mixed, mixed, mixed>|null, mixed, void>
	 */
	public function addClientRandomId(string $username, int $clientRandomId): Generator{
		yield from $this->queries->addClientRandomId($username, $clientRandomId);
	}

	/**
	 * @phpstan-return Generator<mixed, 'all'|'once'|'race'|'reject'|'resolve'|array{'resolve'}|Generator<mixed, mixed, mixed, mixed>|null, mixed, void>
	 */
	public function addDeviceId(string $username, string $deviceId): Generator{
		yield from $this->queries->addDeviceId($username, $deviceId);
	}


	/**
	 * @phpstan-return Generator<mixed, 'all'|'once'|'race'|'reject'|'resolve'|array{'resolve'}|Generator<mixed, mixed, mixed, mixed>|null, mixed, void>
	 */
	public function addXuid(string $username, string $xuid): Generator{
		yield from $this->queries->addXuid($username, $xuid);
	}

	/**
	 * @phpstan-return Generator<mixed, 'all'|'once'|'race'|'reject'|'resolve'|array{'resolve'}|Generator<mixed, mixed, mixed, mixed>|null, mixed, list<string>>
	 */
	public function getPlayersMatchingAddressesFrom(string $username, ?string $extraAddress = null): Generator{
		/**
		 * @phpstan-var array{Username: string} $rows
		 */
		$rows = yield from $this->queries->getAltAddress($username, $extraAddress);
		return array_column($rows, 'Username');
	}

	/**
	 * @phpstan-return Generator<mixed, 'all'|'once'|'race'|'reject'|'resolve'|array{'resolve'}|Generator<mixed, mixed, mixed, mixed>|null, mixed, list<string>>
	 */
	public function getPlayersMatchingClientRandomIdsFrom(string $username, ?int $extraClientRandomId = null): Generator{
		/**
		 * @phpstan-var array{Username: string} $rows
		 */
		$rows = yield from $this->queries->getAltClientRandomId($username, $extraClientRandomId);
		return array_column($rows, 'Username');
	}


	/**
	 * @phpstan-return Generator<mixed, 'all'|'once'|'race'|'reject'|'resolve'|array{'resolve'}|Generator<mixed, mixed, mixed, mixed>|null, mixed, list<string>>
	 */
	public function getPlayersMatchingDeviceIdsFrom(string $username, ?string $extraDeviceId = null): Generator{
		/**
		 * @phpstan-var array{Username: string} $rows
		 */
		$rows = yield from $this->queries->getAltDeviceId($username, $extraDeviceId);
		return array_column($rows, 'Username');
	}

	/**
	 * @phpstan-return Generator<mixed, 'all'|'once'|'race'|'reject'|'resolve'|array{'resolve'}|Generator<mixed, mixed, mixed, mixed>|null, mixed, list<string>>
	 */
	public function getPlayersMatchingXUIDFrom(string $username, ?string $extraXuid = null): Generator{
		/**
		 * @phpstan-var array{Username: string} $rows
		 */
		$rows = yield from $this->queries->getAltXuid($username, $extraXuid);
		return array_column($rows, 'Username');
	}
}