<?php

declare(strict_types=1);

namespace NhanAZ\DebugStick;

use pocketmine\block\Anvil;
use pocketmine\block\Bamboo;
use pocketmine\block\Block;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\item\VanillaItems;
use pocketmine\math\Facing;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

class Main extends PluginBase implements Listener {

	private const DEBUG_TAG = "debugstick";
	private const DEBUG_PERMISSION = "debugstick.use";

	/** @var array<string> $anvil */
	private array $anvil = [];
	/** @var array<string> $bambom */
	private array $bambom = [];

	protected function onEnable(): void {
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->registerDubugSick();
	}

	private function registerDubugSick(): void {
		StringToItemParser::getInstance()->register("debugstick", function (): Item {
			$stick = VanillaItems::STICK();
			$stick->setCustomName("Debug Stick");
			$stick->getNamedTag()->setByte(self::DEBUG_TAG, 1);
			return $stick;
		});
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
		switch ($command->getName()) {
			case "debugstick":
				if ($sender instanceof Player) {
					$item = StringToItemParser::getInstance()->parse("debugstick");
					if ($item === null) {
						$sender->sendMessage(TextFormat::RED . "DebugStick item not registered!");
						return true;
					}
					if ($sender->getInventory()->canAddItem($item)) {
						$sender->getInventory()->addItem($item);
						$sender->sendMessage(TextFormat::GREEN . "Given you a debug stick!");
					} else {
						$sender->sendMessage(TextFormat::RED . "Your inventory is full!");
					}
				} else {
					$sender->sendMessage(TextFormat::RED . "This command can't be used from the console!");
				}
				return true;
			default:
				return false;
		}
	}

	private function updateBlock(Block $block): void {
		$blockPos = $block->getPosition();
		$blockPos->getWorld()->scheduleDelayedBlockUpdate($blockPos->asVector3(), 0);
		(new BlockUpdateEvent($block));
	}

	private function intAnvilFacingToText(int $facing): string {
		// Facing::NORTH = 2 = BẮC
		// Facing::SOUTH = 3 = NAM
		// Facing::WEST  = 4 = TÂY
		// Facing::EAST  = 5 = ĐÔNG
		match ($facing) {
			Facing::NORTH => $string = "§c--- §6Facing §c---\n§b> §9North(2)\n§9South(3)\n§9West(4)\n§9East(5)",
			Facing::SOUTH => $string = "§c--- §6Facing §c---\n§9North(2)\n§b> §9South(3)\n§9West(4)\n§9East(5)",
			Facing::WEST => $string = "§c--- §6Facing §c---\n§9North(2)\n§9South(3)\n§b> §9West(4)\n§9East(5)",
			Facing::EAST => $string = "§c--- §6Facing §c---\n§9North(2)\n§9South(3)\n§9West(4)\n§b> §9East(5)",
			default => throw new \Exception("Unknown status code")
		};
		return $string ?? throw new \Exception("Unknown status code");
	}

	private function intAnvilDamageToText(int $damage): string {
		// Anvil::UNDAMAGED        = 0 = KHÔNG_BỊ_HƯ_HẠI
		// Anvil::SLIGHTLY_DAMAGED = 1 = BỊ_HƯ_HẠI_NHẸ
		// Anvil::VERY_DAMAGED     = 2 = BỊ_HƯ_HẠI_NẶNG
		match ($damage) {
			Anvil::UNDAMAGED => $string = "§c--- §6Damage §c---\n§b> §9Undamaged(0)\n§9Sightly_Damaged(1)\n§9Very_Damaged(2)",
			Anvil::SLIGHTLY_DAMAGED => $string = "§c--- §6Damage §c---\n§9Undamaged(0)\n§b> §9Sightly_Damaged(1)\n§9Very_Damaged(2)",
			Anvil::VERY_DAMAGED => $string = "§c--- §6Damage §c---\n§9Undamaged(0)\n§9Sightly_Damaged(1)\n§b> §9Very_Damaged(2)",
			default => throw new \Exception("Unknown status code")
		};
		return $string ?? throw new \Exception("Unknown status code");
	}

