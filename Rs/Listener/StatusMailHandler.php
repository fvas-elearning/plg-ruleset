<?php
namespace Rs\Listener;

use App\Db\MailTemplate;
use Ca\Db\Assessment;
use Tk\Event\Subscriber;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class StatusMailHandler implements Subscriber
{

    /**
     * @param \Bs\Event\StatusEvent $event
     * @throws \Exception
     */
    public function onSendAllStatusMessages(\Bs\Event\StatusEvent $event)
    {
        // do not send messages
        $course = \Uni\Util\Status::getCourse($event->getStatus());
        if (!$event->getStatus()->isNotify() || ($course && !$course->getCourseProfile()->isNotifications())) {
            //\Tk\Log::debug('Rs::onSendAllStatusMessages: Status Notification Disabled');
            return;
        }
        $subject = \Uni\Util\Status::getSubject($event->getStatus());

        /** @var \Tk\Mail\CurlyMessage $message */
        foreach ($event->getMessageList() as $message) {
            if (!$message->get('placement::id')) continue;

            /** @var \App\Db\Placement $placement */
            $placement = \App\Db\PlacementMap::create()->find($message->get('placement::id'));
            if ($placement) {
                /** @var MailTemplate $mailTemplate */
                $mailTemplate = $message->get('_mailTemplate');
                $companyRules = \Rs\Calculator::findCompanyRuleList($placement->getCompany(), $placement->getSubject())->toArray('name');
                $message->set('rules::companyRules', implode(',', $companyRules));

                $placementRules = \Rs\Calculator::findPlacementRuleList($placement)->toArray('name');
                $message->set('rules::placementRules', implode(',', $placementRules));

            }
        }
    }

    /**
     * @param \Tk\Event\Event $event
     * @throws \Exception
     */
    public function onTagList(\Tk\Event\Event $event)
    {
        $course = $event->get('course');
        $list = $event->get('list');

        $list['{rules::companyRules}'] = 'Category1, Category2, ...';
        $list['{rules::placementRules}'] = 'Category1, Category2, ...';

//        $list['{entry::id}'] = 1;
//        $list['{entry::assessor}'] = 'Assessor Name';
//        $list['{entry::status}'] = 'approved';
//        $list['{entry::notes}'] = 'Notes Text';
//        $list['{entry::attachPdf}'] = 'Attach Entry PDF to email';
//
//        $list['{assessment::id}'] = 1;
//        $list['{assessment::name}'] = 'Assessment Name';
//        $list['{assessment::description}'] = 'HTML description text';
//        $list['{assessment::placementTypes}'] = 'StatusNames';
//        $list['{assessment::linkHtml}'] = 'HTML links';
//        $list['{assessment::linkText}'] = 'Text links';
//
//        //Deprecated update all mail templates.
////        $list['{ca::linkHtml}'] = 'All available assessment HTML links';
////        $list['{ca::linkText}'] = 'All available assessment Text links';
//
//        $aList = \Ca\Db\AssessmentMap::create()->findFiltered(array('courseId' => $course->getId()));
//        foreach ($aList as $assessment) {
//            $key = $assessment->getNameKey();
//            $tag = sprintf('{%s}{/%s}', $key, $key);
//            $list[$tag] = 'Assessment block';
//            // Hidden do not encourage use of these at the moment only by developers as needed.
////            $list[sprintf('{%s::linkHtml}', $key)] = '<a href="http://www.example.com/form.html" title="Assessment">Assessment</a>';
////            $list[sprintf('{%s::linkText}', $key)] = 'Assessment: http://www.example.com/form.html';
//        }

        $event->set('list', $list);
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
            \Bs\StatusEvents::STATUS_SEND_MESSAGES => array('onSendAllStatusMessages', 10),
            \App\AppEvents::MAIL_TEMPLATE_TAG_LIST => array('onTagList', 10)
        );
    }
    
}