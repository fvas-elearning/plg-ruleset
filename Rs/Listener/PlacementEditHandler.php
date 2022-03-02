<?php
namespace Rs\Listener;

use App\Controller\Student\Placement\Create;
use App\Db\Placement;
use Bs\DbEvents;
use Rs\Calculator;
use Symfony\Component\HttpKernel\KernelEvents;
use Tk\ConfigTrait;
use Tk\Event\Subscriber;
use Tk\Log;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class PlacementEditHandler implements Subscriber
{
    use ConfigTrait;

    /**
     * @var null|\App\Controller\Placement\Edit
     */
    protected $controller = null;

    /**
     * @var null|\App\Db\Placement
     */
    protected $placement = null;


    /**
     * @param \Symfony\Component\HttpKernel\Event\ControllerEvent $event
     * @throws \Exception
     */
    public function onControllerInit($event)
    {
        $controller = \Tk\Event\Event::findControllerObject($event);
        if ($controller instanceof \App\Controller\Placement\Edit || $controller instanceof \App\Controller\Student\Placement\Create) {
            $this->controller = $controller;

            if ($controller->getRequest()->has('getRules')) {
                $this->doGetRules($controller->getRequest());
            }
        }
    }

    /**
     * @param \Tk\Request $request
     * @throws \Exception
     */
    public function doGetRules(\Tk\Request $request)
    {
        /** @var \App\Db\Placement $placement */
        $placement = \App\Db\CompanyMap::create()->find($request->get('placementId'));
        /** @var \App\Db\Company $company */
        $company = \App\Db\CompanyMap::create()->find($request->get('companyId'));
        /** @var \App\Db\Subject $subject */
        $subject = \App\Db\SubjectMap::create()->find($request->get('subjectId'));
//        /** @var \App\Db\Supervisor $supervisor */
//        $supervisor = \App\Db\SupervisorMap::create()->find($request->get('supervisorId'));

        // TODO: do we only need static rules here??
        $data = \Rs\Calculator::findCompanyRuleList($company, $subject, false)->toArray('id');
        if ($placement) {
            $data = \Rs\Calculator::findPlacementRuleList($placement, false)->toArray('id');
        }

        \Tk\ResponseJson::createJson($data)->send();
        exit;
    }

    /**
     * @param \Tk\Event\FormEvent $event
     * @throws \Exception
     */
    public function onFormInit(\Tk\Event\FormEvent $event)
    {
        /** @var \Tk\Form $form */
        $form = $event->getForm();
        if ($this->controller) {
            $this->placement = $this->controller->getPlacement();

            $courseRules = \Rs\Calculator::findSubjectRuleList($this->placement->getSubject(), false);
            $companyRules = \Rs\Calculator::findCompanyRuleList($this->placement->getCompany(), $this->placement->getSubject(), false);
            $placementRules = \Rs\Calculator::findPlacementRuleList($this->placement, false);

            //$field = new \Tk\Form\Field\CheckboxGroup('rules', $courseRules);
            $field = new \Tk\Form\Field\Radio('rules', $courseRules);
            if (!$this->placement->getId() && $this->controller instanceof \App\Controller\Student\Placement\Create) {
                $field = new \Tk\Form\Field\Radio('rules', $companyRules);
            }

            $field->setArrayField(true);
            $field->setValue($companyRules);
            if (!$this->placement->getId() || $this->placement->getStatus() == Placement::STATUS_DRAFT) {
                $field->setValue($companyRules->toArray('id'));
            } else {
                $field->setValue($placementRules->toArray('id'));
            }

            $field->addOnShowOption(function (\Dom\Template $template, \Tk\Form\Field\Option $option, $var) {
                $catList = $this->placement->getCompany()->getCategoryList();
                // Highlight the categories that are in the company list where possible
                foreach ($catList as $cat) {
                    if ($option->getName() == $cat->getName()) {
                        if (!$this->placement->getId() && $this->controller instanceof \App\Controller\Student\Placement\Create) {
                            $option->setName('[' . $cat->getClass() . '] ' . $option->getName());
                        } else {
                            $option->setName('* ' . $option->getName());
                        }
                        break;
                    }
                }
            });

            $field->setAttr('data-placement-id', $this->placement->getId());
            $field->setAttr('data-company-id', $this->placement->companyId);
            $field->setAttr('data-subject-id', $this->placement->subjectId);
            //$field->setAttr('data-supervisor-id', $this->placement->supervisorId.'');

            if ($this->controller instanceof \App\Controller\Student\Placement\Create) {
                if ($this->placement->getId()) {
                    $field->setReadonly();
                    $field->setDisabled(true);
                    $field->setAttr('data-hide-unselected');
                }
                $form->appendField($field, 'units');
                $field->setNotes('If this company has multiple categories, please select your preferred placement experience');
            } else {
                $field->setTabGroup('Details');
                $form->appendField($field);
            }
            $field->setLabel('Assessment Credit');

            if ($form->getField('update'))
                $form->addEventCallback('update', array($this, 'doSubmit'));
            if ($form->getField('save'))
                $form->addEventCallback('save', array($this, 'doSubmit'));
            if ($form->getField('submitForApproval'))
                $form->addEventCallback('submitForApproval', array($this, 'doSubmit'));

            // style the list to look nice.
            $css = <<<CSS
ul.assessment-credit {
  padding-left: 15px;
}
ul.assessment-credit li {
  font-weight: 600;
}
CSS;

            $this->controller->getTemplate()->appendCss($css);


            $js = <<<JS
jQuery(function ($) {
  
  function setCheckboxes(checkboxList) {
    var params = checkboxList.first().data();
    var hideUnselected = params.hideUnselected;
    params = $.extend({getRules: 'getRules'}, {
      placementId: params.placementId, 
      companyId: params.companyId, 
      subjectId: params.subjectId, 
      //supervisorId: params.supervisorId
    });
    $(this).parent().find('input[type=checkbox],input[type=radio]').prop('checked', false);
    
    console.log(params);
    console.log($(this).parent().find('input[type=checkbox],input[type=radio]'));
    
    $.post(document.location, params, function (data) {
      checkboxList.each(function () {
        if(jQuery.inArray(parseInt($(this).val()), data) !== -1) {
          $(this).prop('checked', true).closest('.checkbox').show();
        } else {
          $(this).prop('checked', false);
          if (hideUnselected) {
            $(this).closest('.checkbox').hide();
          }
        }
      });
    });
  }
  
  
  $('.tk-rules').each(function () {
    var fieldGroup = $(this);
    var checkboxList = fieldGroup.find('input[type=checkbox],input[type=radio]');
    //console.log(checkboxList);
    if (config.roleType !== 'student') {
        var resetBtn = $('<p><button type="button" class="btn btn-default btn-xs" title="Reset the placement to the company default assessment credit."><i class="fa fa-refresh"></i> Reset</button></p>');
        fieldGroup.find(' > div').append(resetBtn);
        resetBtn.on('click', function () {
          //var checkboxList = $(this).parent().find('input[type=checkbox]');
          setCheckboxes(checkboxList);
          return false;
        });
    }
    console.log(checkboxList.first().data('placementId'));
    if (checkboxList.first().data('placementId') === '0') {
    //if (fieldGroup.find('.checkbox-group').data('placementId') === '0') {
      setCheckboxes(checkboxList);
      fieldGroup.closest('form').find('.tk-supervisorid select').on('change', function () {
        checkboxList.first().data('supervisorId', $(this).val());
        setCheckboxes(checkboxList);
      });
    }
  });
  
});
JS;
            $this->controller->getTemplate()->appendJs($js);


        }
    }

    /**
     * @param \Tk\Form $form
     * @param \Tk\Form\Event\Iface $event
     * @throws \Exception
     */
    public function doSubmit($form, $event)
    {
        $selectedRules = $form->getFieldValue('rules');
        if (!is_array($selectedRules)) $selectedRules = array();

        \App\Config::getInstance()->getSession()->set(Create::SID.'_rules', $selectedRules);

        if($form->hasErrors()) return;

        // Remove non-static rules
        \Rs\Db\RuleMap::create()->removeFromPlacement($this->placement);
        // Add selected non-static rules
        foreach ($selectedRules as $ruleId) {
            \Rs\Db\RuleMap::create()->addPlacement($ruleId, $this->placement->getVolatileId());
        }

    }

    /**
     * Assign new static rules at the time of placement creation
     *
     * @param \Bs\Event\DbEvent $event
     * @throws \Exception
     */
    public function onPlacementInsert(\Bs\Event\DbEvent $event)
    {
        if (!$event->getModel() instanceof Placement) return;
        /** @var Placement $placement */
        $placement = $event->getModel();
        // TODO: Assign new static rules at the time of placement creation
        $companyStaticRules = Calculator::findCompanyRuleList($placement->getCompany(), $placement->getSubject(), true);
        foreach ($companyStaticRules as $rule) {
            if ($this->getConfig()->isDebug())
                Log::alert(sprintf(' + Added new non-static rule to placement [p:%s => r:%s]: %s',
                    $placement->getId(), $rule->getId(), $rule->getName()));
            \Rs\Db\RuleMap::create()->addPlacement($rule->getId(), $placement->getId());
        }

    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::CONTROLLER => array('onControllerInit', 0),
            \Tk\Form\FormEvents::FORM_INIT => array('onFormInit', 0),
            DbEvents::MODEL_INSERT_POST =>  array('onPlacementInsert', 0),
        );
    }
    
}