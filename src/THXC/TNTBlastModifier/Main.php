<?php

declare(strict_types=1);

namespace THXC\TNTBlastModifier;

use pocketmine\event\entity\ExplosionPrimeEvent;
use pocketmine\event\Listener;
use pocketmine\math\AxisAlignedBB;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\Config;
use pocketmine\world\Position;

class Main extends PluginBase implements Listener {
	/** @var Config */
	protected $config;
	/** @var float */
	protected $factor;

	public function onEnable(): void {
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML, [
			"blastMultiplier" => 1.0
		]);
		$this->factor = $this->config->get("blastMultiplier", 1.0);
	}

	/**
	 * @param ExplosionPrimeEvent $ev
	 *
	 * @priority        HIGHEST
	 * @ignoreCancelled true
	 */
	public function onExplode(ExplosionPrimeEvent $ev): void {
		$level = $ev->getEntity()->getWorld();
		$this->getScheduler()->scheduleTask(new ClosureTask(function () use ($ev, $level): void {
			$explosionSize = $ev->getForce() * 2;
			$minX = (int)floor($ev->getEntity()->getPosition()->getX() - $explosionSize - 1);
			$maxX = (int)ceil($ev->getEntity()->getPosition()->getX() + $explosionSize + 1);
			$minY = (int)floor($ev->getEntity()->getPosition()->getY() - $explosionSize - 1);
			$maxY = (int)ceil($ev->getEntity()->getPosition()->getY() + $explosionSize + 1);
			$minZ = (int)floor($ev->getEntity()->getPosition()->getZ() - $explosionSize - 1);
			$maxZ = (int)ceil($ev->getEntity()->getPosition()->getZ() + $explosionSize + 1);

			$explosionBB = new AxisAlignedBB($minX, $minY, $minZ, $maxX, $maxY, $maxZ);

			$list = $level->getNearbyEntities($explosionBB, $ev->getEntity());
			foreach($list as $entity) {
				$distance = $entity->getPosition()->distance($ev->getEntity()) / $explosionSize;

				if($distance <= 1) {
					$motion = $entity->subtract($ev->getEntity())->normalize();
					$impact = (1 - $distance) * ($exposure = 1);

					$entity->setMotion($motion->multiply($impact)->multiply($this->factor));
				}
			}
		}));
	}
}
