<?php

class TranslationModule extends CWebModule
{

    /**
     * @property string the path to the layout file to use for displaying module.
     */
    public $layout = 'translation.views.layouts.main';

    /**
     * @property string the base url for module.
     */
    public $baseUrl = '/translation/module';

    /**
     * @property array the list of languages 'shortCode'=>'label' ('en'=>'English')
     */
    public $languages = array();

    /**
     * @property string source language in messages files
     */
    public $sourceLanguage;

    /**
     * @property Translation operator object
     */
    public $translation;

    /**
     * @property boolean whether to enable debug mode.
     */
    public $debug = false;

    private $_assetsUrl;

    public function init()
	{
		$this->setImport(array(
			'translation.components.*',
		));
        $this->defaultController = 'module';
        $this->translation = new Translation();
	}


    /**
     * Registers the necessary scripts.
     */
    public function registerScripts()
    {
        $assetsUrl = $this->getAssetsUrl();
        cs()->registerCssFile($assetsUrl.'/css/translation.css');
    }

    /**
     * Publishes the module assets path.
     * @return string the base URL that contains all published asset files of Translation module.
     */
    public function getAssetsUrl()
    {
        if( $this->_assetsUrl===null )
        {
            $assetsPath = Yii::getPathOfAlias('translation.assets');

            // We need to republish the assets if debug mode is enabled.
            if( $this->debug )
                $this->_assetsUrl = Yii::app()->getAssetManager()->publish($assetsPath, false, -1, true);
            else
                $this->_assetsUrl = Yii::app()->getAssetManager()->publish($assetsPath);
        }

        return $this->_assetsUrl;
    }
}
