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
use pocketmine\entity\Human;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\{PlayerChangeSkinEvent, PlayerJoinEvent};
use pocketmine\event\player\PlayerQuitEvent;
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
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class Main extends PluginBase implements Listener {
	
	/** @var self $instance */
    public $skin = [];
	public static $skins = [];
    public static $instance;

    /** @var Config */
    private Config $cfg;
    private Config $pdata;

    /** @var int*/
    public $json;
	
	public function onEnable(): void{
		self::$instance = $this;
    	$this->getServer()->getPluginManager()->registerEvents($this, $this);
	$this->saveResource("config.yml");

        $this->cfg = new Config($this->getDataFolder() . "config.yml", Config::YAML);
        $this->pdata = new Config($this->getDataFolder() . "data.yml", Config::YAML);
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
        $this->skin[$player->getName()] = $player->getSkin();
        
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
	
	public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args) : bool {
		if($sender instanceof Player){
			if($cmd->getName() == "simpleclothes"){
				$this->MenuForm($sender, TextFormat::YELLOW . "Select Your Clothes:");
				return true;
			}
		} else {
			$sender->sendMessage(TextFormat::RED . "You dont Have Permission to use this Command");
			return false;
		}
        return false;
	}

	public function MenuForm($player) {
		$form = new SimpleForm(function(Player $player, $data = null) {
			$result = $data;
			if(is_null($result)) {
				return true;
			}
			switch($result) {
				case 0;
				$this->WForm($player, "Select your wings");
				break;
				case 1;
				$this->CSForm($player, "Select your skin");
				break;
				case 2;
				$this->openCapesUI($player, "Select your capes");
				break;
				case 3;
				break;
			}
		});
		$form->setTitle("§eClothesMenu");
		$form->setContent("Select your clothes");
		$form->addButton("§eWings\n§rClick to open", 0,"textures/items/broken_elytra.png");
		$form->addButton("§eGensinImpact Skin\n§rClick to open", 0,"textures/ui/dressing_room_skins.png");
		$form->addButton("§eCapes\n§rClick to open", 0,"textures/ui/dressing_room_capes");
        $form->addButton("§eHats\n§rClick to open", 1,"https://i.imgur.com/pgrzKO7.png");
		$form->addButton("Exit", 0,"textures/ui/realms_red_x");
		$form->sendToPlayer($player);
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
		case 2;
		    $this->MenuForm($player);
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
    			return false;
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
    			    $this->PForm($sender, TextFormat::RED . "You dont have Permission to Use This Wing");
    			  }
    			break;
    			case 2:
    			if($sender->hasPermission("phoenix.wing") or $sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)){

    			    $setskin = new setSkin();
    			    $setskin->setSkin($sender, "phoenix");
    			  } else {
    			    $this->Form($sender, TextFormat::RED . "You dont have Permission to Use This Wing");
    			  }
    			break;
    			case 3:
    			if($sender->hasPermission("sunset.wing") or $sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)){

    			    $setskin = new setSkin();
    			    $setskin->setSkin($sender, "sunset");
    			  } else {
    			    $this->Form($sender, TextFormat::RED . "You dont have Permission to Use This Wing");
    			  }
    			break;
    			case 4:
    			if($sender->hasPermission("fallenangel.wing") or $sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)){

    			    $setskin = new setSkin();
    			    $setskin->setSkin($sender, "fallenangel");
    			  } else {
    			    $this->Form($sender, TextFormat::RED . "You dont have Permission to Use This Wing");
			}
			break;
			case 5:
    			if($sender->hasPermission("aquatentacle.wing") or $sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)){

    			    $setskin = new setSkin();
    			    $setskin->setSkin($sender, "aquatentacle");
    			  } else {
    			    $this->Form($sender, TextFormat::RED . "You dont have Permission to Use This Wing");
    			  }
    			break;
			case 6:
    			if($sender->hasPermission("aquadragon.wing") or $sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)){

    			    $setskin = new setSkin();
    			    $setskin->setSkin($sender, "aquadragon");
    			  } else {
    			    $this->Form($sender, TextFormat::RED . "You dont have Permission to Use This Wing");
    			  }
    			break;
			case 7:
    			if($sender->hasPermission("butterfly.wing") or $sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)){

    			    $setskin = new setSkin();
    			    $setskin->setSkin($sender, "butterfly");
    			  } else {
    			    $this->Form($sender, TextFormat::RED . "You dont have Permission to Use This Wing");
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
            return false;
    	});
    	$form->setTitle("Wings");
    	$form->setContent("Select your wing");
    	$form->addButton("§l§eCombo Wings§r\nClick to open");
    	$form->addButton("§bDemon §awing");
    	$form->addButton("§bPhoenix §awing");
    	$form->addButton("§bSunset §awing");
    	$form->addButton("§bFallenAngel §awing");
    	$form->addButton("§bAquaTentacle §awing");
	$form->addButton("§bAquaDragon §awing");
    	$form->addButton("§bButterFly §awing");
    	$form->addButton("Reset Skin");
	
        $form->addButton("Back Menu", 0,"textures/ui/arrow_left");
    	$form->addButton("Exit");
    	$form->sendToPlayer($sender);
    	return $form;
    }
    
    public function PForm($player) {
    $form = new ModalForm(function (Player $sender, ?bool $data) {
    });

    $form->setTitle("Permission Denied");
    $form->setContent("You don't have permission to use this skin.");
    $form->setButton1("OK");    // Tombol untuk melanjutkan
    $form->setButton2("Exit"); // Tombol untuk keluar

    $player->sendForm($form);
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
    			    $this->Form($player, TextFormat::RED . "You dont have Permission to Use This Wing");
    			  }
			break;
			case 1;
			if($player->hasPermission("combopk1.wing") or $player->hasPermission(DefaultPermissions::ROOT_OPERATOR)){

    			    $setskin = new setSkin();
    			    $setskin->setSkin($player, "combopk1");
    			  } else {
    			    $this->Form($player, TextFormat::RED . "You dont have Permission to Use This Wing");
    			  }
			break;
            case 2;
			if($player->hasPermission("combopk2.wing") or $player->hasPermission(DefaultPermissions::ROOT_OPERATOR)){

    			    $setskin = new setSkin();
    			    $setskin->setSkin($player, "combopk2");
    			  } else {
    			    $this->Form($player, TextFormat::RED . "You dont have Permission to Use This Wing");
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
		$form->addButton("§eGolden");
		$form->addButton("§eBlue");
        $form->addButton("§eGolden Blue");
	        $form->addButton("Back Wing", 0,"textures/ui/arrow_left");
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
	
    public function CSForm(Player $p){
      
        $form = new SimpleForm(function (Player $p, int $data = null) {
            $result = $data;
            if($result === null){
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
                $geometryName = $this->getGeometryNameFromJSON($this->getDataFolder() . "skingenshin/{$value}.json");
            if ($geometryName === null) {
                $p->sendMessage(TextFormat::RED . "Geometry data not found for: " . $value);
                return false;
            }
            $p->setSkin(new Skin($p->getSkin()->getSkinId(), $this->createSkins($value), "", $geometryName, $this->getGeometryData($value)));
            
                /**8if(array_key_exists($result,Loader::$skins)){
                   $p->setSkin(new Skin($p->getSkin()->getSkinId(), $this->createSkins(Loader::$skins[$result]), "", $p->getSkin()->getGeometryName(), $this->getGeometryData($geometryName)));*/
                   $p->sendSkin();
                   $p->sendMessage(TextFormat::GREEN."Succesfully Changed a Skin §r§f". " ".$value);
               /** }*/
            }

            return true;
        });
        $skins = 0;
        $form->setTitle("GensinImpactSkin");
        if(self::$skins != []){
            foreach (self::$skins as $values) {
                    $form->addButton($values);
                    $skins++;
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
