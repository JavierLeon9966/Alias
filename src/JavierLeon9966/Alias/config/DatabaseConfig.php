<?php

namespace JavierLeon9966\Alias\config;

use libMarshal\attributes\Field;
use libMarshal\MarshalTrait;

class DatabaseConfig
{
	use MarshalTrait;

	#[Field]
	public string $type = 'sqlite';
	#[Field]
	public SQLiteConfig $sqlite;
	#[Field]
	public MySQLConfig $mysql;
	#[Field(name: "worker-limit")]
	public int $workerLimit = 1;
}
