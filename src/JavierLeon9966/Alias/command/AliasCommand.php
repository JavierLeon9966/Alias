<?php
namespace JavierLeon9966\Alias\command;
use Generator;
use JavierLeon9966\Alias\Alias;
use pocketmine\command\{Command, CommandSender};
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\player\Player;
use pocketmine\plugin\{Plugin, PluginOwned, PluginOwnedTrait};
use pocketmine\utils\TextFormat;
use JavierLeon9966\Alias\libs\_bc46b7a33061b3b0\SOFe\AwaitGenerator\Await;
use WeakReference;

class AliasCommand extends Command implements PluginOwned{
	use PluginOwnedTrait;
	public function __construct(Plugin $plugin){
		$this->owningPlugin = $plugin;
		parent::__construct(
			'alias',
			'Lists a player\'s possible accounts.',
			'/alias <name: target>'
		);
		$this->setPermission('alias.command.alias');
	}
	public function execute(CommandSender $sender, string $commandLabel, array $args): void{
		if(!$this->testPermission($sender)){
			return;
		}

		if(count($args) === 0){
			throw new InvalidCommandSyntaxException;
		}
		/**
		 * @phpstan-var WeakReference<CommandSender> $weakSender
		 */
		$weakSender = WeakReference::create($sender);
		Await::f2c(function() use ($args, $weakSender): Generator{
			$database = yield from Alias::getDatabase();
			$detected = yield from Await::all([
				'Address' => $database->getPlayersMatchingAddressesFrom($args[0]),
				'ClientRandomId' => $database->getPlayersMatchingClientRandomIdsFrom($args[0]),
				'DeviceId' => $database->getPlayersMatchingDeviceIdsFrom($args[0]),
				'SelfSignedId' => $database->getPlayersMatchingSelfSignedIdsFrom($args[0]),
				'XUID' => $database->getPlayersMatchingXUIDFrom($args[0])
			]);
			$message = '';
			foreach($detected as $type => $usernames){
				if(count($usernames) > 0){
					$message .= "\n$type: " . implode(', ', $usernames);
				}
			}
			if(strlen($message) > 0){
				$message = TextFormat::GREEN."'$args[0]' possible accounts:". $message;
			}else{
				$message = TextFormat::RED . "'$args[0]' has no recorded alternative accounts.";
			}
			$sender = $weakSender->get();
			if($sender !== null && (!$sender instanceof Player || $sender->isConnected())){
				$sender->sendMessage($message);
			}
		});
	}
}