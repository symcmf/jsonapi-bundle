<?php

namespace JsonApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ErrorController
 * @package JsonApiBundle\Controller
 */
class ErrorController extends Controller
{
    /**
     * @param Request $request
     *
     * @return Response
     */
    public function responseErrorAction(Request $request)
    {
        return $this->get('jsonapi.response')->createResponse(
            $request->attributes->get('_errors'),
            Response::HTTP_BAD_REQUEST
        );
    }
}
