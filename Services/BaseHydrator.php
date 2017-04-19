<?php

namespace JsonApiBundle\Services;

use Doctrine\ORM\EntityManager;

/**
 * Class BaseHydrator
 * @package JsonApiBundle\Services
 */
abstract class BaseHydrator
{

    use MethodsTrait;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var boolean
     */
    private $isUpdated;

    /**
     * @param $value
     */
    private function setUpdate($value)
    {
        if ($this->isUpdated == true) {
            return;
        }

        $this->isUpdated = $value;
    }

    /**
     * BaseHydrator constructor.
     * @param $entityManager
     */
    public function __construct($entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return object
     */
    abstract protected function getNewObject();

    /**
     * @return array
     */
    abstract public function getAttributes();

    /**
     * @return array
     */
    abstract public function getRelations();


    /**
     * @param $item - one row in data section in section relationships JSON API
     * @return boolean
     */
    private function addChildEntity($object, $field, $item)
    {
        $class = get_class($this->getRelatedObjects()[$item['type']]);
        $child = $this->entityManager->getRepository($class)->find($item['id']);

        return $this->setParamsToObject($object, [$field => $child]);
    }

    /**
     * @param array $requestAttributes - [field => value ] form section data['attributes'] for JSON Api request
     * @param array $relationAttributes - array from section data['relationships'] for JSON Api request
     *
     * @return object
     */
    public function setValues(array $requestAttributes, array $relationAttributes)
    {
        $object = $this->getNewObject();

        // set attributes for entity
        $this->setAttributesObject($requestAttributes, $object);

        // set relationship values
        $this->setRelationshipsObject($relationAttributes, $object);

        return $object;
    }


    /**
     * @param array $requestAttributes - array ['field' => 'value'] from attributes section (JSON Api)
     * @param $object
     *
     * @return boolean
     */
    private function setAttributesObject(array $requestAttributes, $object)
    {
        // get attributes for entity's hydrator
        $objectAttributes = $this->getAttributes();

        // set only attributes values
        $this->setUpdate($this->setParamsToObject($object, $requestAttributes, $objectAttributes));

        return $object;
    }

    /**
     * @param array $relationAttributes
     * @param $object
     *
     * @return $object
     */
    private function setRelationshipsObject(array $relationAttributes, $object)
    {
        // relationship values
        foreach ($this->getRelations() as $relation) {

            if (array_key_exists($relation, $relationAttributes)) {

                foreach ($relationAttributes[$relation] as $data) {

                    $isIndexed = array_values($data) === $data;

                    // if get indexes array - it's a few child entities in queue to add
                    if ($isIndexed) {

                        foreach ($data as $item) {
                            $this->setUpdate($this->addChildEntity($object, $relation, $item));
                        }

                    } else {
                        $this->setUpdate($this->addChildEntity($object, $relation, $data));
                    }
                }
            }
        }

        return $object;
    }

    /**
     * @param array $requestAttributes
     * @param array $relationAttributes
     *
     * @return object
     */
    public function updateValues(array $requestAttributes, array $relationAttributes)
    {
        $this->isUpdate = false;

        $class = get_class($this->getNewObject());

        $object = $this
            ->entityManager
            ->getRepository($class)
            ->find($requestAttributes['id']);

        if (!empty($requestAttributes['attributes'])) {
            $this->setAttributesObject($requestAttributes['attributes'], $object);
        }

        if (!empty($relationAttributes)) {
            $this->setRelationshipsObject($relationAttributes, $object);
        }

        return $object;
    }
}
