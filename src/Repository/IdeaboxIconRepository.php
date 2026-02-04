<?php

namespace App\Repository;

use App\Entity\QualityCheck\IdeaboxIcon;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * IdeaboxIconRepository constructor.
 * @param ManagerRegistry $registry
 */
class IdeaboxIconRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, IdeaboxIcon::class);
    }

    /**
     * @return IdeaboxIcon[]
     */
    public function getOrderedIdeaBoxIcons(): array
    {
        return $this->createQueryBuilder('icons')
            ->orderBy('icons.order', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
