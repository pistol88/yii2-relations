<?php
namespace pistol88\relations\behaviors;

use yii;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\web\UploadedFile;

class AttachRelations extends Behavior
{
    public $relatedModel = null;
    public $inAttribute = 'relations';
    
    private $relatedFind = null;
    private $doResetRelations = true; 
    
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_INSERT => 'saveRelations',
            ActiveRecord::EVENT_AFTER_UPDATE => 'saveRelations',
        ];
    }
    
    public function init()
    {
        parent::init();
        
        if(empty($this->relatedFind)) {
            $relatedModel = $this->relatedModel;
            $this->relatedFind = $relatedModel::find();
        }
    }
    
    public function saveRelations()
    {
        if($this->doResetRelations && yii::$app->request->post('send_relations')) {
            $this->doResetRelations = false;
            
            $models = yii::$app->request->post('relations_models');
            $ids = yii::$app->request->post('relations_ids');

            $relations = [];
            foreach($models as $key => $model) {
                $relations[$model][] = $ids[$key];
            }

            $this->owner->{$this->inAttribute} = serialize($relations);
            $this->owner->save(false);
        }

        return $this;
    }
    
    public function getRelatedModel()
    {
        return $this->relatedModel;
    }
    
    public function getRelations($where = null)
    {
        $find = $this->relatedFind;
        
        if($where) {
            $find = $find->where($where);
        }
        
        $ids = [];
        $relations = unserialize($this->owner->{$this->inAttribute});
        foreach($relations as $model => $relatedIds) {
            if($model == $this->relatedModel) {
                $ids = array_merge($ids, $relatedIds);
            }
        }
        
        if($ids) {
            return $find->where(['id' => $ids])->all();
        } else {
            return [];
        }
    }
}