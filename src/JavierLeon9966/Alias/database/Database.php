<?php

namespace JavierLeon9966\Alias\database;

use Generator;
use JavierLeon9966\Alias\RawQueries;
use SOFe\AwaitGenerator\Await;

final readonly class Database{

	private function __construct(private RawQueries $queries){
	}

	/** @return Generator<mixed, Await::RESOLVE|null|Await::RESOLVE_MULTI|Await::REJECT|Await::ONCE|Await::ALL|Await::RACE|Generator, mixed, \JavierLeon9966\Alias\database\Database> */
	public static function create(RawQueries $queries): Generator{
		$instance = new self($queries);
		yield from $queries->initTables();
		return $instance;
	}

	/**
	 * @phpstan-return Generator<mixed, Await::RESOLVE|null|Await::RESOLVE_MULTI|Await::REJECT|Await::ONCE|Await::ALL|Await::RACE|Generator, mixed, void>
	 */
	public function addAddress(string $username, string $address): Generator{
		yield from $this->queries->addAddress($username, hash('sha512', $address));
	}

	/**
	 * @phpstan-return Generator<mixed, Await::RESOLVE|null|Await::RESOLVE_MULTI|Await::REJECT|Await::ONCE|Await::ALL|Await::RACE|Generator, mixed, void>
	 */
	public function addClientRandomId(string $username, string $clientRandomId): Generator{
		yield from $this->queries->addClientRandomId($username, hash('sha512', $clientRandomId));
	}

	/**
	 * @phpstan-return Generator<mixed, Await::RESOLVE|null|Await::RESOLVE_MULTI|Await::REJECT|Await::ONCE|Await::ALL|Await::RACE|Generator, mixed, void>
	 */
	public function addDeviceId(string $username, string $deviceId): Generator{
		yield from $this->queries->addDeviceId($username, hash('sha512', $deviceId));
	}

	/**
	 * @phpstan-return Generator<mixed, Await::RESOLVE|null|Await::RESOLVE_MULTI|Await::REJECT|Await::ONCE|Await::ALL|Await::RACE|Generator, mixed, void>
	 */
	public function addSelfSignedId(string $username, string $selfSignedId): Generator{
		yield from $this->queries->addSelfSignedId($username, hash('sha512', $selfSignedId));
	}


	/**
	 * @phpstan-return Generator<mixed, Await::RESOLVE|null|Await::RESOLVE_MULTI|Await::REJECT|Await::ONCE|Await::ALL|Await::RACE|Generator, mixed, void>
	 */
	public function addXuid(string $username, string $xuid): Generator{
		yield from $this->queries->addXuid($username, hash('sha512', $xuid));
	}

	/**
	 * @phpstan-return Generator<mixed, Await::RESOLVE|null|Await::RESOLVE_MULTI|Await::REJECT|Await::ONCE|Await::ALL|Await::RACE|Generator, mixed, list<string>>
	 */
	public function getPlayersMatchingAddressesFrom(string $username, ?string $extraAddress = null): Generator{
		/**
		 * @phpstan-var list<array{Username: string}> $rows
		 */
		$rows = yield from $this->queries->getAltAddress($username, $extraAddress !== null ? hash('sha512', $extraAddress) : null);
		return array_column($rows, 'Username');
	}

	/**
	 * @phpstan-return Generator<mixed, Await::RESOLVE|null|Await::RESOLVE_MULTI|Await::REJECT|Await::ONCE|Await::ALL|Await::RACE|Generator, mixed, list<string>>
	 */
	public function getPlayersMatchingClientRandomIdsFrom(string $username, ?string $extraClientRandomId = null): Generator{
		/**
		 * @phpstan-var list<array{Username: string}> $rows
		 */
		$rows = yield from $this->queries->getAltClientRandomId($username, $extraClientRandomId !== null ? hash('sha512', $extraClientRandomId) : null);
		return array_column($rows, 'Username');
	}


	/**
	 * @phpstan-return Generator<mixed, Await::RESOLVE|null|Await::RESOLVE_MULTI|Await::REJECT|Await::ONCE|Await::ALL|Await::RACE|Generator, mixed, list<string>>
	 */
	public function getPlayersMatchingDeviceIdsFrom(string $username, ?string $extraDeviceId = null): Generator{
		/**
		 * @phpstan-var list<array{Username: string}> $rows
		 */
		$rows = yield from $this->queries->getAltDeviceId($username, $extraDeviceId !== null ? hash('sha512', $extraDeviceId) : null);
		return array_column($rows, 'Username');
	}

	/**
	 * @phpstan-return Generator<mixed, Await::RESOLVE|null|Await::RESOLVE_MULTI|Await::REJECT|Await::ONCE|Await::ALL|Await::RACE|Generator, mixed, list<string>>
	 */
	public function getPlayersMatchingSelfSignedIdsFrom(string $username, ?string $extraSelfSignedId = null): Generator{
		/**
		 * @phpstan-var list<array{Username: string}> $rows
		 */
		$rows = yield from $this->queries->getAltSelfSignedId($username, $extraSelfSignedId !== null ? hash('sha512', $extraSelfSignedId) : null);
		return array_column($rows, 'Username');
	}

	/**
	 * @phpstan-return Generator<mixed, Await::RESOLVE|null|Await::RESOLVE_MULTI|Await::REJECT|Await::ONCE|Await::ALL|Await::RACE|Generator, mixed, list<string>>
	 */
	public function getPlayersMatchingXUIDFrom(string $username, ?string $extraXuid = null): Generator{
		/**
		 * @phpstan-var list<array{Username: string}> $rows
		 */
		$rows = yield from $this->queries->getAltXuid($username, $extraXuid !== null ? hash('sha512', $extraXuid) : null);
		return array_column($rows, 'Username');
	}
}