	private function intLeafSizeToText(int $leafSize): string {
		// Bamboo::NO_LEAVES    = 0 = KHÔNG_LÁ
		// Bamboo::SMALL_LEAVES = 1 = LÁ_NHỎ
		// Bamboo::LARGE_LEAVES = 2 = LÁ_BỰ
		match ($leafSize) {
			Bamboo::NO_LEAVES => $string = "§c--- §6LeafSize §c---\n§b> §9No_Leaves(0)\n§9Small_Leaves(1)\n§9Large_Leaves(2)",
			Bamboo::SMALL_LEAVES => $string = "§c--- §6LeafSize §c---\n§9No_Leaves(0)\n§b> §9Small_Leaves(1)\n§9Large_Leaves(2)",
			Bamboo::LARGE_LEAVES => $string = "§c--- §6LeafSize §c---\n§9No_Leaves(0)\n§9Small_Leaves(1)\n§b> §9Large_Leaves(2)",
			default => throw new \Exception("Unknown status code")
		};
		return $string ?? throw new \Exception("Unknown status code");
	}

	private function boolReadyToText(bool $ready): string {
		// false = 0
		// true  = 1
		match ($ready) {
			true => $string = "§c--- §6Ready §c---\n§b> §9true(1)\n§9false(0)",
			false => $string = "§c--- §6Ready §c---\n§9true(1)\n§b> §9false(0)"
		};
		return $string ?? throw new \Exception("Unknown status code");
	}

