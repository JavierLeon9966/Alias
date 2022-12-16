<?php
namespace JavierLeon9966\Alias\command;
use JavierLeon9966\Alias\Alias;
use pocketmine\command\{Command, CommandSender};
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\plugin\{PluginOwned, PluginOwnedTrait};
use pocketmine\utils\TextFormat;
class AliasCommand extends Command implements PluginOwned{
	use PluginOwnedTrait;
	public function __construct(Alias $plugin){
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

		if(count($args) == 0){
			throw new InvalidCommandSyntaxException;
		}
		if(!$this->owningPlugin instanceof Alias){
			return;
		}
		$message = TextFormat::GREEN."'$args[0]' possible accounts:";
		$possiblePlayers = $this->owningPlugin->getAliases($args[0]);
		foreach(['Address', 'ClientRandomId', 'DeviceId', 'SelfSignedId', 'XUID'] as $key){
			$message .= "\n$key: ".implode(', ', $possiblePlayers[$key] ?? ['None']);
		}
		$sender->sendMessage($message);
	}
}