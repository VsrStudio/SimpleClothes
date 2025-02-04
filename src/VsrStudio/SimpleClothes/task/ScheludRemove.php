<?php

namespace VsrStudio\SimpleClothes\task; 
use pocketmine\scheduler\Task; 
use SimpleClothes\PlayerParticle\task\Schelud;
use SimpleClothes\PlayerParticle\Main;
use pocketmine\level\particle\FloatingTextParticle; 


class ScheludRemove extends Task{


    public function __construct(Schelud $plugin, $part, $vect, $level){ 
       $this->vect = $vect;
       $this->part = $part;
       $this->level = $level;
    } 


    public function onRun(): void{ 
        
        $this->part->setInvisible(true);
        $this->level->addParticle($this->vect, $this->part);
        
    }

}
