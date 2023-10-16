<?php

namespace Sprint\Migration;

use Bitrix\Main\Loader;
use Sprint\Migration\Helpers\IblockHelper;
use Sprint\Migration\Helpers\HlblockHelper;

class AddHLBlockNotify20230904142034 extends Version
{
    protected $description = "Миграция создает HL-блок для самих уведомлений";
    protected $moduleVersion = "4.1.2";
    protected $hlBlockName = "Notify";
    protected $hlBlockTableName = 'notify';
    protected $hlBlockRuName = 'Уведомления';


    public function up()
    {
        global $DB;
        $DB->StartTransaction();
        try {
            $helper = new HlblockHelper;
            $helper->saveHlblock([
                'NAME' => $this->hlBlockName,
                'TABLE_NAME' => $this->hlBlockTableName,
                'LANG' => [
                    'ru' => [
                        'NAME' => $this->hlBlockRuName
                    ],
                    'en' => [
                        'NAME' => 'Notify'
                    ]
                ]
            ]);
            $hlBlockId = $helper->getHlblockId($this->hlBlockName);
            $fields = $this->getUserFields();
            foreach ($fields as $field) {
                $helper->saveField($hlBlockId, $field);
            }
        } catch (\Exception $ex) {
            $DB->Rollback();
            $this->outError('Ошибка установки миграции:' . $ex->getMessage());
        }
        $DB->Commit();
    }

