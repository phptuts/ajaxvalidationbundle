<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of GetFormErrorsTest
 *
 * @author Owner
 */
namespace NoahGlaser\ValidationBundle\Tests\FormServices;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Form\Form;
use NoahGlaser\ValidationBundle\FormServices\GetFormErrors;

class GetFormErrorsTest extends WebTestCase
{
    protected static $container;
    
    public static function setUpBeforeClass()
    {
         //start the symfony kernel
         $kernel = static::createKernel();
         $kernel->boot();

         //get the DI container
         self::$container = $kernel->getContainer();

         //now we can instantiate our service (if you want a fresh one for
         //each test method, do this in setUp() instead
    }
    
  

    
    public function helperCreateRootForm($name)
    {
       $form_root = $this->getMockBuilder('Symfony\Component\Form\Form')
                ->disableOriginalConstructor()->getMock();
       $form_root->expects($this->any())->method('getName')->willReturn($name);
       $form_root->expects($this->any())->method('isRoot')->willReturn(true);
       return $form_root;
    }
    
    public function helperCreateNonRootForm($name, $parentform)
    {
       $form = $this->getMockBuilder('Symfony\Component\Form\Form')
                ->disableOriginalConstructor()->getMock();
       $form->expects($this->any())->method('getName')->willReturn($name);
       $form->expects($this->any())->method('isRoot')->willReturn(false);
       $form->expects($this->any())->method('getParent')->willReturn($parentform);

       return $form;
    }
    
    public function testGetFormNameReturnUnderscoredName()
    {
       $rootform = $this->helperCreateRootForm('form');
       $firstname = $this->helperCreateNonRootForm('firstname', $rootform);
       $formerror = self::$container->get('noahglaser.validation.formservices.getformerrors');

       $formname = $formerror->getFormName($firstname);
       $this->assertEquals('form_firstname',$formname, 'Form name should be separated by _ because that matches how symfony creates the id field.');
    }
    
    
    public function testCollectionFieldReturnUnderscoreWithName()
    {
       
        $rootform = $this->helperCreateRootForm('form');
        $shirtCollection = $this->helperCreateNonRootForm('shirts', $rootform);
        $shirtrow = $this->helperCreateNonRootForm('0', $shirtCollection);
        $color = $this->helperCreateNonRootForm('color', $shirtrow);
        $formerror = self::$container->get('noahglaser.validation.formservices.getformerrors');

        $formname = $formerror->getFormName($color);
        $this->assertEquals('form_shirts_0_color',$formname, 'Form name should be separated by _ because that matches how symfony creates the id field.');        
    }
   
    public function testOneFieldHasErrorInForm()
    {
        $rootform = $this->helperCreateRootForm('form');
        $firstname = $this->helperCreateNonRootForm('firstname', $rootform);
        $firstNameError = $this->getMockBuilder('Symfony\Component\Form\FormError')
                                ->disableOriginalConstructor()
                                ->getMock();
        $firstNameError->expects($this->once())->method('getMessage')->willReturn("First Name Can Not Be Blank");
        
        $firstNameErrorIterator = $this->getMockBuilder('Symfony\Component\Form\FormErrorIterator')
                                  ->disableOriginalConstructor()
                                  ->getMock();
        
        $firstNameErrorIterator->expects($this->at(0))->method('current')->will($this->returnValue($firstNameError));
        $firstNameErrorIterator->expects($this->at(1))->method('getForm')->willReturn($firstname);
        $firstNameErrorIterator->expects($this->at(2))->method('next')->willReturn(null);
        $firstNameErrorIterator->expects($this->at(3))->method('current')->willReturn($this->equalTo(false));

        $formErrorIterator = $this->getMockBuilder('Symfony\Component\Form\FormErrorIterator')
                                  ->disableOriginalConstructor()
                                  ->getMock();
        $formErrorIterator->expects($this->at(0))->method('current')->willReturn($firstNameErrorIterator);
        $formErrorIterator->expects($this->at(1))->method('next')->willReturn(null);
        $formErrorIterator->expects($this->at(2))->method('current')->willReturn(false);


        $rootform->expects($this->once())
                 ->method('getErrors')
                 ->with($this->equalTo(true), $this->equalTo(false))
                 ->willReturn($formErrorIterator);
                
        $formerror = self::$container->get('noahglaser.validation.formservices.getformerrors');

        $errorArray = $formerror->getAllFormErrors($rootform);
        
        $this->assertEquals(['form_firstname' => ['First Name Can Not Be Blank']], $errorArray);
    }
    
