<?php

namespace JavierLeon9966\Alias\config;

use libMarshal\attributes\Field;
use libMarshal\MarshalTrait;

class DatabaseConfig
{
	use MarshalTrait;

	public function __construct(
		#[Field]
		public string $type = 'sqlite',
		#[Field]
		public SQLiteConfig $sqlite = new SQLiteConfig(),
		#[Field]
		public MySQLConfig $mysql = new MySQLConfig(),
		#[Field(name: "worker-limit")]
		public int $workerLimit = 1
	){
	}
}
