<?php

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
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class Main extends PluginBase implements Listener {
	
	/** @var self $instance */
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
			if($cmd->getName() == "customwing"){
				$this->MenuForm($sender, TextFormat::YELLOW . "Select Your Wings:");
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
				$this->openCapesUI($player, "Select your hats");
				break;
			}
		});
		$form->setTitle("§eCosmaticMenu");
		$form->setContent("Select your clothes");
		$form->addButton("§eWings");
		$form->addButton("§eGensinImpact Skin");
		$form->addButton("§eCapes");
        $form->addButton("§eHats\n§rClick to open", 0,"https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTQLgpcojdh0iyaMAnuIQDW61XV7ABo7mY1kw&usqp=CAU");
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
                    break;
                case 1:
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
                case 2:
                    $this->openCapeListUI($player);
                    break;
            }
        });

        $form->setTitle($this->cfg->get("UI-Title"));
        $form->setContent($this->cfg->get("UI-Content"));
        $form->addButton("§4Abort", 0);
        $form->addButton("§0Remove your Cape", 1);
        $form->addButton("§eChoose a Cape", 2);
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

        $form->setTitle($this->cfg->get("UI-Title"));
        $form->setContent($this->cfg->get("UI-Content"));
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
    
    public function WForm($sender, string $txt){
    	$form = new SimpleForm(function (Player $sender, $data = null){
    		if($data === null){
    			return false;
    		}
    		switch($data){
    			case 0:
    			if($sender->hasPermission("kagune.wing") or $sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)){

    			    $setskin = new setSkin();
    			    $setskin->setSkin($sender, "kagune");
    			  } else {
    			    $this->Form($sender, TextFormat::RED . "You dont have Permission to Use This Wing");
    			  }
    			break;
    			case 1:
    			if($sender->hasPermission("kakuja.wing") or $sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)){

    			    $setskin = new setSkin();
    			    $setskin->setSkin($sender, "kakuja");
    			  } else {
    			    $this->Form($sender, TextFormat::RED . "You dont have Permission to Use This Wing");
    			  }
    			break;
    			case 2:
    			if($sender->hasPermission("mercy.wing") or $sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)){

    			    $setskin = new setSkin();
    			    $setskin->setSkin($sender, "mercy");
    			  } else {
    			    $this->Form($sender, TextFormat::RED . "You dont have Permission to Use This Wing");
    			  }
    			break;
    			case 3:
    			if($sender->hasPermission("balrog.wing") or $sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)){

    			    $setskin = new setSkin();
    			    $setskin->setSkin($sender, "balrog");
    			  } else {
    			    $this->Form($sender, TextFormat::RED . "You dont have Permission to Use This Wing");
    			  }
    			break;
    			case 4:
    			if($sender->hasPermission("blazingelectro.wing") or $sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)){

    			    $setskin = new setSkin();
    			    $setskin->setSkin($sender, "blazingelectro");
    			  } else {
    			    $this->Form($sender, TextFormat::RED . "You dont have Permission to Use This Wing");
			}
			break;
			case 5:
    			if($sender->hasPermission("poisondragon.wing") or $sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)){

    			    $setskin = new setSkin();
    			    $setskin->setSkin($sender, "PoisonDragon");
    			  } else {
    			    $this->Form($sender, TextFormat::RED . "You dont have Permission to Use This Wing");
    			  }
    			break;
			case 6:
    			  $this->resetSkin($sender);
    			break;
			case 7:
    			break;
    		}
            return false;
    	});
    	$form->setTitle(TextFormat::RED . "Custom" . TextFormat::WHITE . "Wing");
    	$form->setContent($txt);
    	$form->addButton("§cKagune §4Kaneki");
    	$form->addButton("§0Kakuja §4Kaneki");
    	$form->addButton("§6Mercy §awing");
    	$form->addButton("§cBalrog §awing");
    	$form->addButton("§eBlazing §fElectro §awing");
    	$form->addButton("§2Poison§5Dragon §awing");
    	$form->addButton("Reset Skin");
    	$form->addButton("Exit");
    	$form->sendToPlayer($sender);
    	return $form;
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

    public function CSForm($sender, string $txt){
    	$form = new SimpleForm(function (Player $sender, $data = null){
    		if($data === null){
    			return false;
    		}
    		switch($data){
    			case 0:
    			if($sender->hasPermission("kagune.wing") or $sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)){

    			    $setskin = new setSkin();
    			    $setskin->setSkin($sender, "kagune");
    			  } else {
    			    $this->Form($sender, TextFormat::RED . "You dont have Permission to Use This Wing");
    			  }
    			break;
    			case 1:
    			if($sender->hasPermission("kakuja.wing") or $sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)){

    			    $setskin = new setSkin();
    			    $setskin->setSkin($sender, "kakuja");
    			  } else {
    			    $this->Form($sender, TextFormat::RED . "You dont have Permission to Use This Wing");
    			  }
    			break;
    			case 2:
    			if($sender->hasPermission("mercy.wing") or $sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)){

    			    $setskin = new setSkin();
    			    $setskin->setSkin($sender, "mercy");
    			  } else {
    			    $this->Form($sender, TextFormat::RED . "You dont have Permission to Use This Wing");
    			  }
    			break;
    			case 3:
    			if($sender->hasPermission("balrog.wing") or $sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)){

    			    $setskin = new setSkin();
    			    $setskin->setSkin($sender, "balrog");
    			  } else {
    			    $this->Form($sender, TextFormat::RED . "You dont have Permission to Use This Wing");
    			  }
    			break;
    			case 4:
    			if($sender->hasPermission("blazingelectro.wing") or $sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)){

    			    $setskin = new setSkin();
    			    $setskin->setSkin($sender, "blazingelectro");
    			  } else {
    			    $this->Form($sender, TextFormat::RED . "You dont have Permission to Use This Wing");
			}
			break;
			case 5:
    			if($sender->hasPermission("poisondragon.wing") or $sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)){

    			    $setskin = new setSkin();
    			    $setskin->setSkin($sender, "PoisonDragon");
    			  } else {
    			    $this->Form($sender, TextFormat::RED . "You dont have Permission to Use This Wing");
    			  }
    			break;
			case 6:
    			  $this->resetSkin($sender);
    			break;
			case 7:
    			break;
    		}
            return false;
    	});
    	$form->setTitle(TextFormat::RED . "Custom" . TextFormat::WHITE . "Wing");
    	$form->setContent($txt);
    	$form->addButton("§cKagune §4Kaneki");
    	$form->addButton("§0Kakuja §4Kaneki");
    	$form->addButton("§6Mercy §awing");
    	$form->addButton("§cBalrog §awing");
    	$form->addButton("§eBlazing §fElectro §awing");
    	$form->addButton("§2Poison§5Dragon §awing");
    	$form->addButton("Reset Skin");
    	$form->addButton("Exit");
    	$form->sendToPlayer($sender);
    	return $form;
    }
    
    public function checkRequirement(){
      if(!extension_loaded("gd")){
        $this->getServer()->getLogger()->info("§6Clothes: Uncomment gd2.dll (remove symbol ';' in ';extension=php_gd2.dll') in bin/php/php.ini to make the plugin working");
        $this->getServer()->getPluginManager()->disablePlugin($this);
      }
      if(!class_exists(SimpleForm::class)){
        $this->getServer()->getLogger()->info("§6Clothes: FormAPI class missing,pls use .phar from poggit!");
        $this->getServer()->getPluginManager()->disablePlugin($this);
        return;
      }
      if (!file_exists($this->getDataFolder() . "steve.png") || !file_exists($this->getDataFolder() . "steve.json") || !file_exists($this->getDataFolder() . "config.yml")) {
            if (file_exists(str_replace("config.yml", "", $this->getResources()["config.yml"]))) {
                $this->recurse_copy(str_replace("config.yml", "", $this->getResources()["config.yml"]), $this->getDataFolder());
            } else {
                $this->getServer()->getLogger()->info("§6Clothes: Something wrong with the resources");
                $this->getServer()->getPluginManager()->disablePlugin($this);
                return;
            }
      }
    }
    
    public function recurse_copy($src, $dst)
    {
        $dir = opendir($src);
        @mkdir($dst);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
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
