<?php

namespace JsonApiBundle\Services;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use ICanBoogie\Inflector;

/**
 * Class MethodsTrait
 * @package JsonApiBundle\Services
 */
trait MethodsTrait
{
    /**
     * @return Inflector
     */
    private function getInflector()
    {
        return Inflector::get(Inflector::DEFAULT_LOCALE);
    }

    /**
     * Get name function for set field
     *
     * @param $field
     *
     * @return string
     */
    private function setter($field)
    {
        return 'set' . $this->getInflector()->camelize($field);
    }

    /**
     * @param $field
     *
     * @return string
     */
    private function getter($field)
    {
        return 'get' . $this->getInflector()->camelize($field);
    }

    /**
     * @param $field
     *
     * @return string
     */
    private function adder($field)
    {
        return 'add' . $this->getInflector()->camelize($field);
    }

    /**
     * @param $field
     *
     * @return array
     */
    private function getNameGetters($field)
    {
        return [
            $this->getter($field)
        ];
    }

    /**
     * @param $field
     *
     * @return array
     */
    private function getNameSetters($field)
    {
        return [
            $this->setter($field),
            $this->adder($field),
        ];
    }

    /**
     * @param $object
     * @param $field
     * @param $value
     */
    private function findSetterMethods($object, $field, $value)
    {
        $methods = $this->getNameSetters($field);
        foreach ($methods as $method) {

            if (method_exists($object, $method)) {
                $object->{$method}($value);
            }
        }
    }

    /**
     * @param $object
     * @param $field
     *
     * @return array
     */
    private function findGetterMethods($object, $field)
    {
        $result = [];

        $methods = $this->getNameGetters($field);
        foreach ($methods as $method) {

            if (method_exists($object, $method)) {
                $result[$field] = $object->{$method}();
            }
        }

        return $result;
    }

    /**
     * @param $object
     * @param $field
     * @param $value
     */
    private function callSetterMethods($object, $field, $value)
    {
        $singleField = $this->getInflector()->singularize($field);
        $pluralField = $this->getInflector()->pluralize($field);

        $this->findSetterMethods($object, $singleField, $value);
        $this->findSetterMethods($object, $pluralField, $value);
    }

    /**
     * @param $object
     * @param $field
     *
     * @return array
     */
    private function callGetterMethods($object, $field)
    {
        $values = [];

        $singleField = $this->getInflector()->singularize($field);
        $pluralField = $this->getInflector()->pluralize($field);

        $values[$singleField] = $this->findGetterMethods($object, $singleField);
        $values[$pluralField] = $this->findGetterMethods($object, $pluralField);

        return !empty($values[$singleField]) ? $values[$singleField] : $values[$pluralField];
    }

    /**
     * @param array $object
     * @param $map
     *
     * @return array
     */
    protected function getParamsFromObject($object, array $map)
    {
        $result = [];
        foreach ($map as $field) {
            $result[] = $this->callGetterMethods($object, $field);
        }

        return $result;
    }

    /**
     * @param $current
     * @param $new
     *
     * @return bool
     */
    private function isEqual($current, $new)
    {
        if ($current instanceof PersistentCollection) {
            return $current->contains($new) ? true : false;
        }

        if ($current instanceof ArrayCollection) {
            return $current->contains($new) ? true : false;
        }

        return $current == $new ? true : false;
    }

    /**
     * @param $object
     * @param $map - array ['title' => 'value']
     * @param array|null $arrayFilter - if set, need to check if field value from map array in filter array
     *
     * @return boolean
     */
    public function setParamsToObject($object, array $map, array $arrayFilter = null)
    {
        $isUpdate = false;

        foreach ($map as $field => $value) {

            if ($arrayFilter) {
                if (!in_array($field, $arrayFilter)) {
                    continue;
                }
            }

            $currentValue = $this->callGetterMethods($object, $field);

            if ($this->isEqual($currentValue[$field], $value)) {
                continue;
            }

            $this->callSetterMethods($object, $field, $value);
            $isUpdate = true;
        }

        return $isUpdate;
    }
}
