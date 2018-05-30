<?php

namespace App\Repository;

use App\Entity\AbstractEntity;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;

/**
 * Class AbstractRepository
 * @package App\Repository
 */
class AbstractRepository extends EntityRepository
{
    const DEFAULT_PAGINATOR_LIMIT = 100;

    /**
     * @param QueryBuilder $queryBuilder
     * @param int $page
     * @param int $limit
     * @return Pagerfanta
     */
    private function createQueryBuilderPaginator(QueryBuilder $queryBuilder, int $page, $limit = self::DEFAULT_PAGINATOR_LIMIT)
    {
        $paginator = $this->getPaginator($queryBuilder, false, false);
        $paginator->setMaxPerPage($limit);
        $paginator->setCurrentPage($page);

        return $paginator;
    }

    /**
     * @param array $criteria
     * @param array $sorting
     * @param int $page
     * @param int $limit
     * @return Pagerfanta
     */
    public function createPaginator(array $criteria = [], array $sorting = [], int $page, int $limit = self::DEFAULT_PAGINATOR_LIMIT)
    {
        $queryBuilder = $this->createQueryBuilder('o');

        $this->applyCriteria($queryBuilder, $criteria);
        $this->applySorting($queryBuilder, $sorting);

        $paginator = $this->createQueryBuilderPaginator($queryBuilder, $page, $limit);

        return $paginator;
    }

    /**
     * @param string $entityPath
     * @return int
     */
    public function countEntities(string $entityPath)
    {
        $qb = $this->getEntityManager()->getRepository($entityPath)->createQueryBuilder('o');
        $qb->select('COUNT(o.id)');

        return $qb->getFirstResult();
    }

    /**
     * @param AbstractEntity $resource
     */
    public function add(AbstractEntity $resource)
    {
        $this->_em->persist($resource);
        $this->_em->flush();
    }

    /**
     * @param AbstractEntity $resource
     */
    public function remove(AbstractEntity $resource)
    {
        if (null !== $this->find($resource->getId())) {
            $this->_em->remove($resource);
            $this->_em->flush();
        }
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param bool $fetchJoinCollection
     * @param null $useOutputWalkers
     * @return Pagerfanta
     */
    protected function getPaginator(QueryBuilder $queryBuilder, $fetchJoinCollection = true, $useOutputWalkers = null)
    {
        // Use output walkers option in DoctrineORMAdapter should be false as it affects performance greatly (see #3775)
        return new Pagerfanta(new DoctrineORMAdapter($queryBuilder, $fetchJoinCollection, $useOutputWalkers));
    }

    /**
     * @param array $objects
     *
     * @return Pagerfanta
     */
    protected function getArrayPaginator($objects)
    {
        return new Pagerfanta(new ArrayAdapter($objects));
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param array $criteria
     */
    protected function applyCriteria(QueryBuilder $queryBuilder, array $criteria = [])
    {
        foreach ($criteria as $property => $value) {
            if (!in_array($property, array_merge($this->_class->getAssociationNames(), $this->_class->getFieldNames()))) {
                continue;
            }

            $name = $this->getPropertyName($property);

            if (null === $value) {
                $queryBuilder->andWhere($queryBuilder->expr()->isNull($name));
            } elseif (is_array($value)) {
                $queryBuilder->andWhere($queryBuilder->expr()->in($name, $value));
            } elseif ('' !== $value) {
                $parameter = str_replace('.', '_', $property);
                $queryBuilder
                    ->andWhere($queryBuilder->expr()->eq($name, ':' . $parameter))
                    ->setParameter($parameter, $value);
            }
        }
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param array $sorting
     */
    protected function applySorting(QueryBuilder $queryBuilder, array $sorting = [])
    {
        foreach ($sorting as $property => $order) {
            if (!in_array($property, array_merge($this->_class->getAssociationNames(), $this->_class->getFieldNames()))) {
                continue;
            }

            if (!empty($order)) {
                $queryBuilder->addOrderBy($this->getPropertyName($property), $order);
            }
        }
    }

    /**
     * @param string $name
     *
     * @return string
     */
    protected function getPropertyName($name)
    {
        if (false === strpos($name, '.')) {
            return 'o' . '.' . $name;
        }

        return $name;
    }
}
