<?php

namespace VsrStudio\SimpleClothes\Shop;

use VsrStudio\SimpleClothes\Form\CustomForm;
use VsrStudio\SimpleClothes\Form\SimpleForm;
use VsrStudio\SimpleClothes\Main;
use onebone\economyapi\EconomyAPI;
use pocketmine\player\Player;

class Shop {

    private $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function ShopForm(Player $player) {
        $form = new SimpleForm(function(Player $player, $data = null) {
            if ($data === null) return;

            switch ($data) {
                case 0: $this->WingShop($player); break;
                case 1: $this->CapeShop($player); break;
                case 2: $this->HatsShop($player); break;
                case 3: $this->Particle($player); break;
            }
        });

        $form->setTitle("§eShop Menu");
        $form->setContent("Select your options shop");
        $form->addButton("§eWing Shop\n§rClick to buy");
        $form->addButton("§eCapes Shop\n§rClick to buy");
        $form->addButton("§eHats Shop\n§rClick to buy");
        $form->addButton("§eParticle Shop\n§rClick to buy");
        $form->sendToPlayer($player);
    }

    private function openShop(Player $player, string $type, string $title) {
        $items = $this->plugin->getConfig()->getNested("shop.{$type}", []);
        if (empty($items)) {
            $player->sendMessage("§cNo items available for this shop.");
            return;
        }

        $form = new CustomForm(function (Player $player, $data = null) use ($type, $items) {
            if ($data === null) return;

            $keys = array_keys($items);
            if (!isset($keys[$data[0]])) {
                $player->sendMessage("§cInvalid selection.");
                return;
            }

            $selectedPermission = $keys[$data[0]];
            $price = $items[$selectedPermission];

            if ($player->hasPermission($selectedPermission)) {
                $player->sendMessage("§cYou already own this item: §e{$selectedPermission}");
                return;
            }

            $economy = EconomyAPI::getInstance();
            $balance = $economy->myMoney($player);

            if ($balance >= $price) {
                $economy->reduceMoney($player, $price);
                $attachment = $player->addAttachment($this->plugin);
                $attachment->setPermission($selectedPermission, true);
                $player->sendMessage("§aSuccessfully purchased: §e{$selectedPermission}");
            } else {
                $player->sendMessage("§cNot enough money! Price: §e{$price} Money.");
            }
        });

        $form->setTitle("§e{$title}");
        $form->addDropdown("Select an item to buy:", array_map(fn($key, $price) => "{$key} - {$price} Coins", array_keys($items), $items));
        $form->sendToPlayer($player);
    }

    public function WingShop(Player $player) {
        $this->openShop($player, "wings", "Buy Wings");
    }

    public function CapeShop(Player $player) {
        $this->openShop($player, "capes", "Buy Capes");
    }

    public function HatsShop(Player $player) {
        $this->openShop($player, "hats", "Buy Hats");
    }

    public function Particle(Player $player) {
        $this->openShop($player, "particles", "Buy Particles");
    }
}
