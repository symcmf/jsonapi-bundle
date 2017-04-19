<?php

namespace JsonApiBundle\Services;

use Doctrine\ORM\EntityManager;

class BaseJSONApiBundle
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var integer
     */
    private $totalCount;

    /**
     * @return integer
     */
    public function getTotalCount()
    {
        return $this->totalCount;
    }

    /**
     * BaseJSONApiBundle constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param $class
     * @param $pageAttributes
     *
     * @return array
     */
    public function getQuery($class, $pageAttributes)
    {
        $offset = ($pageAttributes['number'] - 1) * $pageAttributes['size'];
        $object = $this
            ->entityManager
            ->getRepository($class)
            ->findBy([], [], $pageAttributes['size'], $offset);

        return $object;
    }

    /**
     * @param $id
     * @param $class
     *
     * @return null|object
     */
    public function getObject($id, $class)
    {
        return $this->entityManager->getRepository($class)->find($id);
    }

    /**
     * @param $object
     *
     * @return object
     */
    public function saveObject($object)
    {
        $this->entityManager->persist($object);
        $this->entityManager->flush();

        return $object;
    }

    /**
     * @param $object
     *
     * @return object
     */
    public function updateObject($object)
    {
        $this->entityManager->flush();

        return $object;
    }
}