    public function getUserFields()
    {
        return [
            [
                'FIELD_NAME' => 'UF_NOTIFY_STATUS',
                'USER_TYPE_ID' => 'enumeration',
                'XML_ID' => '',
                'SORT' => '100',
                'MULTIPLE' => 'N',
                'MANDATORY' => 'N',
                'SHOW_FILTER' => 'N',
                'SHOW_IN_LIST' => 'Y',
                'EDIT_IN_LIST' => 'Y',
                'IS_SEARCHABLE' => 'N',
                'SETTINGS' =>
                    [
                        'DISPLAY' => 'LIST',
                        'LIST_HEIGHT' => 5,
                        'CAPTION_NO_VALUE' => '',
                        'SHOW_NO_VALUE' => 'Y',
                    ],
                'EDIT_FORM_LABEL' =>
                    [
                        'en' => 'Notify status',
                        'ru' => 'Статус уведомления',
                    ],
                'LIST_COLUMN_LABEL' =>
                    [
                        'en' => '',
                        'ru' => 'Статус уведомления',
                    ],
                'LIST_FILTER_LABEL' =>
                    [
                        'en' => '',
                        'ru' => 'Статус уведомления',
                    ],
                'ERROR_MESSAGE' =>
                    [
                        'en' => '',
                        'ru' => '',
                    ],
                'HELP_MESSAGE' =>
                    [
                        'en' => '',
                        'ru' => '',
                    ],
                'ENUM_VALUES' =>
                    [
                        0 =>
                            [
                                'VALUE' => 'Необходимо отправить',
                                'DEF' => 'N',
                                'SORT' => '1',
                            ],
                        1 =>
                            [
                                'VALUE' => 'Отправлено',
                                'DEF' => 'N',
                                'SORT' => '2',
                            ],
                        2 =>
                            [
                                'VALUE' => 'Получено',
                                'DEF' => 'N',
                                'SORT' => '3',
                            ],
                    ],
            ],
            [
                'FIELD_NAME' => 'UF_RULE_ID',
                'USER_TYPE_ID' => 'double',
                'XML_ID' => '',
                'SORT' => '100',
                'MULTIPLE' => 'N',
                'MANDATORY' => 'N',
                'SHOW_FILTER' => 'N',
                'SHOW_IN_LIST' => 'Y',
                'EDIT_IN_LIST' => 'Y',
                'IS_SEARCHABLE' => 'N',
                'SETTINGS' =>
                    [
                        'PRECISION' => 4,
                        'SIZE' => 20,
                        'MIN_VALUE' => 0.0,
                        'MAX_VALUE' => 0.0,
                        'DEFAULT_VALUE' => '',
                    ],
                'EDIT_FORM_LABEL' =>
                    [
                        'en' => 'Notify rule id',
                        'ru' => 'ID правила уведомлений',
                    ],
                'LIST_COLUMN_LABEL' =>
                    [
                        'en' => 'Notify rule id',
                        'ru' => 'ID правила уведомлений',
                    ],
                'LIST_FILTER_LABEL' =>
                    [
                        'en' => 'Notify rule id',
                        'ru' => 'ID правила уведомлений',
                    ],
                'ERROR_MESSAGE' =>
                    [
                        'en' => '',
                        'ru' => '',
                    ],
                'HELP_MESSAGE' =>
                    [
                        'en' => '',
                        'ru' => '',
                    ],
            ],
            [
                'FIELD_NAME' => 'UF_NOTIFY_DATETIME',
                'USER_TYPE_ID' => 'datetime',
                'XML_ID' => '',
                'SORT' => '100',
                'MULTIPLE' => 'N',
                'MANDATORY' => 'N',
                'SHOW_FILTER' => 'N',
                'SHOW_IN_LIST' => 'Y',
                'EDIT_IN_LIST' => 'Y',
                'IS_SEARCHABLE' => 'N',
                'SETTINGS' =>
                    [
                        'DEFAULT_VALUE' =>
                            [
                                'TYPE' => 'NONE',
                                'VALUE' => '',
                            ],
                        'USE_SECOND' => 'Y',
                    ],
                'EDIT_FORM_LABEL' =>
                    [
                        'en' => 'Date to notify',
                        'ru' => 'Дата для отправки',
                    ],
                'LIST_COLUMN_LABEL' =>
                    [
                        'en' => 'Date to notify',
                        'ru' => 'Дата для отправки',
                    ],
                'LIST_FILTER_LABEL' =>
                    [
                        'en' => 'Date to notify',
                        'ru' => 'Дата для отправки',
                    ],
                'ERROR_MESSAGE' =>
                    [
                        'en' => '',
                        'ru' => '',
                    ],
                'HELP_MESSAGE' =>
                    [
                        'en' => '',
                        'ru' => '',
                    ],
            ],
            [
                'FIELD_NAME' => 'UF_NOTIFY_TEXT',
                'USER_TYPE_ID' => 'string_formatted',
                'XML_ID' => '',
                'SORT' => '100',
                'MULTIPLE' => 'N',
                'MANDATORY' => 'N',
                'SHOW_FILTER' => 'N',
                'SHOW_IN_LIST' => 'Y',
                'EDIT_IN_LIST' => 'Y',
                'IS_SEARCHABLE' => 'N',
                'SETTINGS' =>
                    [
                        'SIZE' => 20,
                        'ROWS' => 8,
                        'REGEXP' => '',
                        'MIN_LENGTH' => 0,
                        'MAX_LENGTH' => 0,
                        'DEFAULT_VALUE' => '',
                        'PATTERN' => '',
                    ],
                'EDIT_FORM_LABEL' =>
                    array(
                        'en' => 'Notify text',
                        'ru' => 'Текст для уведомления',
                    ),
                'LIST_COLUMN_LABEL' =>
                    [
                        'en' => 'Notify text',
                        'ru' => 'Текст для уведомления',
                    ],
                'LIST_FILTER_LABEL' =>
                    [
                        'en' => 'Notify text',
                        'ru' => 'Текст для уведомления',
                    ],
                'ERROR_MESSAGE' =>
                    [
                        'en' => '',
                        'ru' => '',
                    ],
                'HELP_MESSAGE' =>
                    [
                        'en' => '',
                        'ru' => '',
                    ],
            ],
            [
                'FIELD_NAME' => 'UF_NOTIFY_USER',
                'USER_TYPE_ID' => 'double',
                'XML_ID' => '',
                'SORT' => '100',
                'MULTIPLE' => 'N',
                'MANDATORY' => 'N',
                'SHOW_FILTER' => 'N',
                'SHOW_IN_LIST' => 'Y',
                'EDIT_IN_LIST' => 'Y',
                'IS_SEARCHABLE' => 'N',
                'SETTINGS' =>
                    [
                        'PRECISION' => 4,
                        'SIZE' => 20,
                        'MIN_VALUE' => 0.0,
                        'MAX_VALUE' => 0.0,
                        'DEFAULT_VALUE' => '',
                    ],
                'EDIT_FORM_LABEL' =>
                    [
                        'en' => 'User',
                        'ru' => 'Пользователь',
                    ],
                'LIST_COLUMN_LABEL' =>
                    [
                        'en' => 'User',
                        'ru' => 'Пользователь',
                    ],
                'LIST_FILTER_LABEL' =>
                    [
                        'en' => 'User',
                        'ru' => 'Пользователь',
                    ],
                'ERROR_MESSAGE' =>
                    [
                        'en' => '',
                        'ru' => '',
                    ],
                'HELP_MESSAGE' =>
                    [
                        'en' => '',
                        'ru' => '',
                    ],
            ]
        ];
    }

    public function down()
    {
        $helper = new HlblockHelper;
        global $DB;
        $DB->StartTransaction();
        try {
            $helper->deleteHlblockIfExists($this->hlBlockName);
        } catch (\Exception $ex) {
            $DB->Rollback();
            $this->outError('Ошибка отката миграции:' . $ex->getMessage());
        }
        $DB->Commit();
    }
}
