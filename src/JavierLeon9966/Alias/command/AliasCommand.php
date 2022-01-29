<?php
namespace JavierLeon9966\Alias\command;
use JavierLeon9966\Alias\Alias;
use pocketmine\command\{Command, CommandSender, PluginIdentifiableCommand};
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\plugin\{Plugin, PluginOwned, PluginOwnedTrait};
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
	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$this->testPermission($sender)){
			return true;
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
		return true;
	}
}