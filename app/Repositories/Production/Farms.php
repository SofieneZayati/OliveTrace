<?php

namespace App\Repositories\Production;

use App\Entities\Production\Farm;
use App\Enums\FarmStatus;
use App\Enums\Role;
use App\Models\User;
use Doctrine\ORM\EntityManagerInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class Farms
{
    public function __construct(private EntityManagerInterface $manager) {}

    public function find(int $id): Farm
    {
        return $this->manager->find(Farm::class, $id) ?? abort(404);
    }

    /** Mariem can select active farms by authenticated producer ID, without copying origin fields. */
    public function selectableForUser(int $userId): array
    {
        if (! User::whereKey($userId)->where('role', Role::Producer->value)->where('is_active', true)->exists()) {
            return [];
        }

        return $this->manager->createQueryBuilder()->select('f', 'p')->from(Farm::class, 'f')
            ->join('f.producerProfile', 'p')->where('p.userId = :user')->andWhere('p.isActive = true')
            ->andWhere('f.status = :status')->setParameter('user', $userId)
            ->setParameter('status', FarmStatus::Active->value)->orderBy('f.name', 'ASC')->getQuery()->getResult();
    }

    public function paginate(array $filters, int $page, ?int $userId = null): LengthAwarePaginator
    {
        $query = $this->manager->createQueryBuilder()->select('f', 'p')->from(Farm::class, 'f')->join('f.producerProfile', 'p');
        if ($userId !== null) {
            $query->andWhere('p.userId = :user')->setParameter('user', $userId);
        }
        if ($filters['search'] ?? null) {
            $query->andWhere('LOWER(f.name) LIKE :search OR LOWER(p.displayName) LIKE :search')
                ->setParameter('search', '%'.mb_strtolower($filters['search']).'%');
        }
        foreach (['governorate', 'status'] as $field) {
            if ($filters[$field] ?? null) {
                $query->andWhere('f.'.$field.' = :'.$field)->setParameter($field, $filters[$field]);
            }
        }
        $total = (int) (clone $query)->select('COUNT(f.id)')->getQuery()->getSingleScalarResult();
        $items = $query->orderBy('f.name', 'ASC')->addOrderBy('f.id', 'ASC')
            ->setFirstResult(($page - 1) * 12)->setMaxResults(12)->getQuery()->getResult();

        return (new LengthAwarePaginator($items, $total, 12, $page, ['path' => request()->url()]))->withQueryString();
    }
}
