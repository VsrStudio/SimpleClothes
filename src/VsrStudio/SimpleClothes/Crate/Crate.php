<?php

namespace VsrStudio\SimpleClothes\Crate;

use VsrStudio\SimpleClothes\Main;
use pocketmine\player\Player;

use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\transaction\SimpleInvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\VanillaItems;
use pocketmine\block\VanillaBlocks;

class Crate {

    public function openChest(Player $player): void {
        $menu = InvMenu::create(InvMenu::TYPE_CHEST);
        $menu->setName("§l§d⚡ List Reward ⚡");

        // Fake enchantment glow effect
        $glow = new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 1);

        $items = [
            VanillaItems::NETHER_STAR()
                ->setCustomName("§b✦ Random Wings ✦")
                ->setLore(["§7A mystical power that", "§7grants random wings!"])
                ->addEnchantment($glow),
            
            VanillaItems::ENCHANTED_BOOK()
                ->setCustomName("§e✦ Random Cape ✦")
                ->setLore(["§7A magical cape from another realm.", "§7Select a random cape!"])
                ->addEnchantment($glow),
            
            VanillaItems::DRAGON_BREATH()
                ->setCustomName("§6✦ Random Hats ✦")
                ->setLore(["§7A mysterious hat with", "§7unknown magical properties!"])
                ->addEnchantment($glow),
            
            VanillaItems::END_CRYSTAL()
                ->setCustomName("§d✦ Random Particle ✦")
                ->setLore(["§7A mystical particle effect", "§7that radiates magical energy!"])
                ->addEnchantment($glow),
        ];

        $inventory = $menu->getInventory();
        $slots = [10, 12, 14, 16]; // Positions in GUI
        foreach ($items as $index => $item) {
            $inventory->setItem($slots[$index], $item);
        }

        $borderItem = VanillaBlocks::STAINED_GLASS_PANE()->asItem()->setCustomName(" ");
        for ($i = 0; $i < 27; $i++) {
            if (!in_array($i, $slots)) {
                $inventory->setItem($i, $borderItem);
            }
        }

        // Prevent item pickup
        $menu->setListener(function (SimpleInvMenuTransaction $transaction): InvMenuTransactionResult {
            return $transaction->discard();
        });

        $menu->send($player);
    }
}
