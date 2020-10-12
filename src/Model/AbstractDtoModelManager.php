<?php
declare(strict_types=1);

namespace JarJobs\SonataDtoModelManager\Model;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityRepository;
use Sonata\AdminBundle\Exception\ModelManagerException;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery;
use Sonata\DoctrineORMAdminBundle\Model\ModelManager;

abstract class AbstractDtoModelManager extends ModelManager
{
    /**
     * Returns Entity class name
     */
    abstract protected function getSubjectClass(): string;

    /**
     * Should build Entity for update from DTO
     */
    abstract protected function doUpdate($dto, $entity);

    /**
     * Should build Entity for create from DTO
     */
    abstract protected function doCreate($dto);

    /**
     * Returns clear DTO for creating new Entity
     */
    abstract protected function doGetModelInstance($class);

    /**
     * Returns built DTO from existing Entity
     */
    abstract protected function buildDto($entity);

    public function create($object): void
    {
        try {
            $instance = $this->doCreate($object);

            $this->getEntityManager($object)->persist($instance);
            $this->getEntityManager($object)->flush();

            $object->setId($instance->getId());
        } catch (\PDOException $e) {
            throw new ModelManagerException(
                sprintf('Failed to create object: %s', ClassUtils::getClass($object)),
                $e->getCode(),
                $e
            );
        } catch (DBALException $e) {
            throw new ModelManagerException(
                sprintf('Failed to create object: %s', ClassUtils::getClass($object)),
                $e->getCode(),
                $e
            );
        }
    }

    public function update($object): void
    {
        try {
            // TODO: if $object->getId() is null

            $entity = $this->getRepository()->find($object->getId());
            if (is_null($entity)) {
                throw new \RuntimeException(sprintf('Unable to find object to update with id: %d', $object->getId()));
            }
            $instance = $this->doUpdate($object, $entity);

            $this->getEntityManager($object)->persist($instance);
            $this->getEntityManager($object)->flush();
        } catch (\PDOException $e) {
            throw new ModelManagerException(
                sprintf('Failed to update object: %s', ClassUtils::getClass($object)),
                $e->getCode(),
                $e
            );
        } catch (DBALException $e) {
            throw new ModelManagerException(
                sprintf('Failed to update object: %s', ClassUtils::getClass($object)),
                $e->getCode(),
                $e
            );
        }
    }

    public function getModelInstance($class)
    {
        return $this->doGetModelInstance($class);
    }

    public function getExportFields($class): array
    {
        $metadata = $this->getEntityManager($this->getSubjectClass())->getClassMetadata($this->getSubjectClass());

        return $metadata->getFieldNames();
    }

    public function getEntityManager($class): ObjectManager
    {
        return parent::getEntityManager($this->getSubjectClass());
    }

    public function getMetadata($class): ClassMetadata
    {
        return parent::getMetadata($this->getSubjectClass());
    }

    public function getParentMetadataForProperty($baseClass, $propertyFullName): array
    {
        return parent::getParentMetadataForProperty($this->getSubjectClass(), $propertyFullName);
    }

    public function hasMetadata($class): bool
    {
        return $this->getEntityManager($this->getSubjectClass())->getMetadataFactory()->hasMetadataFor($this->getSubjectClass());
    }

    public function createQuery($class, $alias = 'o'): ProxyQuery
    {
        $repository = $this
            ->getEntityManager($this->getSubjectClass())
            ->getRepository($this->getSubjectClass());

        if (!$repository instanceof EntityRepository) {
            throw new \LogicException('AbstractDtoManager works with EntityManager only');
        }

        return new ProxyQuery($repository->createQueryBuilder($alias));
    }

    public function find($class, $id)
    {
        if (empty($id)) {
            return null;
        }
        $values = \array_combine($this->getIdentifierFieldNames($class), explode(self::ID_SEPARATOR, $id));

        $entity = $this->getRepository()->find($values);

        return $this->buildDto($entity);
    }

    public function findBy($class, array $criteria = []): array
    {
        return $this->getRepository()->findBy($criteria);
    }

    public function findOneBy($class, array $criteria = [])
    {
        return $this->getRepository()->findOneBy($criteria);
    }

    private function getRepository(): EntityRepository
    {
        $repository = $this
            ->getEntityManager($this->getSubjectClass())
            ->getRepository($this->getSubjectClass());

        if (!$repository instanceof EntityRepository) {
            throw new \LogicException('AbstractDtoModelManager works with EntityManager only');
        }

        return $repository;
    }

    public function getNormalizedIdentifier($entity)
    {
        if (is_scalar($entity)) {
            throw new \RuntimeException('Invalid argument, object or null required');
        }

        if (!$entity || is_array($entity) || is_null($entity->getId())) {
            return;
        }

        $values = [$entity->getId()];

        if (count($values) === 0) {
            return;
        }

        return implode(self::ID_SEPARATOR, $values);
    }
}
