<?php

namespace JavierLeon9966\Alias\database;

use Generator;

interface Database{

	/**
	 * @phpstan-return Generator<mixed, 'all'|'once'|'race'|'reject'|'resolve'|array{'resolve'}|Generator<mixed, mixed, mixed, mixed>|null, mixed, void>
	 */
	public function addKnownPlayer(string $username): Generator;

	/**
	 * @phpstan-return Generator<mixed, 'all'|'once'|'race'|'reject'|'resolve'|array{'resolve'}|Generator<mixed, mixed, mixed, mixed>|null, mixed, void>
	 */
	public function addAddress(string $username, string $address): Generator;

	/**
	 * @phpstan-return Generator<mixed, 'all'|'once'|'race'|'reject'|'resolve'|array{'resolve'}|Generator<mixed, mixed, mixed, mixed>|null, mixed, void>
	 */
	public function addClientRandomId(string $username, int $clientRandomId): Generator;

	/**
	 * @phpstan-return Generator<mixed, 'all'|'once'|'race'|'reject'|'resolve'|array{'resolve'}|Generator<mixed, mixed, mixed, mixed>|null, mixed, void>
	 */
	public function addDeviceId(string $username, string $deviceId): Generator;


	/**
	 * @phpstan-return Generator<mixed, 'all'|'once'|'race'|'reject'|'resolve'|array{'resolve'}|Generator<mixed, mixed, mixed, mixed>|null, mixed, void>
	 */
	public function addXuid(string $username, string $xuid): Generator;

	/**
	 * @phpstan-return Generator<mixed, 'all'|'once'|'race'|'reject'|'resolve'|array{'resolve'}|Generator<mixed, mixed, mixed, mixed>|null, mixed, list<string>>
	 */
	public function getPlayersMatchingAddressesFrom(string $username, ?string $extraAddress = null): Generator;

	/**
	 * @phpstan-return Generator<mixed, 'all'|'once'|'race'|'reject'|'resolve'|array{'resolve'}|Generator<mixed, mixed, mixed, mixed>|null, mixed, list<string>>
	 */
	public function getPlayersMatchingClientRandomIdsFrom(string $username, ?int $extraClientRandomId = null): Generator;


	/**
	 * @phpstan-return Generator<mixed, 'all'|'once'|'race'|'reject'|'resolve'|array{'resolve'}|Generator<mixed, mixed, mixed, mixed>|null, mixed, list<string>>
	 */
	public function getPlayersMatchingDeviceIdsFrom(string $username, ?string $extraDeviceId = null): Generator;

	/**
	 * @phpstan-return Generator<mixed, 'all'|'once'|'race'|'reject'|'resolve'|array{'resolve'}|Generator<mixed, mixed, mixed, mixed>|null, mixed, list<string>>
	 */
	public function getPlayersMatchingXUIDFrom(string $username, ?string $extraXuid = null): Generator;
}