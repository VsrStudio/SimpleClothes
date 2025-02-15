<?php

/*
*
*__     __        ____  _             _ _       
*\ \   / /__ _ __/ ___|| |_ _   _  __| (_) ___  
* \ \ / / __| '__\___ \| __| | | |/ _` | |/ _ \ 
*  \ V /\__ \ |   ___) | |_| |_| | (_| | | (_) |
*   \_/ |___/_|  |____/ \__|\__,_|\__,_|_|\___/ 
*
* This plugin was created by VsrStudio
* Warning Not To Sell Plugins Or Change Author
*/

declare(strict_types=1);

namespace VsrStudio\SimpleClothes;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\entity\Human;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\{PlayerChangeSkinEvent, PlayerJoinEvent};
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\network\mcpe\JwtUtils;
use pocketmine\network\mcpe\JwtException;
use pocketmine\network\PacketHandlingException;
use pocketmine\network\mcpe\protocol\types\login\ClientData;
use pocketmine\plugin\PluginBase;
use VsrStudio\SimpleClothes\Form\SimpleForm;
use VsrStudio\SimpleClothes\Form\ModalForm;
use VsrStudio\SimpleClothes\Shop\Shop;
use VsrStudio\SimpleClothes\Crate\Crate;

use VsrStudio\SimpleClothes\task\Schelud;
use VsrStudio\SimpleClothes\Commands;
use muqsit\invmenu\InvMenuHandler;
use onebone\economyapi\EconomyAPI;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\block\VanillaBlocks;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\math\Vector3;
use pocketmine\level\Level;
use pocketmine\scheduler\Task;
use pocketmine\scheduler\TaskScheduler;
//Particles
use pocketmine\world\particle\EnchantParticle;
use pocketmine\world\particle\EnchantmentTableParticle;
use pocketmine\world\particle\PortalParticle;
use pocketmine\world\particle\FlameParticle;
use pocketmine\world\particle\ExplodeParticle;
use pocketmine\world\particle\EntityFlameParticle;
use pocketmine\world\particle\WaterParticle;
use pocketmine\world\particle\WaterDripParticle;
use pocketmine\world\particle\LavaParticle;
use pocketmine\world\particle\LavaDripParticle;
use pocketmine\world\particle\HeartParticle;
use pocketmine\world\particle\AngryVillagerParticle;
use pocketmine\world\particle\HappyVillagerParticle;
use pocketmine\world\particle\CriticalParticle;
use pocketmine\world\particle\InkParticle;
use pocketmine\world\particle\SporeParticle;
use pocketmine\world\particle\SmokeParticle;
use pocketmine\world\particle\SnowballPoofParticle;
use pocketmine\world\particle\RedstoneParticle;
use pocketmine\world\particle\FloatingTextParticle;

class Main extends PluginBase implements Listener {
	
	/** @var self $instance */
    public $skin = [];
	public static $skins = [];
    public static $instance;

    /** @var Config */
    private Config $cfg;
    private Config $pdata;
    private array $cratePositions = [];
    private Config $crateConfig;
    private array $crateParticles = [];

    /** @var int*/
    public $config;
    public $deco;
    public $particle;
    public $colors = array("0","1","2","3","4","5","6","7","8","9","a","b","c","d","e","f");
    public $types = array("0","1","2","3","4","5","6","7","8","9","10","11","12","13","14","15","16","17","18");
    public $unregister = array("particle");
    public $json;
	
	public function onEnable(): void{
		self::$instance = $this;
    	$this->getServer()->getPluginManager()->registerEvents($this, $this);
	$this->saveResource("config.yml");
	@mkdir($this->getDataFolder()."players/");
        if (!InvMenuHandler::isRegistered()) {
        InvMenuHandler::register($this);
        }

        $server = $this->getServer();
        $server->getCommandMap()->register("particle", new Commands($this));
         //Unregister
         foreach($this->unregister as $disable){
          $commandMap = $this->getServer()->getCommandMap();
          $command = $commandMap->getCommand($disable);
          $command->setLabel($disable."_disabled");
          $command->unregister($commandMap);
          }
        
        $this->crateConfig = new Config($this->getDataFolder() . "crates.json", Config::JSON);
        $this->cfg = new Config($this->getDataFolder() . "config.yml", Config::YAML);
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML, array());
        $this->data = new Config($this->getDataFolder() . "data.yml", Config::YAML, array());
        $this->pdata = new Config($this->getDataFolder() . "data.yml", Config::YAML);
        $this->loadCrates();
    	$this->checkSkin();
    	$this->checkRequirement();
    	$this->checkAvailableSkins();
    	$this->getLogger()->info($this->json . " Geometry Skin Confirmed");
	