    public function testFieldWithMultipleErrors()
    {
                
        $rootform = $this->helperCreateRootForm('form');
        $firstname = $this->helperCreateNonRootForm('firstname', $rootform);
        $firstNameBlank = $this->getMockBuilder('Symfony\Component\Form\FormError')
                                ->disableOriginalConstructor()
                                ->getMock();
        $firstNameBlank->expects($this->any())->method('getMessage')->willReturn("First Name Can Not Be Blank");
        
        $firstNameLength = $this->getMockBuilder('Symfony\Component\Form\FormError')
                                ->disableOriginalConstructor()
                                ->getMock();
        $firstNameLength->expects($this->any())->method('getMessage')->willReturn("First Name Must Be 5 Characters Long.");

        $firstNameErrorIterator = $this->getMockBuilder('Symfony\Component\Form\FormErrorIterator')
                                  ->disableOriginalConstructor()
                                  ->getMock();
        
        $firstNameErrorIterator->expects($this->at(0))->method('current')->will($this->returnValue($firstNameBlank));
        $firstNameErrorIterator->expects($this->at(1))->method('getForm')->willReturn($firstname);
        $firstNameErrorIterator->expects($this->at(2))->method('next')->willReturn(null);
        $firstNameErrorIterator->expects($this->at(3))->method('current')->will($this->returnValue($firstNameLength));
        $firstNameErrorIterator->expects($this->at(4))->method('getForm')->willReturn($firstname);
        $firstNameErrorIterator->expects($this->at(5))->method('next')->willReturn(null);
        $firstNameErrorIterator->expects($this->at(6))->method('current')->willReturn($this->equalTo(false));
        $firstNameErrorIterator->expects($this->atLeast(3))->method('current');
        
        $formErrorIterator = $this->getMockBuilder('Symfony\Component\Form\FormErrorIterator')
                                  ->disableOriginalConstructor()
                                  ->getMock();
        $formErrorIterator->expects($this->at(0))->method('current')->willReturn($firstNameErrorIterator);
        $formErrorIterator->expects($this->at(1))->method('next')->willReturn(null);
        $formErrorIterator->expects($this->at(2))->method('current')->willReturn(false);


        $rootform->expects($this->once())
                 ->method('getErrors')
                 ->with($this->equalTo(true), $this->equalTo(false))
                 ->willReturn($formErrorIterator);
              
        $formerror = self::$container->get('noahglaser.validation.formservices.getformerrors');

        
        $errorArray = $formerror->getAllFormErrors($rootform);
        $this->assertEquals(['form_firstname' => ['First Name Can Not Be Blank', 'First Name Must Be 5 Characters Long.']], $errorArray);

    }
    
    public function testMulipleFieldsWithMultipleErrors()
    {
               
        $rootform = $this->helperCreateRootForm('form');
        $firstname = $this->helperCreateNonRootForm('firstname', $rootform);
        
        
        $firstNameBlank = $this->getMockBuilder('Symfony\Component\Form\FormError')
                                ->disableOriginalConstructor()
                                ->getMock();
        $firstNameBlank->expects($this->any())->method('getMessage')->willReturn("First Name Can Not Be Blank");
        
        $firstNameLength = $this->getMockBuilder('Symfony\Component\Form\FormError')
                                ->disableOriginalConstructor()
                                ->getMock();
        $firstNameLength->expects($this->any())->method('getMessage')->willReturn("First Name Must Be 5 Characters Long.");

        $firstNameErrorIterator = $this->getMockBuilder('Symfony\Component\Form\FormErrorIterator')
                                  ->disableOriginalConstructor()
                                  ->getMock();
        
        $firstNameErrorIterator->expects($this->at(0))->method('current')->will($this->returnValue($firstNameBlank));
        $firstNameErrorIterator->expects($this->at(1))->method('getForm')->willReturn($firstname);
        $firstNameErrorIterator->expects($this->at(2))->method('next')->willReturn(null);
        $firstNameErrorIterator->expects($this->at(3))->method('current')->will($this->returnValue($firstNameLength));
        $firstNameErrorIterator->expects($this->at(4))->method('getForm')->willReturn($firstname);
        $firstNameErrorIterator->expects($this->at(5))->method('next')->willReturn(null);
        $firstNameErrorIterator->expects($this->at(6))->method('current')->willReturn($this->equalTo(false));
        $firstNameErrorIterator->expects($this->atLeast(3))->method('current');
        
        
        $lastname  = $this->helperCreateNonRootForm('lastname', $rootform);
        
        $lastNameBlank = $this->getMockBuilder('Symfony\Component\Form\FormError')
                                ->disableOriginalConstructor()
                                ->getMock();
        
        $lastNameErrorIterator = $this->getMockBuilder('Symfony\Component\Form\FormErrorIterator')
                                  ->disableOriginalConstructor()
                                  ->getMock();
        
        $lastNameErrorIterator->expects($this->at(0))->method('current')->will($this->returnValue($lastNameBlank));
        $lastNameErrorIterator->expects($this->at(1))->method('getForm')->willReturn($lastname);
        $lastNameErrorIterator->expects($this->at(2))->method('next')->willReturn(null);
        $lastNameErrorIterator->expects($this->at(3))->method('current')->willReturn($this->equalTo(false));

        
        $lastNameBlank->expects($this->any())->method('getMessage')->willReturn("Last Name Can Not Be Blank");

        
        $formErrorIterator = $this->getMockBuilder('Symfony\Component\Form\FormErrorIterator')
                                  ->disableOriginalConstructor()
                                  ->getMock();
        $formErrorIterator->expects($this->at(0))->method('current')->willReturn($firstNameErrorIterator);
        $formErrorIterator->expects($this->at(1))->method('next')->willReturn(null);
        $formErrorIterator->expects($this->at(2))->method('current')->willReturn($lastNameErrorIterator);
        $formErrorIterator->expects($this->at(3))->method('next')->willReturn(null);
        $formErrorIterator->expects($this->at(4))->method('current')->willReturn(false);


        $rootform->expects($this->once())
                 ->method('getErrors')
                 ->with($this->equalTo(true), $this->equalTo(false))
                 ->willReturn($formErrorIterator);
              
        $formerror = self::$container->get('noahglaser.validation.formservices.getformerrors');

        
        $errorArray = $formerror->getAllFormErrors($rootform);

        $this->assertEquals(['form_firstname' => ['First Name Can Not Be Blank', 'First Name Must Be 5 Characters Long.'], 'form_lastname' => ['Last Name Can Not Be Blank']], $errorArray);

    }
}
