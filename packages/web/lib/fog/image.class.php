<?php
class Image extends FOGController {
    protected $databaseTable = 'images';
    protected $databaseFields = array(
        'id' => 'imageID',
        'name' => 'imageName',
        'description' => 'imageDesc',
        'path' => 'imagePath',
        'createdTime' => 'imageDateTime',
        'createdBy' => 'imageCreateBy',
        'building' => 'imageBuilding',
        'size' => 'imageSize',
        'imageTypeID' => 'imageTypeID',
        'imagePartitionTypeID' => 'imagePartitionTypeID',
        'osID' => 'imageOSID',
        'size' => 'imageSize',
        'deployed' => 'imageLastDeploy',
        'format' => 'imageFormat',
        'magnet' => 'imageMagnetUri',
        'protected' => 'imageProtect',
        'compress' => 'imageCompress',
        'isEnabled' => 'imageEnabled',
        'toReplicate' => 'imageReplicate',
    );
    protected $databaseFieldsRequired = array(
        'name',
        'path',
        'imageTypeID',
        'osID',
    );
    protected $additionalFields = array(
        'hosts',
        'hostsnotinme',
        'storageGroups',
        'storageGroupsnotinme',
    );
    public function destroy($field = 'id') {
        self::getClass('HostManager')->update(array('imageID'=>$this->get('id')),'',array('imageID'=>0));
        self::getClass('ImageAssociationManager')->destroy(array('imageID'=>$this->get('id')));
        return parent::destroy($field);
    }
    public function save() {
        parent::save();
        switch ($this->get('id')) {
        case 0:
        case null:
        case false:
        case '0':
        case '':
            $this->destroy();
            throw new Exception(_('Image ID was not set, or unable to be created'));
            break;
        case ($this->isLoaded('hosts')):
            $DBHostIDs = self::getSubObjectIDs('Host',array('imageID'=>$this->get('id')),'hostID');
            $RemoveHostIDs = array_diff((array)$DBHostIDs,(array)$this->get('hosts'));
            if (count($RemoveHostIDs)) {
                self::getClass('HostManager')->update(array('imageID'=>$this->get('id')),'',array('imageID'=>0));
                $DBHostIDs = self::getSubObjectIDs('Host',array('imageID'=>$this->get('id')),'hostID');
                unset($RemoveHostIDs);
            }
            $Hosts = array_diff((array)$this->get('hosts'),(array)$DBHostIDs);
            self::getClass('HostManager')->update(array('id'=>$Hosts),'',array('imageID'=>$this->get('id')));
            unset($Hosts,$DBHostIDs);
        case ($this->isLoaded('storageGroups')):
            $DBGroupIDs = self::getSubObjectIDs('ImageAssociation',array('imageID'=>$this->get('id')),'storageGroupID');
            $RemoveGroupIDs = array_diff((array)$DBGroupIDs,(array)$this->get('storageGroups'));
            if (count($RemoveGroupIDs)) {
                self::getClass('ImageAssociationManager')->destroy(array('imageID'=>$this->get('id'),'storageGroupID'=>$RemoveGroupIDs));
                $DBGroupIDs = self::getSubObjectIDs('ImageAssociation',array('imageID'=>$this->get('id')),'storageGroupID');
                unset($RemoveGroupIDs);
            }
            array_map(function(&$Group) {
                if (!$Group->isValid()) return;
                self::getClass('ImageAssociation')
                    ->set('imageID',$this->get('id'))
                    ->set('storageGroupID',$Group->get('id'))
                    ->save();
                unset($Group);
            },(array)self::getClass('StorageGroupManager')->find(array('id'=>array_diff((array)$this->get('storageGroups'),(array)$DBGroupIDs))));
            unset($Groups,$DBGroupIDs);
        }
        return $this;
    }
    public function deleteFile() {
        if ($this->get('protected')) throw new Exception(self::$foglang['ProtectedImage']);
        array_map(function(&$StorageNode) {
            if (!$StorageNode->isValid()) return;
            $delete = sprintf('/%s/%s',trim($StorageNode->get('ftppath'),'/'),$this->get('path'));
            self::$FOGFTP
                ->set('host',$StorageNode->get('ip'))
                ->set('username',$StorageNode->get('user'))
                ->set('password',$StorageNode->get('pass'));
            if (!self::$FOGFTP->connect()) return;
            self::$FOGFTP
                ->delete($delete)
                ->close();
            unset($StorageNode);
        },(array)self::getClass('StorageNodeManager')->find(array('storageGroupID'=>$this->get('storageGroups'),'isEnabled'=>1)));
    }
    public function addHost($addArray) {
        if (!$this->isLoaded('hosts')) $this->loadHosts();
        $this->set('hosts',array_unique(array_merge((array)$this->get('hosts'),(array)$addArray)));
        return $this;
    }
    public function removeHost($removeArray) {
        if (!$this->isLoaded('hosts')) $this->loadHosts();
        $this->set('hosts',array_unique(array_diff((array)$this->get('hosts'),(array)$removeArray)));
        return $this;
    }
    public function addGroup($addArray) {
        if (!$this->isLoaded('storageGroups')) $this->loadStorageGroups();
        $this->set('storageGroups',array_unique(array_merge((array)$this->get('storageGroups'),(array)$addArray)));
        return $this;
    }
    public function removeGroup($removeArray) {
        if (!$this->isLoaded('storageGroups')) $this->loadStorageGroups();
        $this->set('storageGroups',array_unique(array_diff((array)$this->get('storageGroups'),(array)$removeArray)));
        return $this;
    }
    public function getStorageGroup() {
        if (!$this->isLoaded('storageGroups')) $this->loadStorageGroups();
        if (!count($this->get('storageGroups'))) $this->set('storageGroups',(array)@min(self::getSubObjectIDs('StorageGroup')));
        foreach ((array)$this->get('storageGroups') AS &$Group) {
            if ($this->getPrimaryGroup($Group)) return self::getClass('StorageGroup',$Group);
            unset($Group);
        }
        return self::getClass('StorageGroup',@min($this->get('storageGroups')));
    }
    public function getOS() {
        return self::getClass('OS',$this->get('osID'));
    }
    public function getImageType() {
        return self::getClass('ImageType',$this->get('imageTypeID'));
    }
    public function getImagePartitionType() {
        if ((int)$this->get('imagePartitionTypeID') < 1) $this->set('imagePartitionTypeID',1)->save();
        return self::getClass('ImagePartitionType',(int)$this->get('imagePartitionTypeID'));
    }
    public function getPrimaryGroup($groupID) {
        $primaryCount = self::getClass('ImageAssociationManager')->count(array('imageID'=>$this->get('id'),'primary'=>1));
        if ($primaryCount < 1) $primaryCount = self::getClass('ImageAssociationManager')->count(array('imageID'=>$this->get('id')));
        if ($primaryCount < 1) $this->setPrimaryGroup(@min(self::getSubObjectIDs('StorageGroup')));
        $assocID = @min(self::getSubObjectIDs('ImageAssociation',array('storageGroupID'=>$groupID,'imageID'=>$this->get('id'))));
        return self::getClass('ImageAssociation',$assocID)->isPrimary();
    }
    public function setPrimaryGroup($groupID) {
        self::getClass('ImageAssociationManager')->update(array('imageID'=>$this->get('id'),'storageGroupID'=>array_diff((array)$this->get('storageGroups'),(array)$groupID)),'',array('primary'=>0));
        self::getClass('ImageAssociationManager')->update(array('imageID'=>$this->get('id'),'storageGroupID'=>$groupID),'',array('primary'=>1));
    }
    protected function loadHosts() {
        if (!$this->get('id')) return;
        $this->set('hosts',self::getSubObjectIDs('Host',array('imageID'=>$this->get('id')),'id'));
    }
    protected function loadHostsnotinme() {
        if (!$this->get('id')) return;
        if (!$this->isLoaded('hosts')) $this->loadHosts();
        $find = array('id'=>$this->get('hosts'));
        $this->set('hostsnotinme',self::getSubObjectIDs('Host',$find,'',true));
        unset($find);
    }
    protected function loadStorageGroups() {
        if (!$this->get('id')) return;
        $this->set('storageGroups',self::getSubObjectIDs('ImageAssociation',array('imageID'=>$this->get('id')),'storageGroupID'));
        if (!count($this->get('storageGroups'))) $this->set('storageGroups',(array)@min(self::getSubObjectIDs('StorageGroup','','id')));
    }
    protected function loadStorageGroupsnotinme() {
        if (!$this->get('id'));
        $find = array('id'=>$this->get('storageGroups'));
        $this->set('storageGroupsnotinme',self::getSubObjectIDs('StorageGroup',$find,'',true));
        unset($find);
    }
    public function getPartitionType() {
        return $this->getImagePartitionType()->get('type');
    }
}
