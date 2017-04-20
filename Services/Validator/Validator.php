<?php

namespace JsonApiBundle\Services\Validator;

use JsonApiBundle\Request\JSONApiRequest;
use JsonApiBundle\Services\JSONApiError;
use Symfony\Component\Validator\Validator\RecursiveValidator;
use Symfony\Component\Validator\ConstraintViolation;
use Doctrine\ORM\EntityManager;
use Neomerx\JsonApi\Encoder\Encoder;

class Validator
{
    protected $requestAttributes;
    protected $validator;
    protected $entityManager;
    protected $jsonApiRequest;
    protected $jsonApiError;

    /**
     * Validator constructor.
     * @param RecursiveValidator $validator
     * @param EntityManager $entityManager
     * @param JSONApiRequest $jsonApiRequest
     * @param JSONApiError $jsonApiError
     */
    public function __construct($validator, $entityManager, $jsonApiRequest, $jsonApiError)
    {
        $this->validator = $validator;
        $this->entityManager = $entityManager;
        $this->jsonApiRequest = $jsonApiRequest;
        $this->jsonApiError = $jsonApiError;
    }

    /**
     * @param string $bundleName
     * @param string $className
     * @return object
     */
    protected function getEntity($bundleName, $className)
    {
        $class = $bundleName . '\Entity\\' . $className;

        return new $class;
    }

    /**
     * @param string $type
     * @return object
     */
    protected function getHydrator($type)
    {
        $class = 'JsonApiBundle\\' . $type . '\\Hydrator';

        return new $class($this->entityManager);
    }

    /**
     * @param Hydrator $hydrator
     * @param object $object
     * @param array $requestAttributes
     */
    protected function setValuesInHydrator($hydrator, $object, $requestAttributes)
    {
        foreach ($hydrator->getAttributes() as $fieldName) {
            if (array_key_exists($fieldName, $requestAttributes)) {

                $hydrator->setParamsToObject($object, $requestAttributes, $hydrator->getAttributes());
            }
        }
    }

    /**
     * @param array $errors
     * @param object $validateObject
     * @param string $errorName
     * @param string $source
     */
    protected function setErrors(&$errors, $validateObject, $errorName, $source)
    {
        $violations = $this->validator->validate($validateObject);

        if (count($violations) !== 0) {
            /** @var ConstraintViolation $violation */
            foreach ($violations as $violation) {
                $errors[] = $this->jsonApiError->getErrorObjectByErrorName(
                    $errorName,
                    $violation->getMessage(),
                    ['source' => $source . $violation->getPropertyPath()]
                );
            }
        }
    }

    /**
     * @param array $errors
     * @param string $type
     * @param string $bundleName
     * @param array $requestAttributes
     */
    protected function getMainEntityErrors(&$errors, $type, $bundleName, $requestAttributes)
    {
        /** @var Hydrator $hydrator */
        $hydrator = $this->getHydrator($type);
        $object = $this->getEntity($bundleName, $type);

        $this->setValuesInHydrator($hydrator, $object, $requestAttributes);
        $this->setErrors($errors, $object, 'badRequest', 'data/attributes/');
    }

    /**
     * @param array $errors
     * @param string $bundleName
     * @param array $relationAttributes
     * @return array|void
     */
    protected function getRelationEntitiesErrors(&$errors, $bundleName, $relationAttributes)
    {
        foreach ($relationAttributes as $type => $data) {
            $dataRows = $relationAttributes[$type]['data'];

            if (isset($dataRows['type'])) {
                $dataType = $this->jsonApiRequest->getClassNameByType($dataRows['type']);
                $hydrator = $this->getHydrator($dataType);
                $entity = $this->getEntity($bundleName, $dataType);
                unset($dataRows['type']);

                $this->setValuesInHydrator($hydrator, $entity, $dataRows);
                $this->setErrors($errors, $entity, 'badRequest', 'data/relations/');

                return;
            }

            /* if in relationships data more then one rows */
            foreach ($dataRows as $attributes) {
                $dataType = $this->jsonApiRequest->getClassNameByType($attributes['type']);
                $hydrator = $this->getHydrator($dataType);
                $entity = $this->getEntity($bundleName, $dataType);
                unset($attributes['type']);

                $this->setValuesInHydrator($hydrator, $entity, $attributes);
                $this->setErrors($errors, $entity, 'badRequest', 'data/relations/');
            }
        }
    }

    /**
     * @param string $bundleName
     * @param array $requestAttributes
     * @param array $relationAttributes
     * @param string $type
     * @return bool|string
     */
    public function validate($bundleName, $requestAttributes, $relationAttributes, $type)
    {
        $errors = [];

        $this->getMainEntityErrors($errors, $type, $bundleName, $requestAttributes);
        $this->getRelationEntitiesErrors($errors, $bundleName, $relationAttributes);

        return (!empty($errors)) ? Encoder::instance()->encodeErrors($errors) : true;
    }
}