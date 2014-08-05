<?php

/**
 * Class ModuleController
 *
 * @property $module TranslationModule
 */
class ModuleController extends BackendController
{

    public $layout;

    public function init()
    {
        $this->layout = $this->module->layout;
        parent::init();
    }


    public function actionIndex()
    {
        $this->render('index');
    }

    public function actionSave()
    {
        $request_body = file_get_contents('php://input');
        $data = CJSON::decode($request_body, true);
        if (isset($data['phrase'])) {
            $this->renderJson($this->module->translation->save($data['phrase']));
        }
    }

    public function actionDelete()
    {
        $request_body = file_get_contents('php://input');
        $data = CJSON::decode($request_body, true);
        if (isset($data['phrase'])) {
            $this->renderJson($this->module->translation->delete($data['phrase']));
        }
    }

    /**
     *
     */
    public function actionAll()
    {
        $this->renderJson($this->module->translation->getTranslationData());
    }

    /**
     *
     */
    public function actionTest()
    {
        $this->render('test');
    }

}