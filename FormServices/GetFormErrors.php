<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace NoahGlaser\ValidationBundle\FormServices;

use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormErrorIterator;

/**
 * Description of ProcessForm
 *
 * @author Owner
 */
class GetFormErrors
{
    private $translator;
    
    public function __construct(Translator $trans) 
    {
        $this->translator = $trans;
    }
    
    public function getAllFormErrors(Form $form)
    {        
        return $this->getFormFieldErrors($form->getErrors(true, false));;
    }
    
    
    private function getFormFieldErrors(FormErrorIterator $errorIterator, $errors = array())
    {
      
        $current = $errorIterator->current();
        
        while($current != false)
        {
            if($current instanceof  FormError)
            {      
               $errors[$this->getFormName($errorIterator->getForm())][] = $this->translator->trans($current->getMessage());               
            }
            elseif($current instanceof FormErrorIterator)
            {
                $errors = $this->getFormFieldErrors($current, $errors);
            }
                
            $errorIterator->next();
                     
            $current = $errorIterator->current();

        }
        
        return $errors;
    }
    
    public function getFormName(Form $form)
    {
        $name_array = array();
        
        while($form->isRoot() == false)
        {
            $name_array[] = $form->getName();
            $form = $form->getParent();
        }
        
        $name_array[] = $form->getName();
        return implode('_',  array_reverse($name_array));
    }
}
