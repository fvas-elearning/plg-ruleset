<?php
namespace Rs\Listener;

use Symfony\Component\HttpKernel\KernelEvents;
use Tk\Event\Subscriber;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class PlacementViewHandler implements Subscriber
{

    /**
     * @var null|\App\Controller\Placement\View
     */
    protected $controller = null;

    /**
     * @var null|\App\Db\Placement
     */
    protected $placement = null;


    /**
     * @param \Symfony\Component\HttpKernel\Event\FilterControllerEvent $event
     * @throws \Exception
     */
    public function onControllerInit($event)
    {
        $controller = \Tk\Event\Event::findControllerObject($event);
        if ($controller instanceof \App\Controller\Placement\View) {
            $this->controller = $controller;
        }
    }

    /**
     * @param \Tk\Event\Event $event
     * @throws \Dom\Exception
     * @throws \Exception
     */
    public function onControllerShow(\Tk\Event\Event $event)
    {
        if (!$this->controller) return;
        $placement = $this->controller->getPlacement();
        $rules = \Rs\Calculator::findPlacementRuleList($placement);

        $html = '';
        foreach ($rules as $rule) {
            $html .= sprintf('<li>%s</li>', $rule->getName()) . "\n";
        }
        $html = rtrim($html , "\n");
        if ($html) {
            //$html = sprintf('<ul style="padding: 0 0 0 0px; list-style: none;">%s</ul>', $html);
            $html = sprintf('<ul style="padding: 0 0 0 5px; list-style: none;">%s</ul>', $html);
            $this->controller->getTemplate()->appendHtml('dl-list', sprintf('<dt>Assessment Credit:</dt> <dd>%s</dd>', $html));
        }

    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::CONTROLLER => array('onControllerInit', 0),
            \Tk\PageEvents::CONTROLLER_SHOW => array('onControllerShow', 0)
        );
    }
    
}