	if(is_array($this->cfg->get("standard_capes"))) {
            foreach($this->cfg->get("standard_capes") as $cape){
                $this->saveResource("$cape.png");
	    }
	    $this->cfg->set("standard_capes", "done");
            $this->cfg->save();
        }

    }

	public function onJoin(PlayerJoinEvent $event) {
        $player = $event->getPlayer();
        $this->setOnJoin($player);
        $this->skin[$player->getName()] = $player->getSkin();
        
        // var_dump($this->getDataFolder()."players/".$p->getPlayer()->getName());
        if(!is_dir($this->getDataFolder()."players/".strtolower($player->getName()))){

          @mkdir($this->getDataFolder()."players/".strtolower($player->getName()));
          $playerData = fopen($this->getDataFolder()."players/".strtolower($player->getName())."/player.yaml", "w");
          $data = "Particle: true\nType: NULL";
          fwrite($playerData, $data);
          fclose($playerData);
          $this->deco = new Config($this->getDataFolder()."players/". strtolower($player->getName()) . "/player.yaml", Config::YAML);
        }
        
        if(file_exists($this->getDataFolder() . $this->pdata->get($player->getName()) . ".png")) {
            $oldSkin = $player->getSkin();
            $capeData = $this->createCape($this->pdata->get($player->getName()));
            $setCape = new Skin($oldSkin->getSkinId(), $oldSkin->getSkinData(), $capeData, $oldSkin->getGeometryName(), $oldSkin->getGeometryData());

            $player->setSkin($setCape);
            $player->sendSkin();
        } else {
            $this->pdata->remove($player->getName());
            $this->pdata->save();
        }
    }
    
    public function setOnJoin(Player $p) {
      $cfg = $this->data;
      $player = strtolower($p->getName());
      if (!$cfg->exists($player)) {
      $this->addKey($p, 0);
      $cfg->set($player, $this->getKey($p));
      $cfg->save();
    }
  }
    
    public function createCape($capeName) {
        $path = $this->getDataFolder() . "{$capeName}.png";
        $img = @imagecreatefrompng($path);
        $bytes = '';
        $l = (int) @getimagesize($path)[1];

        for($y = 0; $y < $l; $y++) {
            for($x = 0; $x < 64; $x++) {
                $rgba = @imagecolorat($img, $x, $y);
                $a = ((~((int)($rgba >> 24))) << 1) & 0xff;
                $r = ($rgba >> 16) & 0xff;
                $g = ($rgba >> 8) & 0xff;
                $b = $rgba & 0xff;
                $bytes .= chr($r) . chr($g) . chr($b) . chr($a);
            }
        }

        return $bytes;
    }

    public function onChangeSkin(PlayerChangeSkinEvent $event) {
        $player = $event->getPlayer();

        $this->skin[$player->getName()] = $player->getSkin();
    }

    public function getHelp(Player $player) {
    $title = "§9-----[ §bSimpleClothes §9]-----";
    $line1 = "§e- §a/simpleclothes - §gOpen the clothes menu";
    $line2 = "§e- §a/clothes §7- §gAlias command clothes menu";
    $line3 = "§e- §a/sc §7- §gAlias command clothes menu";
    $title1 = "§9-----[ §b More Command §9]-----";
    $line4 = "§e- §a/scmenu §7- §gShow basic menu";
    $line5 = "§e- §a/scshop §7- §gOpen shop menu simpleclothes";
    $line6 = "§e- §a/sccrate §7- §gOpen crate menu simpleclothes";
    $line7 = "§e- §a/scrca §7- §gRca Simpleclothes";
    $line8 = "§e- §a/permission §7- §gPermission Simpleclothes";
    $title2 = "§9-----[ §bCrate §9]-----";
    $line9 = "§e- §a/sccrate help - §gshow help command simpleclothes";
    $line10 = "§e- §a/sccrate setkey - §gsetkey crate simpleclothes";
    $line11 = "§e- §a/sccrate givekey §7- §ggivekey crate simpleclothes";
    $line12 = "§e- §a/sccrate takekey §7- §gtakekey crate simpleclothes";
    $line13 = "§e- §a/sccrate spawncrate §7- §gspawn crate simpleclothes";
    $line14 = "§e- §a/sccrate removecrate §7- §gremove crate simpleclothes";

    // Changed $p to $player
    $player->sendMessage($title);
    $player->sendMessage($line1);
    $player->sendMessage($line2);
    $player->sendMessage($line3);
    $player->sendMessage($title1);
    $player->sendMessage($line4);
    $player->sendMessage($line5);
    $player->sendMessage($line6);
    $player->sendMessage($line7);
    $player->sendMessage($line8);
    $player->sendMessage($title2);
    $player->sendMessage($line9);
    $player->sendMessage($line10);
    $player->sendMessage($line11);
    $player->sendMessage($line12);
    $player->sendMessage($line13);
    $player->sendMessage($line14);
}

	public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args): bool {
    if ($sender instanceof Player) {
        switch ($cmd->getName()) {
            case "simpleclothes":
                $this->MenuForm($sender);
                return true;
            case "scshop":
                $shop = new Shop($this);
                $shop->ShopForm($sender);
                return true;
            case "scmenu":
                $this->BasicMenu($sender);
                return true;
            case "scrca":
                if (!$sender->hasPermission("scrca.command")) {
                    $sender->sendMessage("§cYou don't have permission to use this command.");
                    return true;
                }
                if (empty($args[0]) || empty($args[1])) {
                    $sender->sendMessage("§cUsage: /scrca <player> <command>");
                    return true;
                }
                $player = $this->getServer()->getPlayerByPrefix($args[0]);
                if ($player === null) {
                    $sender->sendMessage("§cPlayer not found.");
                    return true;
                }
                array_shift($args);
                $command = implode(" ", $args);
                $this->getServer()->dispatchCommand($player, $command);
                $sender->sendMessage("§aSuccessfully executed command for §b{$player->getName()}§a: §e/{$command}");
            return true;
            case "permission":
                if (!$sender->hasPermission("permission.command")) {
                    $sender->sendMessage("§cYou don't have permission to use this command.");
                    break;
                }
                if (empty($args[0])) {
                    $sender->sendMessage("§cUsage: /permission <add|remove|list> <player> [permission]");
                    break;
                }
                switch ($args[0]) {
                    case "add":
                        if (empty($args[1]) || empty($args[2])) {
                            $sender->sendMessage("§cUsage: /permission add <player> <permission>");
                            break;
                        }
                        $player = $this->getServer()->getPlayerByPrefix($args[1]);
                        if ($player !== null) {
                            if ($player->hasPermission($args[2])) {
                        $sender->sendMessage("§cPlayer §e{$args[1]} §calready has permission §e{$args[2]}");
                            } else {
                                $attachment = $player->addAttachment($this);
                                $attachment->setPermission($args[2], true);
                                $player->sendMessage("§aYou have been granted permission: §e{$args[2]}");
                        $sender->sendMessage("§aSuccessfully added permission §e{$args[2]} §ato §b{$args[1]}");
                            }
                        } else {
                            $sender->sendMessage("§cPlayer not found.");
                        }
                        break;
                        case "remove":
                            if (empty($args[1]) || empty($args[2])) {
                                $sender->sendMessage("§cUsage: /permission remove <player> <permission>");
                                break;
                            }
                            $player = $this->getServer()->getPlayerByPrefix($args[1]);
                            if ($player !== null) {
                                if (!$player->hasPermission($args[2])) {
                                    $sender->sendMessage("§cPlayer §e{$args[1]} §cdoes not have permission §e{$args[2]}");
                                } else {
                                    $attachment = $player->addAttachment($this);
                                    $attachment->setPermission($args[2], false);
                                    $player->sendMessage("§cYour permission has been revoked: §e{$args[2]}");
                                    $sender->sendMessage("§aSuccessfully removed permission §e{$args[2]} §afrom §b{$args[1]}");
                                }
                            } else {
                                $sender->sendMessage("§cPlayer not found.");
                            }
                            break;
                            case "list":
                                if (empty($args[1])) {
                                    $sender->sendMessage("§cUsage: /permission list <player>");
                                    break;
                                }
                                $player = $this->getServer()->getPlayerByPrefix($args[1]);
                                if ($player !== null) {
                                    $permissions = [];
                                    foreach ($player->getEffectivePermissions() as $perm) {
                                        $permissions[] = $perm->getPermission();
                                    }
                                    if (empty($permissions)) {
                                        $sender->sendMessage("§c{$args[1]} has no permissions.");
                                    } else {
                                        $sender->sendMessage("§aPermissions for §b{$args[1]}:");
                                        foreach ($permissions as $perm) {
                                            $sender->sendMessage("§e- {$perm}");
                                        }
                                    }
                                } else {
                                    $sender->sendMessage("§cPlayer not found.");
            }
            return true;

        default:
            $sender->sendMessage("§cInvalid sub-command. Use /permission <add|remove|list> <player> [permission]");
            return true;
            }
            case "sccrate":
            if(!isset($args[0])){
                $this->openUI($sender);
                break;
            }
            $usage = "§l§cUSAGE§r§c: §7/sccrate ";
            $noperm = "§cKamu tidak memiliki izin untuk menggunakan perintah";
            switch ($args[0]) {
                case "setkey":
                    if (!$sender->hasPermission("setkey.command")) {
                        $sender->sendMessage($noperm);
                        break;
                    }
                    if (empty($args[1]) || empty($args[2])) {
                        $sender->sendMessage($usage . "setkey <player> <amount>");
                        break;
                    }
                    $player = $this->getServer()->getPlayerByPrefix($args[1]);
                    if ($player !== null) {
                        $this->setKey($player, (int)$args[2]);
                        $player->sendMessage("§bYour key set to §a{$args[2]} §bkey");
                    } else { 
                        $sender->sendMessage("§cPlayer not found");
                    }
                    break;
                case "givekey":
                    if (!$sender->hasPermission("givekey.command")) {
                        $sender->sendMessage($noperm);
                        break;
                    }
                    if (empty($args[1]) || empty($args[2])) {
                        $sender->sendMessage($usage . "givekey <player> <amount>");
                        break;
                    }
                    $player = $this->getServer()->getPlayerByPrefix($args[1]);
                    if ($player !== null) {
                        $this->addKey($player, (int)$args[2]);
                        $player->sendMessage("§bYou received §a{$args[2]} §bkey");
                    } else { 
                        $sender->sendMessage("§cPlayer not found");
                    }
                    break;
                case "takekey":
                    if (!$sender->hasPermission("takekey.command")) {$sender->sendMessage($noperm);
                    break;
                    }
                    if (empty($args[1]) || empty($args[2])) {
                        $sender->sendMessage($usage . "takekey <player> <amount>");
                        break;
                    }
                    $player = $this->getServer()->getPlayerByPrefix($args[1]);
                    if ($player !== null) {
                $this->reduceKey($player, (int)$args[2]);
                $player->sendMessage("§eYour key is taken §a{$args[2]} §ekey");
                    } else { 
                        $sender->sendMessage("§cPlayer not found");
                    }
                    break;
                case "spawncrate":
                $block = $sender->getTargetBlock(5);
                if ($block->getTypeId() === VanillaBlocks::ENDER_CHEST()->getTypeId()) {
                    $vectorString = $block->getPosition()->asVector3()->__toString();

                    if (!isset($this->cratePositions[$vectorString])) {
                        $this->cratePositions[$vectorString] = true;
                        $this->saveCrates();
                        $this->showCrateName($sender, $block->getPosition());
                        $sender->sendMessage("✅ Crate berhasil ditambahkan!");
                    } else {
                        $sender->sendMessage("⚠️ Crate sudah ada.");
                    }
                } else {
                    $sender->sendMessage("⛔ Harus melihat ke Ender Chest untuk menambahkan crate!");
                }
                break;
            case "removecrate":
                $block = $sender->getTargetBlock(5);
                $vectorString = $block->getPosition()->asVector3()->__toString();

                if (isset($this->cratePositions[$vectorString])) {
                    unset($this->cratePositions[$vectorString]);
                    $this->saveCrates();
                    $this->removeCrateName($block->getPosition());
                    $sender->sendMessage("✅ Crate berhasil dihapus!");
                } else {
                    $sender->sendMessage("⛔ Tidak ada crate di lokasi ini!");
                }
                break;
                case "help":
                    if ($sender->hasPermission("help.command")) {
                        $this->getHelp($sender);
                    } else { 
                        $sender->sendMessage($noperm);
                    }
                    break;
                    default:
                        $sender->sendMessage("§cInvalid command. Use §7/sccrate help §cfor a list of available commands.");
                        break;
            }
            break;
            default:
                // Handle perintah tidak dikenal
                break;
        }
        return true;
    } else {
        $sender->sendMessage(TextFormat::RED . "You don't have permission to use this command.");
        return false;
    }
}

	public function MenuForm($player) {
		$form = new SimpleForm(function(Player $player, $data = null) {
			$result = $data;
			if(is_null($result)) {
				return true;
			}
			switch($result) {
				case 0:
				$this->WForm($player);
				break;
				case 1:
				$this->CSForm($player);
				break;
				case 2:
				$this->openCapesUI($player);
				break;
				case 3:
				$this->HatsForm($player);
				break;
				case 4:
				$this->PUI($player);
				break;
				case 5:
				break;
			}
		});
		$form->setTitle("§eClothesMenu");
		$form->setContent("Select your clothes");
		$form->addButton("§eWings\n§rClick to open", 0,"textures/items/broken_elytra");
		$form->addButton("§eGensinImpact Skin\n§rClick to open", 0,"textures/ui/dressing_room_skins");
		$form->addButton("§eCapes\n§rClick to open", 0,"textures/ui/dressing_room_capes");
        $form->addButton("§eHats\n§rClick to open", 0,"textures/ui/dressing_room_customization");
        $form->addButton("§eParticle\n§rClick to open", 0,"textures/ui/particles");
		$form->addButton("Exit", 0,"textures/ui/realms_red_x");
		$form->sendToPlayer($player);
	}
	
	public function BasicMenu($player) {
		$form = new SimpleForm(function(Player $player, $data = null) {
			$result = $data;
			if(is_null($result)) {
				return true;
			}
			switch($result) {
				case 0:
				$this->MenuForm($player);
				break;
				case 1:
				$shop = new Shop($this);
                $shop->ShopForm($player);
				break;
				case 2:
				$this->openUI($player);
				break;
				case 3:
				break;
			}
		});
		$form->setTitle("§eMenu");
		$form->setContent("Select your options");
		$form->addButton("§eClothes\n§rClick to open", 0,"textures/ui/icon_steve");
		$form->addButton("§eShop\n§rClick to open", 0,"textures/ui/sidebar_icons/marketplace.png");
		$form->addButton("§eRandom Gift\n§rClick to open", 0,"textures/blocks/chest_front");
		$form->addButton("Exit", 0,"textures/ui/realms_red_x");
		$form->sendToPlayer($player);
	}

	private function showCrateName(Player $player, Vector3 $pos) {
    $name = $player->getName();
    $text = "§6Hai §r$name\n§b§lMystery Crate§r\n§aRandom Gifts§r";

    $particle = new FloatingTextParticle($text);
    $particlePos = $pos->add(0.5, 1.5, 0.5);

    $this->crateParticles[$pos->asVector3()->__toString()] = [$particle, $particlePos];

    $this->crateConfig->setNested("crates." . $pos->asVector3()->__toString(), [
        "x" => $pos->x,
        "y" => $pos->y,
        "z" => $pos->z,
        "world" => $player->getWorld()->getFolderName(),
        "owner" => $name
    ]);
    $this->crateConfig->save();

    foreach ($player->getWorld()->getPlayers() as $p) {
        $p->getWorld()->addParticle($particlePos, $particle);
    }
}

