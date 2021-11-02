<?php

namespace afzalroq\cms\entities;

use Yii;
use yii\behaviors\TimestampBehavior;
use afzalroq\cms\Module;

/**
 * This is the model class for table "cmc_item_photos".
 *
 * @property int $id
 * @property int $item_id
 * @property string $text
 * @property int $user_id
 * @property int $parent_id
 * @property int $level
 * @property int $status
 * @property int $vote
 * @property int $created_at
 * @property int $updated_at
 *
 * @var $entity afzalroq\cms\entities\Entities
 */
class ItemComments extends \yii\db\ActiveRecord
{
    const STATUS_DRAFT = 0;
    const STATUS_CHECKED = 1;
    const STATUS_DELETED = 2;

    public $childs = [];

    public static function tableName()
    {
        return '{{%cms_item_comments}}';
    }

    public function rules()
    {
        $entity = Entities::findOne(['slug' => \Yii::$app->request->get('slug')]);
        return [
            [['text','username', 'vote'],'filter','filter'=>'\yii\helpers\HtmlPurifier::process'],

            [['vote', 'parent_id', 'item_id', 'user_id', 'level', 'status'], 'integer'],
            [['status'], 'default', 'value' => ItemComments::STATUS_DRAFT],
            [['level'], 'default', 'value' => 0],

            [['text'], 'string'],
            [['text'], 'required',
                'when' => function ($model) use ($entity) {
                    return $entity->use_comments == Entities::COMMENT_ON_REQUIRED;
                },
                'enableClientValidation' => false
            ],

            [['vote'], 'required',
                'when' => function ($model) use ($entity) {
                    return $entity->use_votes == Entities::COMMENT_ON_REQUIRED;
                },
                'enableClientValidation' => false
            ],

            [['username'], 'required', 'when' => function ($model) use ($entity) {
                return $entity->comment_without_login && Yii::$app->user->isGuest;
            }],

            [['user_id'], 'required',
                'when' => function ($model) use ($entity) {
                    return !$entity->comment_without_login;
                },
                'message' => \Yii::t('cms', 'To write comment you should log in first')
            ]
        ];
    }

    public function attributeLabels()
    {
        return [
            'username' => Yii::t('cms', 'Comment Username'),
            'text' => Yii::t('cms', 'Comment Text'),
            'vote' => Yii::t('cms', 'Comment Vote'),
            'created_at' => Yii::t('cms', 'Created At'),
            'updated_at' => Yii::t('cms', 'Updated At'),
            'reCaptcha' => Yii::t('app', 'reCaptcha'),
        ];
    }

    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    /**
     * Gets query for [[Item]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getItem()
    {
        return $this->hasOne(Items::className(), ['id' => 'item_id']);
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(Module::getInstance()->userClass, ['id' => 'user_id']);
    }

    public function beforeSave($insert)
    {
        if ($this->user_id && is_null($this->username)) {
            $this->username = $this->user->full_name;
        }

        $entity = Entities::findOne(['slug' => \Yii::$app->request->get('slug')]);

        if ($entity->use_moderation) {
            if ($this->status == self::STATUS_CHECKED) {
                $this->item->addComment($this);
            }
        } else {
            if ($this->isNewRecord) {
                $this->item->addComment($this);
            }
        }

        if (!$this->isNewRecord && $this->status != self::STATUS_CHECKED) {
            $this->item->deleteComment($this);
        }
        return parent::beforeSave($insert); // TODO: Change the autogenerated stub
    }

    public function beforeDelete()
    {
        if($this->status == self::STATUS_CHECKED){
            $this->item->deleteComment($this);
        }
        return parent::beforeDelete(); // TODO: Change the autogenerated stub
    }

    public static function getStatusList()
    {
        return [
            self::STATUS_CHECKED => Yii::t('cms', 'Comment Status checked'),
            self::STATUS_DRAFT => Yii::t('cms', 'Comment Status Draft'),
            self::STATUS_DELETED => Yii::t('cms', 'Comment Status Deleted')
        ];
    }

    public function haveAnyChild()
    {
        return self::find()->where(['parent_id' => $this->id])->count() > 0;
    }

    public static function getChildIds($category_id)
    {
        static $k = 0;
        $categories[$k++] = $category_id;

        $result = self::find()
            ->where(['parent_id' => $category_id])
            ->all();

        foreach ($result as $mainCategory) {
            if ($mainCategory->haveAnyChild()) {
                $categories = array_merge($categories, $mainCategory->getChildIds($mainCategory->id));
            } else {
                $categories[$k++] = $mainCategory->id;
            }
        }
        return $categories;
    }

    public function getChilds()
    {
        $dataProvider = new \yii\data\ActiveDataProvider([
            'query' => self::find()->where(['id' => self::getChildIds($this->id)])->andWhere(['!=', 'id', $this->id])->orderBy(['created_at' => SORT_DESC])
        ]);
        return $dataProvider;
    }
}