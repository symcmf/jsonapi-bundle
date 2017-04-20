<?php

namespace JsonApiBundle\Listeners;

use JsonApiBundle\Controller\BaseController;
use JsonApiBundle\Request\JSONApiRequest;
use JsonApiBundle\Services\Validator\Validator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

class ControllerListener
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * RequestListener constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container) // this is @service_container
    {
        $this->container = $container;
    }

    /**
     * @param FilterControllerEvent $event
     * @return Response|void
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $controller = $event->getController();

        /*
         * $controller passed can be either a class or a Closure.
         * This is not usual in Symfony but it may happen.
         * If it is a class, it comes in array format
         */
        if (!is_array($controller)) {
            return;
        }

        $controller = array_shift($controller);

        if ($controller instanceof BaseController) {

            /** @var JSONApiRequest $jsonApiRequest */
            $jsonApiRequest =  $this->container->get('jsonapi.request');

            /** @var Validator $validator */
            $validator = $this->container->get('jsonapi.validator');

            $bundleName = explode('\\', get_class($controller))[0];

            $result = $validator->validate(
                $bundleName,
                $jsonApiRequest->getDataAttributes(),
                $jsonApiRequest->getRelationSection(),
                $jsonApiRequest->getClassNameByType($jsonApiRequest->getDataSection()['type'])
            );

            if ($result !== true) {

                $request = new Request();
                $resolver =  $this->container->get('debug.controller_resolver');
                $request->attributes->set('_controller', 'JsonApiBundle:Error:responseError');

                $event->getRequest()->attributes->set('_errors', $result);
                $event->setController($resolver->getController($request));
            }
        }
    }
}
