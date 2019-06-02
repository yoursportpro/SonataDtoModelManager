# SonataDtoModelManager

[![Latest Stable Version](https://poser.pugx.org/jarjobs/sonatadtomodelmanager/v/stable)](https://packagist.org/packages/jarjobs/sonatadtomodelmanager)
[![Total Downloads](https://poser.pugx.org/jarjobs/sonatadtomodelmanager/downloads)](https://packagist.org/packages/jarjobs/sonatadtomodelmanager)
[![Latest Unstable Version](https://poser.pugx.org/jarjobs/sonatadtomodelmanager/v/unstable)](https://packagist.org/packages/jarjobs/sonatadtomodelmanager)
[![License](https://poser.pugx.org/jarjobs/sonatadtomodelmanager/license)](https://packagist.org/packages/jarjobs/sonatadtomodelmanager)

## Installation and usage

### Installation
To install SonataDtoModelManager run:

```sh
composer require "jarjobs/sonatadtomodelmanager:^1.0"
``` 

### Usage

1. Add in `calls` section record with `setModelManager` in sonata.admin service definitions. 
The `setModelManager` argument should be your inheritance of `AbstractDtoModelManager` from this bundle.

    Example:
    ```yaml
      admin.example.entity:
        class: App\Example\Admin\ExampleAdmin
        arguments: [~, App\Example\Entity\Entity, ~]
        tags:
        - name: sonata.admin
          manager_type: orm
          label: "example label"
        calls:
          - [setModelManager, ['@App\Admin\Example\Model\ExampleModelManager']]
    ```

2. Create class which inherit `JarJobs\SonataDtoModelManager\Model\AbstractDtoModelManager`

    Example:
    ```php
    <?php
    declare(strict_types=1);
    
    namespace App\Admin\Example\Model;
    
    use JarJobs\SonataDtoModelManager\Model\AbstractDtoModelManager;
    use App\Example\Entity\Entity;
    use App\Admin\Example\Dto\ExampleDto;
    
    final class ExampleModelManager extends AbstractDtoModelManager
    {    
        protected function getSubjectClass(): string
        {
            return Entity::class;
        }
    
        protected function doCreate($dto)
        {
            // Here: create Entity based on submitted data from form as dto
        }
    
        protected function doUpdate($dto, $entity)
        {
            // Here: update $entity with validated data form in $dto
            // $entity is here for doctrine reference
        }
    
        protected function doGetModelInstance($class)
        {
            // Here: return clear dto for form
            return new ExampleDto();
        }
    
        protected function buildDto($entity)
        {
            // Here: for load form with data to update action, fill dto based on entity data 
        }
    }
    ```
    
3. Create DTO class with form fields which you wanna update in entity

    Example:
    ```php
   <?php
       declare(strict_types=1);
        
       namespace App\Admin\Example\Dto;
    
       final class ExampleDto
       {    
           private $name;
           private $city;
        
           public function getName()
           {
               return $this->name;
           }
        
           // ...
       }
    ```

That is it! After all, your form is based on DTO instead of Entity. 
Benefit? Entity can be always in proper state without nullable getter methods.
