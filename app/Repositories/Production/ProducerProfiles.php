<?php

namespace App\Repositories\Production;

use App\Entities\Production\ProducerProfile;
use Doctrine\ORM\EntityManagerInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class ProducerProfiles
{
    public function __construct(private EntityManagerInterface $manager) {}

    public function forUser(int $userId): ?ProducerProfile
    {
        return $this->manager->getRepository(ProducerProfile::class)->findOneBy(['userId' => $userId]);
    }

    public function find(int $id): ProducerProfile
    {
        return $this->manager->find(ProducerProfile::class, $id) ?? abort(404);
    }

    public function paginate(array $filters, int $page): LengthAwarePaginator
    {
        $query = $this->manager->createQueryBuilder()->select('p')->from(ProducerProfile::class, 'p');
        if ($filters['search'] ?? null) {
            $query->andWhere('LOWER(p.displayName) LIKE :search OR LOWER(p.companyName) LIKE :search')
                ->setParameter('search', '%'.mb_strtolower($filters['search']).'%');
        }
        if (isset($filters['active'])) {
            $query->andWhere('p.isActive = :active')->setParameter('active', (bool) $filters['active']);
        }
        $total = (int) (clone $query)->select('COUNT(p.id)')->getQuery()->getSingleScalarResult();
        $items = $query->orderBy('p.displayName', 'ASC')->addOrderBy('p.id', 'ASC')
            ->setFirstResult(($page - 1) * 12)->setMaxResults(12)->getQuery()->getResult();

        return (new LengthAwarePaginator($items, $total, 12, $page, ['path' => request()->url()]))->withQueryString();
    }
}
