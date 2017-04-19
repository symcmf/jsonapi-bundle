<?php

namespace JsonApiBundle\Services;

use Symfony\Component\HttpFoundation\Response;

class JSONApiResponse
{
    /**
     * @param $content
     * @param $code
     *
     * @return Response
     */
    public function createResponse($content, $code)
    {
        $response = new Response();

        $response->setContent($content);
        $response->setStatusCode($code);

        $response->headers->set('Content-Type', 'application/vnd.api+json');

        return $response;
    }
}
