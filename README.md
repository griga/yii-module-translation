yii-translation-module
======================

gui for work with yii message files

depends on yii-bootstrap (or yii-booster) extension;

Add **translation** module to your app
<pre>git submodule add https://github.com/griga/yii-translation-module.git protected/modules/translation</pre>

Add next lines to your <code>config.php</code> file
<pre>
	'modules'=>array(
        ...
        'translation'=>array(
            'layout'=>'//../modules/admin/views/layouts/column1',
            'languages' => array(
                'en' => 'English',
                'ru' => 'Русский',
                'ua' => 'Українська',

            ),
            'sourceLanguage'=>'en',
        ),
		...
	),
</pre>
<pre>
		'urlManager'=>array(
            ...
			'rules'=>array(
                ...

                'translation' => 'translation',
                'translation/&lt;controller:\w+&gt;/&lt;action:\w+&gt;'=>'translation/&lt;controller&gt;/&lt;action&gt;',
			),
		),
</pre>

 