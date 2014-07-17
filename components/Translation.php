<?php
/** Created by griga at 17.07.13 | 12:20.
 *
 */

class Translation
{
    private $_categoryFilePath = null;
    private $_data = [];

    public function save($phrase)
    {
        $message = [];
        /** @var TranslationModule $module */
        $module = app()->getModule('translation');
        if (isset($phrase['category'])) {
            foreach ($module->languages as $lang => $name) {
                if ($lang !== $module->sourceLanguage) {
                    $this->setCategoryFilePath($lang, $phrase['category']);
                    $this->setData();
                    unset($this->_data[$phrase[$module->sourceLanguage]]);
                    $this->_data[$phrase[$module->sourceLanguage]] = isset($phrase[$lang]) ? htmlspecialchars($phrase[$lang]) : '';
                    $this->dump();
                }
            }
            $message['success'] = 'Объект с ключем <strong>'.$phrase[$module->sourceLanguage] . '</strong> сохранен.';
        } else {
            $message['error'] = 'не правильный запрос. Не задана категория.';
        }
        return $message;
    }

    public function dump(){
        file_put_contents($this->_categoryFilePath, '<?php return ' . var_export($this->_data, TRUE) . '; ?>');
    }

    public function delete($phrase)
    {
        $message = [];
        /** @var TranslationModule $module */
        $module = app()->getModule('translation');
        if (isset($phrase['category'])) {
            foreach ($module->languages as $lang => $name) {
                if ($module->sourceLanguage && $lang !== $module->sourceLanguage) {
                    $this->setCategoryFilePath($lang, $phrase['category']);
                    $this->setData();
                    unset($this->_data[$phrase['key']]);
                    $this->dump();
                }
            }
            $message['success'] = 'Объект с ключем <strong>'.$phrase['key'].'</strong> удален.';
        } else {
            $message['error'] = 'Не правильный запрос. Не задана категория.';
        }
        return $message;

    }


    private static $dataCache;

    public function getTranslationData()
    {
        if(isset(self::$dataCache)){
            return self::$dataCache;
        }
        /** @var TranslationModule $module */
        $module = app()->getModule('translation');
        $data = [
            'languages'=>$module->languages,
            'categories'=>[],
        ];
        $langFiles = [];
        
        foreach ($module->languages as $lang => $name) {
            if ($module->sourceLanguage && $lang !== $module->sourceLanguage) {
                $langDir = $this->getLangDir($lang);
                foreach (new DirectoryIterator($langDir) as $fileInfo) {
                    /** @var SplFileInfo $fileInfo */
                    if ($fileInfo->isDot()) continue;
                    else {
                        $category = $fileInfo->getBasename('.php');
                        if (!isset($data['categories'][$category])) {
                            $data['categories'][$category] = [
                                'name'=>$category,
                                'phrases'=>[],
                            ];
                        }
                        $langFile = require($fileInfo->getRealPath());
                        ksort($langFile);
                        if(!isset($langFiles[$category]))
                            $langFiles[$category] = [
                                $module->sourceLanguage => []
                            ];

                        $langFiles[$category][$module->sourceLanguage] = array_unique(array_merge($langFiles[$category][$module->sourceLanguage], array_keys($langFile)));
                        $langFiles[$category][$lang] = $langFile;

                    }
                }
            }
        }
        foreach($langFiles as $category => $categoryData){
            foreach ($categoryData[$module->sourceLanguage ] as $phrase) {
                $phraseData = [
                        'key'=>$phrase,
                        'translations'=>[
                            [
                                'key'=>$module->sourceLanguage,
                                'value'=>$phrase
                            ]
                        ]
                    ];
                foreach ($module->languages as $lang => $name) {
                    if ($lang !== $module->sourceLanguage) {
                        $phraseData['translations'][] = ['key'=>$lang, 'value'=>$categoryData[$lang][$phrase]];
                    }
                }
                $data['categories'][$category]['phrases'][] = $phraseData;
            }

        }

        self::$dataCache = $data;
        return $data;
    }

    private function setData(){
        if (file_exists($this->_categoryFilePath)) {
            $this->_data = require_once($this->_categoryFilePath);
        } else {
            $this->_data = [];
        }
    }

    private function setCategoryFilePath($lang, $category)
    {
        $this->_categoryFilePath = $this->getLangDir($lang) . DIRECTORY_SEPARATOR . $category . '.php';
    }
    
    private function getLangDir($lang)
    {
        $langDir = Yii::getPathOfAlias('application.messages').DIRECTORY_SEPARATOR . $lang;
        if (!is_dir($langDir)) {
            mkdir($langDir);
        }
        return $langDir;
    }

}