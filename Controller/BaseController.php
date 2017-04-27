<?php

namespace JsonApiBundle\Controller;

use JsonApiBundle\Request\JSONApiRequest;
use JsonApiBundle\Services\BaseJSONApiBundle;
use JsonApiBundle\Services\JSONApiError;
use JsonApiBundle\Services\Validator\Validator;
use Neomerx\JsonApi\Document\Error;
use Neomerx\JsonApi\Document\Link;
use Neomerx\JsonApi\Encoder\Encoder;
use Neomerx\JsonApi\Encoder\Parameters\EncodingParameters;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class BaseController
 * @package JsonApiBundle\Controller
 */
abstract class BaseController extends Controller
{
    protected $link = 'http://homestead.app/api';

    /**
     * @return string
     */
    abstract public function getClass();

    /**
     * @return mixed
     */
    abstract protected function getEncoder();

    /**
     * @return object
     */
    abstract protected function getHydrator();

    /**
     * @return BaseJSONApiBundle
     */
    protected function getBaseService()
    {
        return $this->get('jsonapi.base.service');
    }

    /**
     * @return JSONApiRequest
     */
    protected function getJsonRequest()
    {
        return $this->get('jsonapi.request');
    }

    /**
     * @return EncodingParameters
     */
    private function getEncodingParameters()
    {
        $params = new EncodingParameters(
            $this->getJsonRequest()->getIncludeAttributes(),
            $this->getJsonRequest()->getSparseFieldAttributes(),
            $this->getJsonRequest()->getSortAttributes(),
            $this->getJsonRequest()->getPaginationAttributes()
        );

        return $params;
    }

    /**
     * @param $content
     * @param $code
     *
     * @return Response
     */
    protected function createResponse($content, $code)
    {
        $response = new Response();

        $response->setContent($content);
        $response->setStatusCode($code);

        $response->headers->set('Content-Type', 'application/vnd.api+json');

        return $response;
    }

    /**
     * @param $object
     *
     * @return mixed
     */
    protected function viewObject($object)
    {
        return $this->getEncoder()->encodeData($object, $this->getEncodingParameters());
    }

    /**
     * @param $page
     * @param $total
     * @param $object
     *
     * @return Response
     */
    private function viewObjectWithPaginateLink($page, $total, $object)
    {
        $nextPage = $page + 1;
        $prevPage = ($page > 1) ? $page - 1 : $page;

        $type = $this->getJsonRequest()->getType();

        $pages = [
            Link::PREV  => new Link('/' . $type . '?page[number]=' . $prevPage . '&page[size]=' . $total),
            Link::NEXT  => new Link('/' . $type . '?page[number]=' . $nextPage . '&page[size]=' . $total),
        ];

        $view = $this->getEncoder()
            ->withLinks($pages)
            ->encodeData($object, $this->getEncodingParameters());

        return $this->createResponse($view, Response::HTTP_OK);
    }

    /**
     *
     * @return Response
     */
    protected function getList()
    {
        $pagination = $this->getJsonRequest()->getPaginationAttributes();

        $objects = $this->getBaseService()->getQuery($this->getClass(), $this->getOrderByParams(), $pagination);

        if (empty($pagination)) {
            return $this->createResponse($this->viewObject($objects), Response::HTTP_OK);
        }

        return $this->viewObjectWithPaginateLink(
            $pagination['number'],
            $pagination['size'],
            $objects);
    }

    /**
     * @param $id
     *
     * @return Response
     */
    protected function getEntity($id)
    {
        $object = $this->getBaseService()->getObject($id, $this->getClass());

        return $this->createResponse($this->viewObject($object), Response::HTTP_OK);
    }

    /**
     * @return array
     */
    private function getOrderByParams()
    {
        $sortAttributes = $this->getJsonRequest()->getSortAttributes();

        if(empty(current($sortAttributes))) return [];

        $orderBy = [];
        foreach ($sortAttributes as $attribute) {
            $direction = 'ASC';
            if ($attribute{0} == '-') {
                $direction = 'DESC';
                $attribute = substr($attribute, 1);
            }
            $orderBy[$attribute] = $direction;
        }

        return $orderBy;
    }

    /**
     * @param $data - json api array (data section)
     *
     * @return string
     */
    private function checkIdField($data)
    {
        // TODO need to user translations for errors

        if (array_key_exists('id', $data)) {
            /** @var JSONApiError $jsonApiError */
            $jsonApiError = $this->get('jsonapi.error');
            $error = $jsonApiError->getErrorObjectByErrorName(
                'forbidden',
                'Unsupported request to create a resource with a client-generated ID',
                ['source' => 'data/id'],
                'Unsupported request'
            );

            return Encoder::instance()->encodeError($error);
        }
    }

    /**
     * @return Response
     */
    protected function postEntity()
    {
        $errorEncoder = $this->checkIdField($this->getJsonRequest()->getDataSection());

        // if catch error
        if ($errorEncoder) {
            return $this->createResponse($errorEncoder, Response::HTTP_FORBIDDEN);
        }

        $object = $this->getHydrator()->setValues(
            $this->getJsonRequest()->getDataAttributes(),
            $this->getJsonRequest()->getRelationSection());

        // TODO uncomment after debug
//        $this->get('jsonapi.base.service')->saveObject($object);

        return $this->createResponse($this->viewObject($object), Response::HTTP_CREATED);
    }

    /**
     * @param $id
     *
     * @return Response
     */
    protected function putEntity($id)
    {
        if (!$this->getBaseService()->getObject($id, $this->getClass())) {
            $this->createResponse($this->viewObject([]), Response::HTTP_NOT_FOUND);
        }

        $object = $this->getHydrator()->updateValues(
            $this->getJsonRequest()->getDataSection(),
            $this->getJsonRequest()->getRelationSection()
        );
        // TODO uncomment after debug
//        $this->getBaseService()->updateObject($object);

        return $this->createResponse($this->viewObject($object), Response::HTTP_OK);
    }
}
