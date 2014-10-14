<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace trntv\debug\xhprof\panels;

use trntv\debug\xhprof\models\search\Xhprof;
use Yii;
use yii\debug\Panel;
use yii\helpers\ArrayHelper;

/**
 * Debugger panel that collects and displays xhprof profiling data.
 *
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class XhprofPanel extends Panel
{
    private $_models = [];

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'Xhprof';
    }

    /**
     * @inheritdoc
     */
    public function getDetail()
    {
        $searchModel = new Xhprof();
        $dataProvider = $searchModel->search(Yii::$app->request->getQueryParams(), $this->getModels());
        return Yii::$app->view->render('@trntv/debug/xhprof/views/detail.php', [
            'panel' => $this,
            'dataProvider'=>$dataProvider,
            'searchModel'=>$searchModel
        ]);
    }


    /**
     * @inheritdoc
     */
    public function save()
    {
        if(function_exists('xhprof_disable')){
            $data = xhprof_disable();
        }
        return $data ?: [];
    }

    public function getModels()
    {
        if(!$this->_models){
            $t_ct = $t_wt = $t_cpu = $t_mu = 0;
            foreach($this->data as $fn => $data){
                $fn = explode('==>', $fn);
                $function = isset($fn[1]) ? $fn[1] : $fn[0];
                $parent = isset($fn[1]) ? $fn[0] : null;
                $data['fn']= $function;
                $t_ct += $data['ct'];
                $t_wt += $data['wt'];
                $t_cpu += $data['cpu'];
                $t_mu += $data['mu'];
                if(isset($this->_models[$function])){
                    $existingData = $this->_models[$function];
                    $this->_models[$function] = [
                        'fn'=>$function,
                        'ct'=>$existingData['ct']+$data['ct'],
                        'wt'=>$existingData['wt']+$data['wt'],
                        'cpu'=>$existingData['cpu']+$data['cpu'],
                        'mu'=>$existingData['mu']+$data['mu'],
                        'pmu'=>$existingData['pmu']+$data['pmu'],
                        'parents'=>$existingData['parents']
                    ];
                    $this->_models[$function]['parents'][] = $parent;
                } else {
                    $this->_models[$function] = $data;
                    $this->_models[$function]['parents'] = [$parent];
                }
            }
            foreach($this->_models as $f => $model){
                $this->_models[$f]['w_ct'] = ($model['ct'] / $t_ct);
                $this->_models[$f]['w_wt'] = ($model['wt'] / $t_wt);
                $this->_models[$f]['w_cpu'] = ($model['cpu'] / $t_cpu);
                $this->_models[$f]['w_mu'] = ($model['mu'] / $t_mu);
            }
        }
        return $this->_models;
    }

    public function getModel($fn)
    {
        return ArrayHelper::getValue($this->getModels(), $fn);
    }
}
