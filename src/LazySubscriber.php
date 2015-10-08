<?php


namespace Dnoegel\LazySubscriber;

use Enlight\Event\SubscriberInterface;
use Shopware\Components\DependencyInjection\Container as DIC;

/**
 * Class LazySubscriber is a convenient base subscriber for the Shopware SubscriberInterface. Just extend it
 * and implement the define method returning an array with service names mapped to callback function.
 * When the service is requested through the Shopware DI container, the callback will be executed
 *
 * @package Dnoegel\LazySubscriber
 */
abstract class LazySubscriber implements SubscriberInterface
{
    private static $container;
    private static $definitions;

    public function __construct(DIC $container)
    {
        self::$container = $container;
        self::$definitions = $this->define();
    }

    /**
     * return an array with your services and a callback function
     *
     * array(
     *  'my_plugin.my_service' => function($c) {
     *          return new MyService($c->get('db'));
     *      }
     *  )
     *
     * @return array
     */
    static function define() {
        return [];
    }


    /**
     * Generate the subscribedEvents array depending on the $container static property
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        $events = array();

        foreach (static::define() as $name => $function) {
            $events['Enlight_Bootstrap_InitResource_' . $name] = 'load';
        }

        return $events;
    }

    /**
     * Generic callback function for all registered subscribers in this class. Will dispatch the event to
     * the anonymous function of the corresponding service
     *
     * @param \Enlight_Event_EventArgs $args
     * @return mixed
     */
    public function load(\Enlight_Event_EventArgs $args)
    {
        // get registered service from event name
        $name = str_replace('Enlight_Bootstrap_InitResource_', '', $args->getName());

        // call anonymous function in order to register service
        $method = self::$definitions[$name];
        if (!$method) {
            throw new \RuntimeException("Service named $name not found");
        }
        return $method(self::$container, $args);
    }
}
