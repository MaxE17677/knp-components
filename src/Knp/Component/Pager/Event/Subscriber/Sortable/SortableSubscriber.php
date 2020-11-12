<?php

namespace Knp\Component\Pager\Event\Subscriber\Sortable;

use Knp\Component\Pager\Event\BeforeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SortableSubscriber implements EventSubscriberInterface
{
    /**
     * Lazy-load state tracker for request
     * @var string
     */
    private $isLoaded = false;
    private $subscribers = [];

    public function before(BeforeEvent $event): void
    {
        $request = $event->getRequest();
        $requestHash = spl_object_hash($request);
        $disp = $event->getEventDispatcher();

        // Do not lazy-load more than once per request
        if ($requestHash === $this->isLoaded) {
            return;
        }

        // remove subscribers of other requests
        foreach($this->subscribers as $subscriber){
            $disp->removeSubscriber($subscriber);
        }
        // hook all standard sortable subscribers
        $this->subscribers = [
            new Doctrine\ORM\QuerySubscriber($request),
            new Doctrine\ODM\MongoDB\QuerySubscriber($request),
            new ElasticaQuerySubscriber($request),
            new PropelQuerySubscriber($request),
            new SolariumQuerySubscriber($request),
            new ArraySubscriber($request),
        ];
        foreach($this->subscribers as $subscriber){
            $disp->addSubscriber($subscriber);
        }

        $this->isLoaded = $requestHash;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'knp_pager.before' => ['before', 1]
        ];
    }
}
