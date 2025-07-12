<?php

namespace JavierLeon9966\Alias\database;

use Generator;
use JavierLeon9966\Alias\RawQueries;
use SOFe\AwaitGenerator\Await;

final readonly class Database{

	private function __construct(private RawQueries $queries){
	}

	/** @return Generator<mixed, 'all'|'once'|'race'|'reject'|'resolve'|array{'resolve'}|Generator<mixed, mixed, mixed, mixed>|null, mixed, \JavierLeon9966\Alias\database\Database> */
	public static function create(RawQueries $queries): Generator{
		$instance = new self($queries);
		yield from $queries->initTables();
		return $instance;
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
	public function addSelfSignedId(string $username, string $selfSignedId): Generator{
		yield from $this->queries->addSelfSignedId($username, $selfSignedId);
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
		 * @phpstan-var list<array{Username: string}> $rows
		 */
		$rows = yield from $this->queries->getAltAddress($username, $extraAddress);
		return array_column($rows, 'Username');
	}

	/**
	 * @phpstan-return Generator<mixed, 'all'|'once'|'race'|'reject'|'resolve'|array{'resolve'}|Generator<mixed, mixed, mixed, mixed>|null, mixed, list<string>>
	 */
	public function getPlayersMatchingClientRandomIdsFrom(string $username, ?int $extraClientRandomId = null): Generator{
		/**
		 * @phpstan-var list<array{Username: string}> $rows
		 */
		$rows = yield from $this->queries->getAltClientRandomId($username, $extraClientRandomId);
		return array_column($rows, 'Username');
	}


	/**
	 * @phpstan-return Generator<mixed, 'all'|'once'|'race'|'reject'|'resolve'|array{'resolve'}|Generator<mixed, mixed, mixed, mixed>|null, mixed, list<string>>
	 */
	public function getPlayersMatchingDeviceIdsFrom(string $username, ?string $extraDeviceId = null): Generator{
		/**
		 * @phpstan-var list<array{Username: string}> $rows
		 */
		$rows = yield from $this->queries->getAltDeviceId($username, $extraDeviceId);
		return array_column($rows, 'Username');
	}

	/**
	 * @phpstan-return Generator<mixed, 'all'|'once'|'race'|'reject'|'resolve'|array{'resolve'}|Generator<mixed, mixed, mixed, mixed>|null, mixed, list<string>>
	 */
	public function getPlayersMatchingSelfSignedIdsFrom(string $username, ?string $extraSelfSignedId = null): Generator{
		/**
		 * @phpstan-var list<array{Username: string}> $rows
		 */
		$rows = yield from $this->queries->getAltSelfSignedId($username, $extraSelfSignedId);
		return array_column($rows, 'Username');
	}

	/**
	 * @phpstan-return Generator<mixed, 'all'|'once'|'race'|'reject'|'resolve'|array{'resolve'}|Generator<mixed, mixed, mixed, mixed>|null, mixed, list<string>>
	 */
	public function getPlayersMatchingXUIDFrom(string $username, ?string $extraXuid = null): Generator{
		/**
		 * @phpstan-var list<array{Username: string}> $rows
		 */
		$rows = yield from $this->queries->getAltXuid($username, $extraXuid);
		return array_column($rows, 'Username');
	}
}