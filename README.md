# LazySubscriber
The Shopware `SubscriberInterface` allows you to define subscribers in order to subscribe to Shopware events easily.
The `SubscriberInterface`, however, does not support anonymous function as callbacks, basically because Shopware tries to
be forward compatible to the [Symfony `EventDispatcher`](https://github.com/symfony/EventDispatcher/blob/master/EventDispatcher.php#L121).

The `LazySubscriber` will provide you a simple way to define your callbacks as anonymous functions, which is especially
comfortable, if you want to register a lot of services for Shopware's DIC. Using the `LazySubscriber` you can define
your services as you would in e.g. Pimple.

# How to use
## Install
If you want to use composer for this dependency, just run `composer install dnoegel/lazy-subscriber`.

## The Subscriber
```php
namespace YourPlugin\Subscriber;

class ContainerSubscriber extends LazySubscriber
{
    public function define()
    {
        return [
            'my_plugin.cart' => function() {
                return new Cart();
            },
            'my_plugin.persister => function(\Enlight_Plugin_Bootstrap_Config $plugin) {
                return new Persister($plugin->get('connection'));
            }
        ];
    }
}
```

## Bootstrap
In order to use your subscriber defined above, you need to register it during runtime.
```php
class Shopware_Plugins_Frontend_YourPlugin_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{
    // register namespaces for your plugin as well as for the lazy-subscriber library
    public function registerMyComponents()
    {
        $this->Application()->Loader()->registerNamespace(
            'Shopware\YourPlugin',
            $this->Path()
        );
        $this->Application()->Loader()->registerNamespace(
            'Dnoegel\LazySubscriber',
            $this->Path() . '/vendor/dnoegel/lazy-subscriber/src'
        );
    }

    // install: register an early event
    public function install()
    {
        $this->subscribeEvent(
            'Enlight_Controller_Front_DispatchLoopStartup',
            'onStartDispatch'
        );

        return true;
    }

    // Dynamically add your own subscriber
    public function onStartDispatch(Enlight_Event_EventArgs $args)
    {
        $this->registerMyComponents();

        $subscribers = array(
            new \Shopware\YourPlugin\Subscriber\ContainerSubscriber($this)

        );

        foreach ($subscribers as $subscriber) {
            $this->Application()->Events()->addSubscriber($subscriber);
        }
    }
}
```

# Should I use it?
It might be ok for populating the DI in your plugin as this is a bit cumbersome, especially during development.
You should **not** use it for default events, and you should not use it as a way to define all your event subscribers
in one big lazy subscriber.
In addition to that be aware, that event subscribers works nicely - if you know what is going on behind the scenes and what
to take care of. If this is not the case, you might want to stick to the "default event registration" way for the time being.
