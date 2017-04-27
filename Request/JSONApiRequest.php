<?php

namespace JsonApiBundle\Request;

use ICanBoogie\Inflector;
use Symfony\Component\HttpFoundation\RequestStack;

class JSONApiRequest
{
    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var string
     */
    private $contentType = 'application/vnd.api+json';

    /**
     * TestRequest constructor.
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @return null|\Symfony\Component\HttpFoundation\Request
     */
    public function getRequest()
    {
        return $this->requestStack->getCurrentRequest();
    }

    /**
     * @param $separator
     * @param $request
     *
     * @return array
     */
    private function getArraySeparator($separator, $request)
    {
        $requestString = str_replace(' ', '', $request);
        return explode($separator, $requestString);
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->getRequest()->getMethod();
    }

    /**
     * Return attributes for sort action
     *
     * @return array
     */
    public function getSortAttributes()
    {
        return $this->getArraySeparator(',', $this->getRequest()->query->get('sort'));
    }

    /**
     * @return array
     */
    public function getSparseFieldAttributes()
    {
        $result = [];
        $fields = $this->getRequest()->query->get('fields');

        if ($fields) {
            foreach ($fields as $key => $value) {
                $result[$key] = $this->getArraySeparator(',', $value);
            }
        }
        return $result;
    }

    /**
     * @return array
     */
    public function getIncludeAttributes()
    {
        return $this->getArraySeparator(',', $this->getRequest()->query->get('include'));
    }

    /**
     * @return array
     */
    public function getPaginationAttributes()
    {
        return $this->getRequest()->query->get('page');
    }

    /**
     * @return array
     */
    private function parseJson()
    {
        $data = [];

        if ($this->getRequest()->getMethod() === 'POST' ||
            $this->getRequest()->getMethod() === 'PUT'
        ) {

            if ($this->getRequest()->headers->get('content-type') == $this->contentType) {
                $data = json_decode($this->getRequest()->getContent(), true);
                // TODO check correct format of request json
            }
        }

        return $data;
    }

    /**
     * @return array
     */
    public function getDataSection()
    {
        $data = $this->parseJson();
        return (!empty($data)) ? $data['data'] : [];
    }

    /**
     * @param $key
     *
     * @return array
     */
    public function parseDataSectionByKey($key)
    {
        $data = $this->parseJson();

        if (array_key_exists($key, $data['data'])) {
            return (!empty($data)) ? $data['data'][$key] : [];
        }

        return [];
    }

    /**
     * @return array
     */
    public function getDataAttributes()
    {
        return $this->parseDataSectionByKey('attributes');
    }

    /**
     * @return array
     */
    public function getRelationSection()
    {
        return $this->parseDataSectionByKey('relationships');
    }

    /**
     * @return string
     */
    public function getClassNameByType($type)
    {
        $invector = Inflector::get(Inflector::DEFAULT_LOCALE);
        return $invector->singularize($invector->camelize($type, Inflector::UPCASE_FIRST_LETTER));
    }

    /**
     * @return array|string
     */
    public function getType()
    {
        // if method get we can get type from url
        if ($this->getRequest()->getMethod() === 'GET') {
            $array = explode('/', $this->getRequest()->getPathInfo());
            return end($array);
        } else {
            // in another situation it must be in body of request (methods POST | PUT)
            return $this->parseDataSectionByKey('type');
        }
    }
}
