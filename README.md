### SimpleClothes 1.2.0
This plugin is a clothing plugin like cosmetics, you could say it's more perfect.
- This plugin will continue to be developed.
- Not for sale

### Feature
- Add Shop
- Add Mystery Crate
- Add Scorehud Score `{simpleclothes.crate}` Show Key
- Add Command rca and permission simpleclothes

- **ClothesForm**
- Wings Form - Have 8 Skin
- Combo Wings Form - Have 3 Skin
- Genshin Impact Skin Form - Have 2 Skin
- Capes Form - Have 25 Skin
- Hats Form - Have 3 Skin
- Particle Form - Have 10 Particle

### Commands
- **/sccrate help**
- -----[ SimpleClothes ]-----
- /simpleclothes - §gOpen the clothes menu";
- /clothes §7- §gAlias command clothes menu";
- /sc §7- §gAlias command clothes menu";
- -----[ More Command ]-----
- /scmenu §7- §gShow basic menu";
- /scshop §7- §gOpen shop menu simpleclothes";
- /sccrate §7- §gOpen crate menu simpleclothes";
- /scrca §7- §gRca Simpleclothes";
- /permission §7- §gPermission Simpleclothes";
- -----[ Crate ]-----
- /sccrate help - §gshow help command simpleclothes";
- /sccrate setkey - §gsetkey crate simpleclothes";
- /sccrate givekey §7- §ggivekey crate simpleclothes";
- /sccrate takekey §7- §gtakekey crate simpleclothes";
- /sccrate spawncrate §7- §gspawn crate simpleclothes";
- /sccrate removecrate §7- §gremove crate simpleclothes";

### Configuration
**Crate**
```yaml
# to add clothes gift
# permission add {player} kagune.wing
# or
# scrca {player} permission add {player} kagune.wing
reward:
  - name: "§l§e1000 Money"
    commands:
      - "givemoney {player} 1000"
  - name: "§l§e20 Diamonds"
    commands:
      - "give {player} diamond 20"
  - name: "§l§e32 Iron Ingot"
    commands:
      - "give {player} iron_ingot 32"
  - name: "§l§e10 Emerald"
    commands:
      - "give {player} emerald 10"
```
**Shop**
```yaml
# Paste Permission Here And The Price
# Customize Format
# Shop
shop:
  wings:
    "demon.wing": 1000
    "kagune.wing": 1500
  capes:
    "Aceee.cape": 5000
    "CawPe.cape": 10000
  hats:
    "tv.hats": 2000
    "frog.hats": 2500
  particles:
    "particle.flame": 1200
    "particle.lava": 1800
```
### Video Review
[Watch the video on YouTube](https://youtu.be/VhpFutHtjFI?si=klDZlk9MRQF5Jk_d)