private function removeCrateName(Vector3 $pos) {
    $vectorString = $pos->asVector3()->__toString();

    if (isset($this->crateParticles[$vectorString])) {
        list($particle, $particlePos) = $this->crateParticles[$vectorString];

        foreach ($this->getServer()->getOnlinePlayers() as $player) {
            $player->getWorld()->addParticle($particlePos, new FloatingTextParticle(""));
        }

        unset($this->crateParticles[$vectorString]);
    }
}

	private function loadCrates(): void {
    foreach ($this->crateConfig->get("crates", []) as $vectorString => $data) {
        $world = $this->getServer()->getWorldManager()->getWorldByName($data["world"]);
        if ($world === null) continue;

        $pos = new Vector3($data["x"], $data["y"], $data["z"]);
        $text = "§6Hai §r" . $data["owner"] . "\n§l§bMystery Crate§r\n§aRandom Gifts§r";

        $particle = new FloatingTextParticle($text);
        $particlePos = $pos->add(0.5, 1.5, 0.5);

        $this->crateParticles[$vectorString] = [$particle, $particlePos];

        foreach ($world->getPlayers() as $player) {
            $player->getWorld()->addParticle($particlePos, $particle);
        }
    }
}

    private function saveCrates(): void {
        $this->crateConfig->set("crates", $this->cratePositions);
        $this->crateConfig->save();
    }

    public function onInteract(PlayerInteractEvent $event): void {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $vectorString = $block->getPosition()->asVector3()->__toString();

        if (isset($this->cratePositions[$vectorString])) {
            $event->cancel();
            $this->openUI($player);
        }
    }

    public function onBlockBreak(BlockBreakEvent $event): void {
        $block = $event->getBlock();
        $vectorString = $block->getPosition()->asVector3()->__toString();

        if (isset($this->cratePositions[$vectorString])) {
            $player = $event->getPlayer();
            if (!$player->hasPermission("crate.remove")) {
                $event->cancel();
                $player->sendMessage("⛔ Kamu tidak bisa menghancurkan crate ini!");
            } else {
                unset($this->cratePositions[$vectorString]);
                $this->saveCrates();
                $player->sendMessage("✅ Crate berhasil dihapus!");
            }
        }
    }

    public function onBlockPlace(BlockPlaceEvent $event): void {
        $block = $event->getBlockAgainst();
        $vectorString = $block->getPosition()->asVector3()->__toString();

        if (isset($this->cratePositions[$vectorString])) {
            $event->cancel();
            $event->getPlayer()->sendMessage("⛔ Kamu tidak bisa menempatkan blok di atas crate!");
        }
    }
	
	public function openUI(Player $p) {
    $form = new SimpleForm(function(Player $p, int $data = null){
      if($data === null){
        return true;
      }
      $key = $this->getKey($p);
      switch($data){
        case 0:
          if($key >= 1){
            $this->reduceKey($p, 1);
            $this->openCrate($p);
          }else{ 
            $p->sendMessage("§cYou don't have enough keys");
          }
        break;
        case 1:
            $crate = new Crate();
            $crate->openChest($p);
        break;
        case 2:
          //EXIT MENU
        break;
      }
    });
    $cfg = $this->getConfig()->get("form.menu");
    $replace = str_replace(["{PLAYER}", "{KEY}"], [$p->getName(), $this->getKey($p)], $cfg["content"]);
    $form->setTitle($cfg["title"]);
    $form->setContent($replace);
    $form->addButton($cfg["open.crate"]["button.name"], 0,"textures/blocks/chest_front.png");
    $form->addButton("§eList Reward\n§rClick to open", 0,"textures/ui/gift_square.png");
    $form->addButton($cfg["exit.menu"]["button.name"], 0, "textures/blocks/barrier");
    $form->sendToPlayer($p);
    }

	public function getKey(Player $p) {
    $cfg = $this->data;
    $player = strtolower($p->getName());
    return $cfg->get($player);
    $this->updatePlayerScoreHud($p);
  }

  public function setKey(Player $p, int $amount) {
    $cfg = $this->data;
    $player = strtolower($p->getName());
    $cfg->set($player, $amount);
    $cfg->save();
    $this->updatePlayerScoreHud($p);
  }

  public function addKey(Player $p, int|string $amount) {
    $amount = (int) $amount;
    $cfg = $this->data;
    $player = strtolower($p->getName());
    $cfg->set($player, $cfg->get($player) + $amount);
    $cfg->save();
    $this->updatePlayerScoreHud($p);
  }

  public function reduceKey(Player $p, int $amount) {
    $cfg = $this->data;
    $player = strtolower($p->getName());
    $cfg->set($player, max($cfg->get($player) - $amount, 0));
    $cfg->save();
    $this->updatePlayerScoreHud($p);
  }
  private function updatePlayerScoreHud(Player $player): void {
    if ($this->getServer()->getPluginManager()->getPlugin("ScoreHud") !== null) {
        $listener = new ScoreListener($this);
        $listener->updateKeyTag($player);
    }
}
  
  public function openCrate(Player $p) {
    $cfg = $this->getConfig()->get("reward");
    $rewardIndex = array_rand($cfg);
    $reward = $cfg[$rewardIndex];
    $rewardName = $reward["name"];
    $this->executeReward($p, $reward["commands"]);
    $p->sendMessage($this->getConfig()->get("reward-message") . $rewardName);
  }
    
    private function executeReward(Player $player, array $commands): void {
    foreach ($commands as $command) {
      $command = str_replace("{player}", $player->getName(), $command);
      $this->getServer()->dispatchCommand(new \pocketmine\console\ConsoleCommandSender($this->getServer(), $this->getServer()->getLanguage()), $command);
    }
  }
	
	public function PUI($player){
		$form = new SimpleForm(function($player, $data = null){
			if($data === null){
				return true;
			}
			if($data === 0){
				$this->getServer()->dispatchCommand($player, "pa disable");
				$player->sendMessage("§cParticle Disable.");
				return true;
			}
			
			$level = $player->getWorld();
			$getx = round($player->getPosition()->getX());
			$gety = round($player->getPosition()->getY());
			$getz = round($player->getPosition()->getZ());
			$vect = new Vector3($getx, $gety, $getz, $level);
			
			if($player->hasPermission($this->config->get((string)$data)["Particle"]["Permission"])){
			    $this->getServer()->dispatchCommand($player, "pa set ".$this->config->get((string)$data)["Particle"]["Name"]);
				$this->getServer()->dispatchCommand($player, "pa enable");
				$player->sendMessage("§aParticle Enable.");
			} else {
				$player->sendMessage("§cYou do not have permission.");
			}
		});
		$content = str_replace (["{player}"], [$player->getName()], $this->config->get("Content"));
		$form->setTitle($this->config->get("Title"));
		$form->setContent($content);
		$form->addButton("§l§cDisable Particle\n§r§8Tap To Enter", 0, "textures/blocks/barrier");
		for($i = 1;$i <= 100;$i++){
			if($this->config->exists((string)$i)){
				if($player->hasPermission($this->cfg->get((string)$i)["Particle"]["Permission"])){
					$form->addButton($this->config->get((string)$i)["Button"]["Name"], 0, "textures/ui/check");
				} else {
					$form->addButton($this->config->get((string)$i)["Button"]["Name"], 0, "textures/ui/icon_lock");
				}
			}
		}
		$form->sendToPlayer($player);
		return true;
	}
	
	public function isOP($player, $type = NULL){
      if($type === NULL){
        return true;
      }else{
      
      $level = $player->getWorld();
      $getx = round($player->getPosition()->getX());
      $gety = round($player->getPosition()->getY());
      $getz = round($player->getPosition()->getZ());
      $vect = new Vector3($getx, $gety, $getz, $level);
       
      switch($type){
      case "0":
        $player->getWorld()->addParticle($vect, new PortalParticle()); 
        return true;
      case "1":
        $player->getWorld()->addParticle($vect, new FlameParticle()); 
        return true;
      case "2":
        $player->getWorld()->addParticle($vect, new EntityFlameParticle()); 
        return true;
      case "3":
        $player->getWorld()->addParticle($vect, new ExplodeParticle()); 
        return true;
      case "4":
        $player->getWorld()->addParticle($vect, new WaterParticle()); 
        return true;
      case "5":
        $player->getWorld()->addParticle($vect, new WaterDripParticle()); 
        return true;
      case "6":
        $player->getWorld()->addParticle($vect, new LavaParticle()); 
        return true;
      case "7":
        $player->getWorld()->addParticle($vect, new LavaDripParticle());
        return true;
      case "8":
        $player->getWorld()->addParticle($vect, new HeartParticle()); 
        return true;
      case "9":
        $player->getWorld()->addParticle($vect, new AngryVillagerParticle()); 
        return true;
      case "10":
        $player->getWorld()->addParticle($vect, new HappyVillagerParticle()); 
        return true;
      case "11":
        $player->getWorld()->addParticle($vect, new CriticalParticle());
        return true;
      case "12":
        $player->getWorld()->addParticle($vect, new EnchantmentTableParticle()); 
        return true;
      case "13":
        $player->getWorld()->addParticle($vect, new InkParticle()); 
        return true;
      case "14":
        $player->getWorld()->addParticle($vect, new SporeParticle()); 
        return true;
      case "15":
        $player->getWorld()->addParticle($vect, new SmokeParticle()); 
        return true;
      case "16":
        $player->getWorld()->addParticle($vect, new SnowballPoofParticle()); 
        return true;
      case "17":
        $player->getWorld()->addParticle($vect, new RedstoneParticle()); 
        return true;
      case "18":
        $task = new Schelud($this, $player); 
        $this->getScheduler()->scheduleDelayedTask($task,1*5); // Counted in ticks (1 second = 20 ticks)
        return true;
      }

      }
	}

    public function onMove(PlayerMoveEvent $e){
      $player = $e->getPlayer();
      
    
      
      if($this->getServer()->isOp($player->getName()) === true){
        $this->deco = new Config($this->getDataFolder()."players/". strtolower($player->getName()) . "/player.yaml", Config::YAML);
        $status = $this->deco->get("Particle");
        $type = $this->deco->get("Type");
        if($status === false){
          return true;
        }else{
        $this->isOP($player, $type);
          return true;
        }
      }
      
      $level = $player->getWorld();
      $getx = round($player->getPosition()->getX());
      $gety = round($player->getPosition()->getY());
      $getz = round($player->getPosition()->getZ());
      $vect = new Vector3($getx, $gety, $getz, $level);

      $this->deco = new Config($this->getDataFolder()."players/". strtolower($player->getName()) . "/player.yaml", Config::YAML);
      $status = $this->deco->get("Particle");
      if($status === false){
        return true;
      }
      

      if($player->hasPermission("particle.portal")){
        $player->getWorld()->addParticle($vect, new PortalParticle($player)); 
      
    }
      if($player->hasPermission("particle.flame")){
        $player->getWorld()->addParticle($vect, new FlameParticle($player)); 
      
    }
      if($player->hasPermission("particle.entityflame")){
        $player->getWorld()->addParticle($vect, new EntityFlameParticle($player)); 
      
    } 
      if($player->hasPermission("particle.explode")){
        $player->getWorld()->addParticle($vect, new ExplodeParticle($player)); 
      
    }
      if($player->hasPermission("particle.water")){
        $player->getWorld()->addParticle($vect, new WaterParticle($player)); 
      
    }
      if($player->hasPermission("particle.waterdrip")){
        $player->getWorld()->addParticle($vect, new WaterDripParticle($player)); 
      
    }
      if($player->hasPermission("particle.lava")){
        $player->getWorld()->addParticle($vect, new LavaParticle($player)); 
      
    }
      if($player->hasPermission("particle.lavadrip")){
        $player->getWorld()->addParticle($vect, new LavaDripParticle($player)); 
      
    }
      if($player->hasPermission("particle.heart")){
        $player->getWorld()->addParticle($vect, new HeartParticle(10, $player)); 
      
    }
      if($player->hasPermission("particle.angryvillager")){
        $player->getWorld()->addParticle($vect, new AngryVillagerParticle($player)); 
      
    }
      if($player->hasPermission("particle.happyvillager")){
        $player->getWorld()->addParticle($vect, new HappyVillagerParticle($player)); 
      
    }
      if($player->hasPermission("particle.critical")){
        $player->getWorld()->addParticle($vect, new CriticalParticle($player)); 
      
    }
      if($player->hasPermission("particle.enchanttable")){
        $player->getWorld()->addParticle($vect, new EnchantmentTableParticle($player)); 
      
    }
      if($player->hasPermission("particle.ink")){
        $player->getWorld()->addParticle($vect, new InkParticle($player)); 
      
    }
      if($player->hasPermission("particle.spore")){
        $player->getWorld()->addParticle($vect, new SporeParticle($player)); 
      
    }
      if($player->hasPermission("particle.smoke")){
        $player->getWorld()->addParticle($vect, new SmokeParticle($player)); 
      
    } 
      if($player->hasPermission("particle.snowball")){
        $player->getWorld()->addParticle($vect, new SnowballPoofParticle($player)); 
      
    } 
      if($player->hasPermission("particle.redstone")){
        $player->getWorld()->addParticle($vect, new RedstoneParticle($player)); 
      
    } 
      
      if($player->hasPermission("particle.floatingtxt")){
        $task = new Schelud($this, $player); 
        $this->getScheduler()->scheduleDelayedTask($task,1*5); // Counted in ticks (1 second = 20 ticks)
  } 

    }
	
	public function openCapesUI($player) {
        $form = new SimpleForm(function(Player $player, $data = null) {
            $result = $data;

            if(is_null($result)) {
                return true;
            }

            switch($result) {
                case 0:
                    $oldSkin = $player->getSkin();
                    $setCape = new Skin($oldSkin->getSkinId(), $oldSkin->getSkinData(), "", $oldSkin->getGeometryName(), $oldSkin->getGeometryData());
                    
                    $player->setSkin($setCape);
                    $player->sendSkin();

                    if($this->pdata->get($player->getName()) !== null){
                        $this->pdata->remove($player->getName());
                        $this->pdata->save();
                    }
                    
                    $player->sendMessage($this->cfg->get("skin-resetted"));
                    break;
                case 1:
                    $this->openCapeListUI($player);
                    break;
		case 2:
		    $this->MenuForm($player);
		    break;
            }
        });

        $form->setTitle("CapesMenu");
        $form->setContent("Select your cape");
        $form->addButton("§0Remove your Cape", 0,"textures/ui/icon_trash");
        $form->addButton("§eChoose a Cape", 0,"textures/ui/dressing_room_capes");
	$form->addButton("Back Menu", 0,"textures/ui/arrow_left");
        $form->sendToPlayer($player);
    }
                        
    public function openCapeListUI($player) {
        $form = new SimpleForm(function(Player $player, $data = null) {
            $result = $data;

            if(is_null($result)) {
                return true;
            }

            $capeperm = str_replace(" ", "_", $data);
            $cape = $data;
            $noperms = $this->cfg->get("no-permissions");
            
            if(!file_exists($this->getDataFolder() . $data . ".png")) {
                $player->sendMessage("The choosen Skin is not available!");
            } else {
                if($player->hasPermission("$capeperm.cape")) {
                    $oldSkin = $player->getSkin();
                    $capeData = $this->createCape($cape);
                    $setCape = new Skin($oldSkin->getSkinId(), $oldSkin->getSkinData(), $capeData, $oldSkin->getGeometryName(), $oldSkin->getGeometryData());

                    $player->setSkin($setCape);
                    $player->sendSkin();

                    $msg = $this->cfg->get("cape-on");
                    $msg = str_replace("{name}", $cape, $msg);

                    $player->sendMessage($msg);
                    $this->pdata->set($player->getName(), $cape);
                    $this->pdata->save();
                } else {
                    if($player->hasPermission(DefaultPermissions::ROOT_OPERATOR)){
                        $oldSkin = $player->getSkin();
                        $capeData = $this->createCape($cape);
                        $setCape = new Skin($oldSkin->getSkinId(), $oldSkin->getSkinData(), $capeData, $oldSkin->getGeometryName(), $oldSkin->getGeometryData());

                        $player->setSkin($setCape);
                        $player->sendSkin();

                        $msg = $this->cfg->get("cape-on");
                        $msg = str_replace("{name}", $cape, $msg);

                        $player->sendMessage($msg);
                        $this->pdata->set($player->getName(), $cape);
                        $this->pdata->save();
                    }else{
                        $player->sendMessage($noperms);
                    }
                }
            }
        });

        $form->setTitle("Capes");
        $form->setContent("Select your capes");
        foreach($this->getCapes() as $capes) {
            $form->addButton("$capes", -1, "", $capes);
        }
        $form->sendToPlayer($player);
    }
                        
    public function getCapes() {
    $list = array();

    foreach(array_diff(scandir($this->getDataFolder()), ["..", "."]) as $data) {
        $dat = explode(".", $data);

        // Check if the file has at least two parts after explode
        if(count($dat) > 1 && $dat[1] == "png") {
            array_push($list, $dat[0]);
        }
    }

    return $list;
}
	
	public function dataReceiveEv(DataPacketReceiveEvent $ev)
    {
        $packet = $ev->getPacket();
        $player = $ev->getOrigin()->getPlayer();
        if ($packet instanceof LoginPacket) {
            $data = self::decodeClientData($packet->clientDataJwt);
            $name = $data->ThirdPartyName;
            if ($data->PersonaSkin) {
                if (!file_exists($this->getDataFolder() . "saveskin")) {
                    mkdir($this->getDataFolder() . "saveskin", 0777);
                }
                copy($this->getDataFolder()."steve.png",$this->getDataFolder() . "saveskin/{$name}.png");
                return;
            }
            if ($data->SkinImageHeight == 32) {
            }
            $saveSkin = new saveSkin();
            $saveSkin->saveSkin(base64_decode($data->SkinData, true), $name);
        }
    }
    
    public function onQuit(PlayerQuitEvent $ev)
    {
        $name = $ev->getPlayer()->getName();

        $willDelete = $this->getConfig()->getNested('DeleteSkinAfterQuitting');
        if ($willDelete) {
            if (file_exists($this->getDataFolder() . "saveskin/{$name}.png")) {
                unlink($this->getDataFolder() . "saveskin/{$name}.png");
            }
        }
    }
    
    public function WForm($sender) {
    	$form = new SimpleForm(function (Player $sender, $data = null){
    		if($data === null){
    			return true;
    		}
    		switch($data){
    			case 0:
    			$this->ComboForm($sender);
    			break;
    			case 1:
    			if($sender->hasPermission("demon.wing") or $sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)){

    			    $setskin = new setSkin();
    			    $setskin->setSkin($sender, "demon");
    			  } else {
    			    $this->WForm($sender, TextFormat::RED . "You dont have Permission to Use This Wing");
    			  }
    			break;
    			case 2:
    			if($sender->hasPermission("phoenix.wing") or $sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)){

    			    $setskin = new setSkin();
    			    $setskin->setSkin($sender, "phoenix");
    			  } else {
    			    $this->WForm($sender, TextFormat::RED . "You dont have Permission to Use This Wing");
    			  }
    			break;
    			case 3:
    			if($sender->hasPermission("sunset.wing") or $sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)){

    			    $setskin = new setSkin();
    			    $setskin->setSkin($sender, "sunset");
    			  } else {
    			    $this->WForm($sender, TextFormat::RED . "You dont have Permission to Use This Wing");
    			  }
    			break;
    			case 4:
    			if($sender->hasPermission("fallenangel.wing") or $sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)){

    			    $setskin = new setSkin();
    			    $setskin->setSkin($sender, "fallenangel");
    			  } else {
    			    $this->WForm($sender, TextFormat::RED . "You dont have Permission to Use This Wing");
			}
			break;
			case 5:
    			if($sender->hasPermission("aquatentacle.wing") or $sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)){

    			    $setskin = new setSkin();
    			    $setskin->setSkin($sender, "aquatentacle");
    			  } else {
    			    $this->WForm($sender, TextFormat::RED . "You dont have Permission to Use This Wing");
    			  }
    			break;
			case 6:
    			if($sender->hasPermission("aquadragon.wing") or $sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)){

    			    $setskin = new setSkin();
    			    $setskin->setSkin($sender, "aquadragon");
    			  } else {
    			    $this->WForm($sender, TextFormat::RED . "You dont have Permission to Use This Wing");
    			  }
    			break;
			case 7:
    			if($sender->hasPermission("butterfly.wing") or $sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)){

    			    $setskin = new setSkin();
    			    $setskin->setSkin($sender, "butterfly");
    			  } else {
    			    $this->WForm($sender, TextFormat::RED . "You dont have Permission to Use This Wing");
    			  }
    			break;
			case 8:
    			$this->resetSkin($sender);
    			break;
			case 9;
			$this->MenuForm($sender);
			break;
			case 10;
			break;
    		}
            return true;
    	});
    	$form->setTitle("Wings");
    	$form->setContent("Select your wing");
    	$form->addButton("§l§eCombo Wings§r\nClick to open");
	if($sender->hasPermission("demon.wing") or $sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)){
    	$form->addButton("§l§dDemon §aWing\n§rClick to use", 0, "textures/ui/unLock");
	} else {
    	$form->addButton("§l§dDemon §aWing\n§r§cYou need permission!", 0, "textures/ui/lock");
	}
    	if($sender->hasPermission("phoenix.wing") or $sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)){
    	$form->addButton("§l§dPhoenix §aWing\n§rClick to use", 0, "textures/ui/unLock");
	} else {
    	$form->addButton("§l§dPhoenix §aWing\n§r§cYou need permission!", 0, "textures/ui/lock");
	}
	if($sender->hasPermission("sunset.wing") or $sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)){
    	$form->addButton("§l§dSunset §aWing\n§rClick to use", 0, "textures/ui/unLock");
	} else {
    	$form->addButton("§l§dSunset §aWing\n§r§cYou need permission!", 0, "textures/ui/lock");
	}
    	if($sender->hasPermission("fallenangel.wing") or $sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)){
    	$form->addButton("§l§dFallenAngel §aWing\n§rClick to use", 0, "textures/ui/unLock");
	} else {
    	$form->addButton("§l§dFallenAngel §aWing\n§r§cYou need permission!", 0, "textures/ui/lock");
	}
	if($sender->hasPermission("aquatentacle.wing") or $sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)){
    	$form->addButton("§l§dAquaTentacle §aWing\n§rClick to use", 0, "textures/ui/unLock");
	} else {
    	$form->addButton("§l§dAquaTentacle §aWing\n§r§cYou need permission!", 0, "textures/ui/lock");
	}
    	if($sender->hasPermission("aquadragon.wing") or $sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)){
    	$form->addButton("§l§dAquaDragon §aWing\n§rClick to use", 0, "textures/ui/unLock");
	} else {
    	$form->addButton("§l§dAquaDragon §aWing\n§r§cYou need permission!", 0, "textures/ui/lock");
	}
	if($sender->hasPermission("butterfly.wing") or $sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)){
    	$form->addButton("§l§dButterFly §aWing\n§rClick to use", 0, "textures/ui/unLock");
	} else {
    	$form->addButton("§l§dButterFly §aWing\n§r§cYou need permission!", 0, "textures/ui/lock");
	}
    	$form->addButton("Reset Skin");
	
        $form->addButton("Back Menu", 0,"textures/ui/arrow_left");
    	$form->addButton("Exit");
    	$form->sendToPlayer($sender);
    	return $form;
    }

    public function ComboForm($player) {
		$form = new SimpleForm(function(Player $player, $data = null) {
			$result = $data;
			if(is_null($result)) {
				return true;
			}
			switch($result) {
			case 0;
			if($player->hasPermission("combopk.wing") or $player->hasPermission(DefaultPermissions::ROOT_OPERATOR)){

    			    $setskin = new setSkin();
    			    $setskin->setSkin($player, "combopk");
    			  } else {
    			    $this->WForm($player, TextFormat::RED . "You dont have Permission to Use This Wing");
    			  }
			break;
			case 1;
			if($player->hasPermission("combopk1.wing") or $player->hasPermission(DefaultPermissions::ROOT_OPERATOR)){

    			    $setskin = new setSkin();
    			    $setskin->setSkin($player, "combopk1");
    			  } else {
    			    $this->WForm($player, TextFormat::RED . "You dont have Permission to Use This Wing");
    			  }
			break;
            case 2;
			if($player->hasPermission("combopk2.wing") or $player->hasPermission(DefaultPermissions::ROOT_OPERATOR)){

    			    $setskin = new setSkin();
    			    $setskin->setSkin($player, "combopk2");
    			  } else {
    			    $this->WForm($player, TextFormat::RED . "You dont have Permission to Use This Wing");
    			  }
            break;
			case 3;
			$this->WForm($player);
			break;
			case 4;
			break;
			}
		});
		$form->setTitle("§eComboForm");
		$form->setContent("Select combo wing");
	        if($player->hasPermission("combopk.wing") or $player->hasPermission(DefaultPermissions::ROOT_OPERATOR)){
    	        $form->addButton("§l§eGolden §aWing\n§rClick to use", 0, "textures/ui/unLock");
	        } else {
    	        $form->addButton("§l§eGolden §aWing\n§r§cYou need permission!", 0, "textures/ui/lock");
	        }
	        if($player->hasPermission("combopk1.wing") or $player->hasPermission(DefaultPermissions::ROOT_OPERATOR)){
    	        $form->addButton("§l§bBlue §aWing\n§rClick to use", 0, "textures/ui/unLock");
	        } else {
    	        $form->addButton("§l§bBlue §aWing\n§r§cYou need permission!", 0, "textures/ui/lock");
	        }
	        if($player->hasPermission("combopk2.wing") or $player->hasPermission(DefaultPermissions::ROOT_OPERATOR)){
        	$form->addButton("§l§eGolden§bBlue §aWing\n§rClick to use", 0, "textures/ui/unLock");
        	} else {
          	$form->addButton("§l§eGolden§bBlue §aWing\n§r§cYou need permission!", 0, "textures/ui/lock");
		}
	        $form->addButton("Back Wing", 0,"textures/ui/arrow_left");
		$form->addButton("Exit", 0,"textures/ui/realms_red_x");
		$form->sendToPlayer($player);
    }

    public function HatsForm($player) {
		$form = new SimpleForm(function(Player $player, $data = null) {
			$result = $data;
			if(is_null($result)) {
				return true;
			}
			switch($result) {
			case 0;
			if($player->hasPermission("tv.hats") or $player->hasPermission(DefaultPermissions::ROOT_OPERATOR)){

    			    $setskin = new setSkin();
    			    $setskin->setSkin($player, "tv");
    			  } else {
    			    $this->WForm($player, TextFormat::RED . "You dont have Permission to Use This Wing");
    			  }
			break;
			case 1;
			if($player->hasPermission("frog.hats") or $player->hasPermission(DefaultPermissions::ROOT_OPERATOR)){

    			    $setskin = new setSkin();
    			    $setskin->setSkin($player, "frog");
    			  } else {
    			    $this->WForm($player, TextFormat::RED . "You dont have Permission to Use This Wing");
    			  }
			break;
                        case 2;
			if($player->hasPermission("melon.hats") or $player->hasPermission(DefaultPermissions::ROOT_OPERATOR)){

    			    $setskin = new setSkin();
    			    $setskin->setSkin($player, "melon");
    			  } else {
    			    $this->WForm($player, TextFormat::RED . "You dont have Permission to Use This Wing");
    			  }
                        break;
			case 3;
    			$this->resetSkin($player);
    			break;
			case 4 ;
			$this->MenuForm($player);
			break;
			case 5;
			break;
			}
		});
		$form->setTitle("§eHats");
		$form->setContent("Select your hats");
	        if($player->hasPermission("tv.hats") or $player->hasPermission(DefaultPermissions::ROOT_OPERATOR)){
        	$form->addButton("§l§bTv §aHats\n§rClick to use", 0, "textures/ui/unLock");
	        } else {
        	$form->addButton("§l§bTv §aHats\n§r§cYou need permission!", 0, "textures/ui/lock");
        	}
	        if($player->hasPermission("frog.hats") or $player->hasPermission(DefaultPermissions::ROOT_OPERATOR)){
         	$form->addButton("§l§bFrog §aHats\n§rClick to use", 0, "textures/ui/unLock");
        	} else {
          	$form->addButton("§l§bFrog §aHats\n§r§cYou need permission!", 0, "textures/ui/lock");
        	}
	        if($player->hasPermission("melon.hats") or $player->hasPermission(DefaultPermissions::ROOT_OPERATOR)){
         	$form->addButton("§l§bMelon §aHats\n§rClick to use", 0, "textures/ui/unLock");
        	} else {
         	$form->addButton("§l§bMelon §aHats\n§r§cYou need permission!", 0, "textures/ui/lock");
		}
	        $form->addButton("Reset Skin");
	        $form->addButton("Back Menu", 0,"textures/ui/arrow_left");
		$form->addButton("Exit", 0,"textures/ui/realms_red_x");
		$form->sendToPlayer($player);
    }
    
    public function resetSkin(Player $player){
      $player->sendMessage("Reset To Original Skin Successfully");
      $reset = new resetSkin();
      $reset->setSkin($player);
    }
    
    public function checkSkin(){
      $Available = [];
      if(!file_exists($this->getDataFolder() . "skin")){
        mkdir($this->getDataFolder() . "skin");
      }
      $path = $this->getDataFolder() . "skin/";
      $allskin = scandir($path);
      foreach($allskin as $file){
          array_push($Available, preg_replace("/.json/", "", $file));
      }
      foreach($Available as $value){
        if(!in_array($value . ".png", $allskin)){
          unset($Available[array_search($value, $Available)]);
        }
      }
      $this->json = count($Available);
      $Available = [];
    }

    public function checkAvailableSkins(){
		if(!file_exists($this->getDataFolder()."skingenshin")) {
            mkdir($this->getDataFolder()."skingenshin");
        }

        $list = scandir($this->getDataFolder()."skingenshin");
        $result = [];
        foreach($list as $value) {
            if(strpos($value, ".png")) {
                array_push($result, str_replace('.png', '', $value));
            }
        }
        foreach($result as $value) {
			if(!in_array($value.".json", $list)) {
				unset($result[array_search($value, $result)]);
			}
		}
        sort($result);
        $result[] = "Reset Skin";
        foreach($result as $res){
            $this->getLogger()->info($res);
        }

        self::$skins = $result;
    }


    public function checkSkinGenshin(){
      $Available = [];
      if(!file_exists($this->getDataFolder() . "saveskin")){
        mkdir($this->getDataFolder() . "saveskin");
      }
      $path = $this->getDataFolder() . "saveskin/";
      $allskin = scandir($path);
      foreach($allskin as $file){
          array_push($Available, preg_replace("/.json/", "", $file));
      }
      foreach($Available as $value){
        if(!in_array($value . ".png", $allskin)){
          unset($Available[array_search($value, $Available)]);
        }
      }
      $this->json = count($Available);
      $Available = [];
    }

    public function createSkins($skinName)
    {
        $path = $this->getDataFolder() . "skingenshin/{$skinName}.png";
        $size = getimagesize($path);
        $img = @imagecreatefrompng($path);
        $skinbytes = "";
        for ($y = 0; $y < $size[1]; $y++) {
            for ($x = 0; $x < $size[0]; $x++) {
                $colorat = @imagecolorat($img, $x, $y);
                $a = ((~((int)($colorat >> 24))) << 1) & 0xff;
                $r = ($colorat >> 16) & 0xff;
                $g = ($colorat >> 8) & 0xff;
                $b = $colorat & 0xff;
                $skinbytes .= chr($r) . chr($g) . chr($b) . chr($a);
            }
    }
        @imagedestroy($img);
    return $skinbytes;
    }
	
    public function CSForm(Player $p) {
    $form = new SimpleForm(function (Player $p, int $data = null) {
        $result = $data;
        if ($result === null) {
            return true;
        }

        $value = self::$skins[$result];
        if ($value === "Reset Skin") {
            // Reset to the original skin
            $originalSkin = $this->skin[$p->getName()] ?? $p->getSkin();
            $p->setSkin($originalSkin);
            $p->sendSkin();
            $p->sendMessage(TextFormat::GREEN . "Skin reset to original.");
        } else {
            // Pengecekan permission untuk skin
            if (
                !$p->hasPermission("skin.use." . strtolower($value)) &&
                !$p->hasPermission(DefaultPermissions::ROOT_OPERATOR)
            ) {
                $p->sendMessage(TextFormat::RED . "You don't have permission to use the skin: " . $value);
                return false;
            }

            $geometryName = $this->getGeometryNameFromJSON($this->getDataFolder() . "skingenshin/{$value}.json");
            if ($geometryName === null) {
                $p->sendMessage(TextFormat::RED . "Geometry data not found for: " . $value);
                return false;
            }

            $p->setSkin(new Skin(
                $p->getSkin()->getSkinId(),
                $this->createSkins($value),
                "",
                $geometryName,
                $this->getGeometryData($value)
            ));

            $p->sendSkin();
            $p->sendMessage(TextFormat::GREEN . "Successfully changed to skin: " . $value);
        }

        return true;
    });

    $form->setTitle("Genshin Impact Skin");
    if (self::$skins !== []) {
        foreach (self::$skins as $values) {
            $form->addButton($values);
        }
    }
    $form->sendToPlayer($p);
    return $form;
}

    private function getGeometryNameFromJSON(string $geometryPath): ?string {
    if (!file_exists($geometryPath)) {
        $this->getLogger()->error("Geometry file not found: " . $geometryPath);
        return null;
    }

    $data = file_get_contents($geometryPath);
    if ($data === false) {
        $this->getLogger()->error("Failed to read geometry file: " . $geometryPath);
        return null;
    }

    $json = json_decode($data, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $this->getLogger()->error("Failed to decode JSON: " . json_last_error_msg());
        return null;
    }

    if (isset($json['minecraft:geometry'][0]['description']['identifier'])) {
        return $json['minecraft:geometry'][0]['description']['identifier'];
    }

    $this->getLogger()->error("Geometry identifier not found in JSON.");
    return null;
}

    private function getGeometryData(string $geometryName): ?string {
        $geometryPath = $this->getDataFolder() . "skingenshin/" . $geometryName . ".json";
        if (!file_exists($geometryPath)) {
            return null;
        }

        return file_get_contents($geometryPath);
    }
    
    public function checkRequirement() {
    if (!extension_loaded("gd")) {
        $this->getServer()->getLogger()->info("§6Clothes: Uncomment gd2.dll (remove symbol ';' in ';extension=php_gd2.dll') in bin/php/php.ini to make the plugin working");
        $this->getServer()->getPluginManager()->disablePlugin($this);
        return;
    }

    if (!class_exists(SimpleForm::class)) {
        $this->getServer()->getLogger()->info("§6Clothes: FormAPI class missing, please use .phar from poggit!");
        $this->getServer()->getPluginManager()->disablePlugin($this);
        return;
    }

    if (!file_exists($this->getDataFolder() . "steve.png") || !file_exists($this->getDataFolder() . "steve.json") || !file_exists($this->getDataFolder() . "config.yml")) {
        $resources = $this->getResources();
        if (isset($resources["config.yml"]) && $resources["config.yml"] instanceof \SplFileInfo) {
            $configPath = $resources["config.yml"]->getPathname();
            $basePath = dirname($configPath);
            $this->recurse_copy($basePath, $this->getDataFolder());
        } else {
            $this->getServer()->getLogger()->info("§6Clothes: Something wrong with the resources");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }
    }
}

public function recurse_copy($src, $dst) {
    $dir = opendir($src);
    @mkdir($dst);
    while (($file = readdir($dir)) !== false) {
        if ($file !== '.' && $file !== '..') {
            if (is_dir($src . '/' . $file)) {
                $this->recurse_copy($src . '/' . $file, $dst . '/' . $file);
            } else {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}

    public static function decodeClientData(string $clientDataJwt): ClientData{
        try{
            [, $clientDataClaims, ] = JwtUtils::parse($clientDataJwt);
        }catch(JwtException $e){
            throw PacketHandlingException::wrap($e);
        }

        $mapper = new \JsonMapper;
        $mapper->bEnforceMapType = false;
        $mapper->bExceptionOnMissingData = true;
        $mapper->bExceptionOnUndefinedProperty = true;
        try{
            $clientData = $mapper->map($clientDataClaims, new ClientData);
        }catch(\JsonMapper_Exception $e){
            throw PacketHandlingException::wrap($e);
        }
        return $clientData;
    }
}