	public function onPlayerInteract(PlayerInteractEvent $event): void {
		$action = $event->getAction();
		$player = $event->getPlayer();
		$playerName = $player->getName();
		$item = $event->getItem();
		$block = $event->getBlock();
		$debugTag = $item->getNamedTag()->getTag(self::DEBUG_TAG);
		if ($debugTag === null) {
			return;
		}
		if (!$player->hasPermission(self::DEBUG_PERMISSION)) {
			$player->sendMessage(TextFormat::RED . "You don't have permission to use this item!");
			return;
		}
		$event->cancel();
		if ($block instanceof Anvil) {
			if (!isset($this->anvil[$playerName])) {
				$this->anvil[$playerName] = "facing";
			}
			if ($player->isSneaking()) {
				if ($this->anvil[$playerName] == "facing") {
					$this->anvil[$playerName] = "damage";
					$player->sendTip("§c--- §6Properties §c---\n§bFacing\n§9> §bDamage");
					return;
				}
				if ($this->anvil[$playerName] == "damage") {
					$this->anvil[$playerName] = "facing";
					$player->sendTip("§c--- §6Properties §c---\n§9> §bFacing\n§bDamage");
					return;
				}
			}
			if ($this->anvil[$playerName] == "facing") {
				if ($action === PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
					match ($block->getFacing()) {
						Facing::NORTH => $block->setFacing(Facing::SOUTH),
						Facing::SOUTH => $block->setFacing(Facing::WEST),
						Facing::WEST => $block->setFacing(Facing::EAST),
						Facing::EAST => $block->setFacing(Facing::NORTH),
						default => throw new \Exception("Unknown status code")
					};
					$this->updateBlock($block);
					$player->sendTip(self::intAnvilFacingToText($block->getFacing()));
					return;
				}
				if ($action === PlayerInteractEvent::LEFT_CLICK_BLOCK) {
					match ($block->getFacing()) {
						Facing::NORTH => $block->setFacing(Facing::EAST),
						Facing::SOUTH => $block->setFacing(Facing::NORTH),
						Facing::WEST => $block->setFacing(Facing::SOUTH),
						Facing::EAST => $block->setFacing(Facing::WEST),
						default => throw new \Exception("Unknown status code")
					};
					$this->updateBlock($block);
					$player->sendTip(self::intAnvilFacingToText($block->getFacing()));
					return;
				}
				return;
			}
			if ($this->anvil[$playerName] == "damage") {
				if ($action === PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
					match ($block->getDamage()) {
						Anvil::UNDAMAGED => $block->setDamage(Anvil::SLIGHTLY_DAMAGED),
						Anvil::SLIGHTLY_DAMAGED =>  $block->setDamage(Anvil::VERY_DAMAGED),
						Anvil::VERY_DAMAGED =>  $block->setDamage(Anvil::UNDAMAGED),
						default => throw new \Exception("Unknown status code")
					};
					$this->updateBlock($block);
					$player->sendTip(self::intAnvilDamageToText($block->getDamage()));
					return;
				}
				if ($action === PlayerInteractEvent::LEFT_CLICK_BLOCK) {
					match ($block->getDamage()) {
						Anvil::UNDAMAGED => $block->setDamage(Anvil::VERY_DAMAGED),
						Anvil::SLIGHTLY_DAMAGED =>  $block->setDamage(Anvil::UNDAMAGED),
						Anvil::VERY_DAMAGED =>  $block->setDamage(Anvil::SLIGHTLY_DAMAGED),
						default => throw new \Exception("Unknown status code")
					};
					$this->updateBlock($block);
					$player->sendTip(self::intAnvilDamageToText($block->getDamage()));
					return;
				}
				return;
			}
			return;
		}
		if ($block instanceof Bamboo) {
			if (!isset($this->bambom[$playerName])) {
				$this->bambom[$playerName] = "leafsize";
			}
			if ($player->isSneaking()) {
				if ($this->bambom[$playerName] == "leafsize") {
					$this->bambom[$playerName] = "ready";
					$player->sendTip("§c--- §6Properties §c---\n§bLeafSize\n§9> §bReady\n§bThick");
					return;
				}
				if ($this->bambom[$playerName] == "ready") {
					$this->bambom[$playerName] = "thick";
					$player->sendTip("§c--- §6Properties §c---\n§bLeafSize\n§bReady\n§9> §bThick");
					return;
				}
				if ($this->bambom[$playerName] == "thick") {
					$this->bambom[$playerName] = "leafsize";
					$player->sendTip("§c--- §6Properties §c---\n§9> §bLeafSize\n§bReady\n§bThick");
					return;
				}
			}
			if ($this->bambom[$playerName] == "leafsize") {
				if ($action === PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
					match ($block->getLeafSize()) {
						Bamboo::NO_LEAVES => $block->setLeafSize(Bamboo::SMALL_LEAVES),
						Bamboo::SMALL_LEAVES => $block->setLeafSize(Bamboo::LARGE_LEAVES),
						Bamboo::LARGE_LEAVES => $block->setLeafSize(Bamboo::NO_LEAVES),
						default => throw new \Exception("Unknown status code")
					};
					$this->updateBlock($block);
					$player->sendTip(self::intLeafSizeToText($block->getLeafSize()));
					return;
				}
				if ($action === PlayerInteractEvent::LEFT_CLICK_BLOCK) {
					match ($block->getLeafSize()) {
						Bamboo::NO_LEAVES => $block->setLeafSize(Bamboo::LARGE_LEAVES),
						Bamboo::SMALL_LEAVES => $block->setLeafSize(Bamboo::NO_LEAVES),
						Bamboo::LARGE_LEAVES => $block->setLeafSize(Bamboo::SMALL_LEAVES),
						default => throw new \Exception("Unknown status code")
					};
					$this->updateBlock($block);
					$player->sendTip(self::intLeafSizeToText($block->getLeafSize()));
					return;
				}
				return;
			}
			if ($this->bambom[$playerName] == "ready") {
				if ($action === PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
					match ($block->isReady()) {
						true => $block->setReady(false),
						false => $block->setReady(true)
					};
					$this->updateBlock($block);
					$player->sendTip(self::boolReadyToText($block->isReady()));
					return;
				}
				if ($action === PlayerInteractEvent::LEFT_CLICK_BLOCK) {
					match (!$block->isReady()) {
						true => $block->setReady(false),
						false => $block->setReady(true)
					};
					$this->updateBlock($block);
					$player->sendTip(self::boolReadyToText($block->isReady()));
					return;
				}

				return;
			}
			if ($this->bambom[$playerName] == "thick") {
				if ($action === PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
					return;
				}
				if ($action === PlayerInteractEvent::LEFT_CLICK_BLOCK) {
					return;
				}
				return;
			}
			return;
		}
	}
}
