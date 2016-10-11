<?php

namespace NoahGlaser\ValidationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AjaxController extends Controller
{
    /**
     * @param Request $request
     * @param $serviceName
     * @return JsonResponse
     */
    public function validateAction(Request $request, $serviceName)
    {
      $formClassName = get_class($this->get($serviceName));
      if($request->query->has('id'))
      {
          $classname = $form->getConfig()->getDataClass();
          $entity  = $this->getDoctrine()->getManager()->getRepository($classname)->findOneBy(array('id' => $request->query->get('id')));
          $form = $this->createForm($serviceName, $entity); 
      }
      
      $form->handleRequest($request);
      
      if($form->isValid())
      {
            return new JsonResponse(array('success' => true, 'hasError' => false));
      }
      else
      {
           $errors =  $this->get('noahglaser.validation.formservices.getformerrors')->getAllFormErrors($form);
           $ret = [];
           $ret['errors'] = $errors;
           $ret['hasError'] = true;
           $ret['success'] = true;
           return new JsonResponse($ret);
      }

    }
}
