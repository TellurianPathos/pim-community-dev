<?php

namespace Pim\Bundle\VersioningBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Pim\Bundle\VersioningBundle\Model\Versionable;
use Pim\Bundle\VersioningBundle\Entity\Version;
use Pim\Bundle\ProductBundle\Entity\Family;
use Pim\Bundle\ProductBundle\Model\ProductValueInterface;
use Pim\Bundle\ProductBundle\Model\ProductInterface;

/**
 * Aims to audit data updates on product, attribute, family, category
 *
 * @author    Nicolas Dupont <nicolas@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/MIT MIT
 */
class AddVersionListener implements EventSubscriber
{
    /**
     * @var ContainerInterface $container
     */
    protected $container;

    /**
     * Inject service container
     *
     * @param ContainerInterface $container
     *
     * @return ScopableListener
     */
    public function setContainer($container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Specifies the list of events to listen
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
                /*
            'prePersist',
            'preUpdate',
            */
            'onFlush'
        );
    }

    /**
     * Before insert
     *
     * @param LifecycleEventArgs $args
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        if ($entity instanceof ProductInterface) {
//die('toto');
        }
    }

    /**
     * Before insert
     *
     * @param LifecycleEventArgs $args
     */
    public function preUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        if ($entity instanceof Family) {
            //die('titi');
        }
    }

    /**
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() AS $entity) {
            if ($entity instanceof Versionable) {
                $this->_makeSnapshot($em, $entity);
            }
        }

        foreach ($uow->getScheduledEntityUpdates() AS $entity) {
            if ($entity instanceof Versionable) {
                $this->_makeSnapshot($em, $entity);
            }
        }
    }

    /**
     * @param EntityManager        $em
     * @param VersionableInterface $entity
     */
    private function _makeSnapshot(EntityManager $em, Versionable $entity)
    {
        $resourceVersion = new Version($entity);
        $class = $em->getClassMetadata(get_class($resourceVersion));
        $em->persist($resourceVersion);
        $em->getUnitOfWork()->computeChangeSet($class, $resourceVersion);
    }
}
