# Alias
Detects players possible alternatives accounts

## Overview
This plugin stores players `Address`, `ClientRandomId`, `DeviceId`, `SelfSignedId`, and `XUID`.
Which will detect players accurately and hard for hackers to ban evade.

Also it has a command and alert system to staff members.

## Configuration
In the configuration you can modify the `mode`, `data` and `alert`.

Here's a example of the default configuration:
```yaml
alert: false #Everyone that has alias.alerts permission
ban: 'You are banned' #Ban message
mode: none #Options: none, ban
data: ['Address', 'ClientRandomId', 'DeviceId', 'SelfSignedId', 'XUID'] #Check for matching data in which will alert the staff members or ban the player
# Data that can be saved from a detected player, usually you would want to save the data that can't be spoofed, so it doesn't ban
# innocent players, but you may also want to save legit players that attempt to ban evade.
save: ['Address', 'XUID']
```

## Command
`/alias <name: target>`

Only users with `alias.command.alias` permission can execute this command.

Returns a list of possible players matching `IP`, `ClientRandomId`, `DeviceId`, `SelfSignedId`, and `XUID`.

## API
You can use this plugin API by the following:
```php
use JavierLeon9966\Alias\Alias;
use SOFe\AwaitGenerator\Await;

Await::f2c(function() use($player){
    $database = yield from Alias::getDatabase();
    $players = yield from $database->getPlayersMatchingAddressesFrom($player->getName());
    //Do something with the players whose adresses match the given player's name
});
```

## Database
In the data folder of this plugin there is a file called `players.sqlite` which there's stored all the players that have logged into the server.
