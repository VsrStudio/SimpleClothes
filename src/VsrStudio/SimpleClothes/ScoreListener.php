<?php

namespace VsrStudio\SimpleClothes;

use Ifera\ScoreHud\event\TagsResolveEvent;
use Ifera\ScoreHud\event\PlayerTagsUpdateEvent;
use Ifera\ScoreHud\scoreboard\ScoreTag;
use Ifera\ScoreHud\ScoreHud;
use pocketmine\event\Listener;
use pocketmine\player\Player;

class ScoreListener implements Listener {

    private Main $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function onTagResolve(TagsResolveEvent $event): void {
        $player = $event->getPlayer();
        $tag = $event->getTag();

        if ($tag->getName() === "simpleclothes.crate") {
            $keys = $this->plugin->getKey($player);
            $event->setTag(new ScoreTag("simpleclothes.crate", (string) $keys));
        }
    }

    public function updateKeyTag(Player $player): void {
        if ($player->isOnline()) {
            $keys = (string) $this->plugin->getKey($player);
            $tag = new ScoreTag("simpleclothes.crate", $keys);

            $event = new PlayerTagsUpdateEvent($player, [$tag]);
            $event->call();
        }
    }
}
