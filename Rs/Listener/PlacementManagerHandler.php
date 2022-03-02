<?php
namespace Rs\Listener;

use App\Db\Placement;
use Ca\Db\Assessment;
use Ca\Db\Entry;
use Dom\Template;
use Rs\Calculator;
use Tk\Table\Cell\Text;
use Uni\Db\Permission;
use Symfony\Component\HttpKernel\KernelEvents;
use Tk\ConfigTrait;
use Tk\Event\Subscriber;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 * @todo; we still need to implement this
 */
class PlacementManagerHandler implements Subscriber
{
    use ConfigTrait;


    /**
     * @var null|\App\Controller\Placement\Manager
     */
    protected $controller = null;


    /**
     * PlacementManagerHandler constructor.
     */
    public function __construct()
    {
    }

    /**
     * @param \Symfony\Component\HttpKernel\Event\FilterControllerEvent $event
     */
    public function onControllerInit($event)
    {
        /** @var \App\Controller\Placement\Edit $controller */
        $controller = \Tk\Event\Event::findControllerObject($event);
        if ($controller instanceof \App\Controller\Placement\Manager) {
            $config = \Uni\Config::getInstance();
            $this->controller = $controller;
        }
    }

    /**
     * Check the user has access to this controller
     *
     * @param \Tk\Event\TableEvent $event
     * @throws \Exception
     */
    public function addEntryCell(\Tk\Event\TableEvent $event)
    {
        if (!$this->controller) return;

        if (!$event->getTable() instanceof \App\Table\Placement ||
            ($event->getTable()->get('isMentorView', false) || !$this->getAuthUser()->isLearner())
        ) return;
        $subjectId = $event->getTable()->get('subjectId');
        if (!$subjectId) return;

        $table = $event->getTable();

        $table->appendCell(Text::create('plClass'), 'coClass')->setOrderProperty('')
            ->setAttr('title', 'Placement Category')
            ->addOnPropertyValue(function (\Tk\Table\Cell\Iface $cell, Placement $obj, $value) {
                $rule = Calculator::findPlacementRuleList($obj, false)->current();
                if ($rule)
                    $value = $rule->getLabel();
                return $value;
            });

    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     *
     * @api
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::CONTROLLER => array('onControllerInit', 0),
            \Tk\Table\TableEvents::TABLE_INIT => array(array('addEntryCell', 0))
        );
    }

}