<?php
class TasktypeeditManagementPage extends FOGPage {
    public $node = 'tasktypeedit';
    public function __construct($name = '') {
        $this->name = 'Task Type Management';
        self::$foglang['ExportTasktypeedit'] = _('Export Task Types');
        self::$foglang['ImportTasktypeedit'] = _('Import Task Types');
        parent::__construct($this->name);
        $this->menu['list'] = sprintf(self::$foglang['ListAll'],_('Task Types'));
        $this->menu['add'] = sprintf(self::$foglang['CreateNew'],_('Task Typee'));
        if ($_REQUEST['id']) {
            $this->subMenu = array(
                $this->delformat => self::$foglang['Delete'],
            );
            $this->notes = array(
                _('Name')=>$this->obj->get('name'),
                _('Icon')=>sprintf('<i class="fa fa-%s fa-2x"></i>',$this->obj->get('icon')),
                _('Type')=>$this->obj->get('type'),
            );
        }
        $this->headerData = array(
            '<input type="checkbox" name="toggle-checkbox" class="toggle-checkboxAction"/>',
            _('Name'),
            _('Access'),
            _('Kernel Args'),
        );
        $this->templates = array(
            '<input type="checkbox" name="tasktypeedit[]" value="${id}" class="toggle-action"/>',
            sprintf('<a href="?node=%s&sub=edit&id=${id}" title="Edit"><i class="fa fa-${icon} fa-1x"> ${name}</i></a>',$this->node),
            '${access}',
            '${args}',
        );
        $this->attributes = array(
            array('width'=>16,'class'=>'l filter-false'),
            array('class'=>'l'),
            array('class'=>'c'),
            array('class'=>'r'),
        );
        self::$returnData = function(&$TaskType) {
            if (!$TaskType->isValid()) return;
            $this->data[] = array(
                'icon'=>$TaskType->get('icon'),
                'id'=>$TaskType->get('id'),
                'name'=>$TaskType->get('name'),
                'access'=>$TaskType->get('access'),
                'args'=>$TaskType->get('kernelArgs'),
            );
            unset($TaskType);
        };
    }
    public function index() {
        $this->title = _('All Task Types');
        if (self::getSetting('FOG_DATA_RETURNED')>0 && self::getClass($this->childClass)->getManager()->count() > self::getSetting('FOG_DATA_RETURNED') && $_REQUEST['sub'] != 'list') $this->redirect(sprintf('?node=%s&sub=search',$this->node));
        $this->data = array();
        array_map(self::$returnData,(array)self::getClass($this->childClass)->getManager()->find());
        self::$HookManager->processEvent('TASKTYPE_DATA',array('headerData'=>&$this->headerData,'data'=>&$this->data,'templates'=>&$this->templates,'attributes'=>&$this->attributes));
        $this->render();
    }
    public function search_post() {
        $this->data = array();
        array_map(self::$returnData,(array)self::getClass($this->childClass)->getManager()->search('',true));
        self::$HookManager->processEvent('TASKTYPE_DATA',array('headerData'=>&$this->headerData,'data'=>&$this->data,'templates'=>&$this->templates,'attributes'=>&$this->attributes));
        $this->render();
    }
    public function add() {
        $this->title = _('New Task Type');
        unset($this->headerData);
        $this->attributes = array(
            array(),
            array(),
        );
        $this->templates = array(
            '${field}',
            '${input}',
        );
        $accessTypes = array('both','host','group');
        ob_start();
        foreach ($accessTypes AS $i => &$type) {
            printf('<option value="%s"%s>%s</option>',$type,$_REQUEST['access'] == $type ? ' selected' : '',ucfirst($type));
            unset($type);
        }
        unset($accessTypes);
        $access_opt = ob_get_clean();
        $fields = array(
            _('Name') => sprintf('<input type="text" name="name" class="smaller" value="%s"/>',$_REQUEST['name']),
            _('Description') => sprintf('<textarea name="description" rows="8" cols="40">%s</textarea>',$_REQUEST['description']),
            _('Icon') => self::getClass('TaskType')->iconlist($_REQUEST['icon']),
            _('Kernel') => sprintf('<input type="text" name="kernel" class="smaller" value="%s"/>',$_REQUEST['kernel']),
            _('Kernel Arguments') => sprintf('<input type="text" name="kernelargs" class="smaller" value="%s"/>',$_REQUEST['kernelargs']),
            _('Type') => sprintf('<input type="text" name="type" class="smaller" value="%s"/>',$_REQUEST['type']),
            _('Is Advanced') => sprintf('<input type="checkbox" name="advanced"%s>',(isset($_REQUEST['advanced']) ? ' checked' : '')),
            _('Accessed By') => sprintf('<select name="access">%s</select>',$access_opt),
            ''=> sprintf('<input class="smaller" type="submit" value="%s"/>',_('Add'))
        );
        foreach((array)$fields AS $field => &$input) {
            $this->data[] = array(
                'field'=>$field,
                'input'=>$input,
            );
            unset($input);
        }
        unset($fields);
        self::$HookManager->processEvent('TASKTYPE_ADD',array('headerData'=>&$this->headerData,'data'=>&$this->data,'templates'=>&$this->templates,'attributes'=>&$this->attributes));
        printf('<form method="post" action="%s">',$this->formAction);
        $this->render();
        echo '</form>';
    }
    public function add_post() {
        try {
            $name = $_REQUEST['name'];
            $description = $_REQUEST['description'];
            $icon = $_REQUEST['icon'];
            $kernel = $_REQUEST['kernel'];
            $kernelargs = $_REQUEST['kernelargs'];
            $type = (string)$_REQUEST['type'];
            $advanced = (string)intval((int)isset($_REQUEST['advanced']));
            $access = $_REQUEST['access'];
            if (!$name) throw new Exception(_('You must enter a name'));
            if (self::getClass('TaskTypeManager')->exists($name)) throw new Exception(_('Task type already exists, please try again.'));
            $TaskType = self::getClass('TaskType')
                ->set('name',$name)
                ->set('description',$description)
                ->set('icon',$icon)
                ->set('kernel',$kernel)
                ->set('kernelArgs',$kernelargs)
                ->set('type',$type)
                ->set('isAdvanced',$advanced)
                ->set('access',$access);
            if (!$TaskType->save()) throw new Exception(_('Failed to create'));
            $this->setMessage(_('Task Type added, editing'));
            $this->redirect(sprintf('?node=%s&sub=edit&id=%s',$this->node,$TaskType->get(id)));
        } catch (Exception $e) {
            $this->setMessage($e->getMessage());
            $this->redirect($this->formAction);
        }
    }
    public function edit() {
        $this->title = sprintf('%s: %s', _('Edit'), $this->obj->get('name'));
        unset($this->headerData);
        $this->attributes = array(
            array(),
            array(),
        );
        $this->templates = array(
            '${field}',
            '${input}',
        );
        $accessTypes = array('both','host','group');
        foreach ($accessTypes AS $i => &$type) {
            printf('<option value="%s"%s>%s</option>',$type,$this->obj->get('access') == $type ? ' selected' : '',ucfirst($type));
            unset($type);
        }
        unset($accessTypes);
        $access_opt = ob_get_clean();
        $fields = array(
            _('Name') => sprintf('<input type="text" name="name" class="smaller" value="%s"/>',$this->obj->get('name')),
            _('Description') => sprintf('<textarea name="description" rows="8" cols="40">%s</textarea>',$this->obj->get('description')),
            _('Icon') => sprintf('<input type="text" name="icon" class="smaller" value="%s"/>',$this->obj->get('icon')),
            _('Icon') => self::getClass('TaskType')->iconlist($this->obj->get('icon')),
            _('Kernel') => sprintf('<input type="text" name="kernel" class="smaller" value="%s"/>',$this->obj->get('kernel')),
            _('Kernel Arguments') => sprintf('<input type="text" name="kernelargs" class="smaller" value="%s"/>',$this->obj->get('kernelArgs')),
            _('Type') => sprintf('<input type="text" name="type" class="smaller" value="%s"/>',$this->obj->get('type')),
            _('Is Advanced') => sprintf('<input type="checkbox" name="advanced"%s/>',($this->obj->get('isAdvanced') ? ' checked' : '')),
            _('Accessed By') => sprintf('<select name="access">%s</select>',$access_opt),
            '' => sprintf('<input class="smaller" type="submit" value="%s"/>',_('Update')),
        );
        foreach((array)$fields AS $field => &$input) {
            $this->data[] = array(
                'field'=>$field,
                'input'=>$input,
            );
            unset($input);
        }
        unset($fields);
        self::$HookManager->processEvent('TASKTYPE_EDIT',array('headerData'=>&$this->headerData,'data'=>&$this->data,'templates'=>&$this->templates,'attributes'=>&$this->attributes));
        printf('<form method="post" action="%s">',$this->formAction);
        $this->render();
        echo '</form>';
    }
    public function edit_post() {
        self::$HookManager->processEvent('TASKTYPE_EDIT_POST',array('TaskType'=>&$this->obj));
        try {
            $name = $_REQUEST['name'];
            $description = $_REQUEST['description'];
            $icon = $_REQUEST['icon'];
            $kernel = $_REQUEST['kernel'];
            $kernelargs = $_REQUEST['kernelargs'];
            $type = (int)$_REQUEST['type'];
            $advanced = (string)intval((int)isset($_REQUEST['advanced']));
            $access = $_REQUEST['access'];
            if (!$name) throw new Exception(_('You must enter a name'));
            if ($this->obj->get('name') != $name && self::getClass('TaskTypeManager')->exists($name)) throw new Exception(_('Task type already exists, please try again.'));
            $this->obj
                ->set('name',$name)
                ->set('description',$description)
                ->set('icon',$icon)
                ->set('kernel',$kernel)
                ->set('kernelArgs',$kernelargs)
                ->set('type',$type)
                ->set('isAdvanced',$advanced)
                ->set('access',$access);
            if (!$this->obj->save()) throw new Exception(_('Failed to update'));
            $this->setMessage('TaskType Updated');
            $this->redirect($this->formAction);
        } catch (Exception $e) {
            $this->setMessage($e->getMessage());
            $this->redirect($this->formAction);
        }
    }
}
