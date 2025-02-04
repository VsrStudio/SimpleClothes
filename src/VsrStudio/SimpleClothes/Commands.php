<?php

declare(strict_types=1);

namespace VsrStudio\SimpleClothes;


use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;
use pocketmine\scheduler\Task;
use pocketmine\scheduler\TaskScheduler;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\utils\Config;
use pocketmine\plugin\{PluginOwned, PluginOwnedTrait};
use pocketmine\command\utils\InvalidCommandSyntaxException;
use VsrStudio\SimpleClothes\Main;
use pocketmine\Server;


class Commands extends Command implements PluginOwned{
    use PluginOwnedTrait;
	
public $plugin;

    public function __construct(Main $plugin){
		parent::__construct("particle", "Particle main command", "/pa [disable/enable]", ["pa"]);
		$this->setPermission("simpleclothes.use");
		
        $this->plugin = $plugin;
	}

    public function execute(CommandSender $player, string $label, array $args) : bool{
        if(!isset($args[0])){ 
            throw new InvalidCommandSyntaxException;
            return false;
       }
       
        if(!($player instanceof Player)){
            $player->sendMessage("§c You can use this command only in-game!");
            return true;
        }
       
       
        switch(strtolower($args[0])){
          case "disable":
            $this->plugin->deco = new Config($this->plugin->getDataFolder()."players/". strtolower($player->getName()) . "/player.yaml", Config::YAML);
            $this->plugin->deco->get("Particle");
            $this->plugin->deco->set("Particle", false);
            $this->plugin->deco->save();

            break;
          case "enable":
            $this->plugin->deco = new Config($this->plugin->getDataFolder()."players/". strtolower($player->getName()) . "/player.yaml", Config::YAML);
            $this->plugin->deco->get("Particle");
            $this->plugin->deco->set("Particle", true);
            $this->plugin->deco->save();
            break;
		  case "set":
          case "list":
            if(($this->plugin->getServer()->isOp($player->getName()) === true)){
              if(!isset($args[1])){
                $player->sendMessage(TF::GREEN."|--------------------[VsrStudioClothes]--------------------|");
                $player->sendMessage(TF::GOLD."To set, use /pa set <number>");
                $player->sendMessage(TF::GREEN."0 - PortalParticle");
                $player->sendMessage(TF::GREEN."1 - FlameParticle");
                $player->sendMessage(TF::GREEN."2 - EntityFlameParticle");
                $player->sendMessage(TF::GREEN."3 - ExplodeParticle");
                $player->sendMessage(TF::GREEN."4 - WaterParticle");
                $player->sendMessage(TF::GREEN."5 - WaterDripParticle");
                $player->sendMessage(TF::GREEN."6 - LavaParticle");
                $player->sendMessage(TF::GREEN."7 - LavaDripParticle");
                $player->sendMessage(TF::GREEN."8 - HeartParticle");
                $player->sendMessage(TF::GREEN."9 - AngryVillagerParticle");
                $player->sendMessage(TF::GREEN."10 - HappyVillagerParticle");
                $player->sendMessage(TF::GREEN."11 - CriticalParticle");
                $player->sendMessage(TF::GREEN."12 - EnchantTableParticle");
                $player->sendMessage(TF::GREEN."13 - InkParticle");
                $player->sendMessage(TF::GREEN."14 - SporeParticle");
                $player->sendMessage(TF::GREEN."15 - SmokeParticle");
                $player->sendMessage(TF::GREEN."16 - SnowballParticle");
                $player->sendMessage(TF::GREEN."17 - RedstoneParticle");
                $player->sendMessage(TF::GREEN."18 - FloatingTextParticle [Nick rainbow!!]");
                break;
              }
              $this->plugin->deco = new Config($this->plugin->getDataFolder()."players/". strtolower($player->getName()) . "/player.yaml", Config::YAML);
              if(in_array($args[1], $this->plugin->types)){
                $this->plugin->deco->get("Type");
                $this->plugin->deco->set("Type", $args[1]);
                $this->plugin->deco->save();
              }else{
                $player->sendMessage("§c Particles not found, use");
              }
            }else{
              $player->sendMessage("§c You need OP permissions to use that!");
            }
        }
        return true;
      }
}
