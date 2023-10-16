<?php

namespace Sprint\Migration;

use Bitrix\Main\Loader;
use Sprint\Migration\Helpers\IblockHelper;
use Sprint\Migration\Helpers\HlblockHelper;

class AddHLBlockNotifyRule20230904130417 extends Version
{
    protected $description = "Миграция добавляет HL-блок правил уведомлений";
    protected $moduleVersion = "4.1.2";
    protected $hlBlockName = 'NotifyRules';
    protected $hlBlockTableName = 'notify_rules';
    protected $hlBlockRuName = 'Правила уведомлений';


    public function up()
    {
        global $DB;
        $DB->StartTransaction();
        try {
            $helper = $this->getHelperManager();
            $hlBlockId = $helper->Hlblock()->getHlblock($this->hlBlockName);
            if (!$hlBlockId) {
                $hlBlockFields = $this->getUserFields();
                $hlblockId = $helper->Hlblock()->saveHlblock([
                    'NAME' => $this->hlBlockName,
                    'TABLE_NAME' => $this->hlBlockTableName,
                    'LANG' => [
                        'ru' => [
                            'NAME' => $this->hlBlockRuName
                        ],
                        'en' => [
                            'NAME' => 'Notify Rules'
                        ]
                    ]
                ]);
                foreach ($hlBlockFields as $field) {
                    $helper->Hlblock()->saveField($hlblockId, $field);
                }
            }
        } catch (\Exception $ex) {
            $DB->Rollback();
            $this->outError('Ошибка установки миграции:' . $ex->getMessage());
        }
        $DB->Commit();
    }

    public function getUserFields()
    {
        $iblock = new IblockHelper();
        $federalProjectIblockId = $iblock->getIblockId('Project', 'mao');
        return [
            [
                'FIELD_NAME' => 'UF_INTERVAL',
                'USER_TYPE_ID' => 'double',
                'XML_ID' => '',
                'SORT' => '100',
                'MULTIPLE' => 'N',
                'MANDATORY' => 'N',
                'SHOW_FILTER' => 'N',
                'SHOW_IN_LIST' => 'Y',
                'EDIT_IN_LIST' => 'Y',
                'IS_SEARCHABLE' => 'N',
                'EDIT_FORM_LABEL' =>
                    [
                        'en' => 'Notify Interval',
                        'ru' => 'Интервал оповещений',
                    ],
                'LIST_COLUMN_LABEL' =>
                    [
                        'en' => '',
                        'ru' => 'Интервал оповещений',
                    ],
                'LIST_FILTER_LABEL' =>
                    [
                        'en' => '',
                        'ru' => 'Интервал оповещений',
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

                'FIELD_NAME' => 'UF_ADDITION_USERS',
                'USER_TYPE_ID' => 'employee',
                'XML_ID' => '',
                'SORT' => '100',
                'MULTIPLE' => 'Y',
                'MANDATORY' => 'N',
                'SHOW_FILTER' => 'N',
                'SHOW_IN_LIST' => 'Y',
                'EDIT_IN_LIST' => 'Y',
                'IS_SEARCHABLE' => 'N',
                'SETTINGS' => NULL,
                'EDIT_FORM_LABEL' =>
                    [
                        'en' => 'Addition users to notify',
                        'ru' => 'Дополнительно оповещаемые пользователи',
                    ],
                'LIST_COLUMN_LABEL' =>
                    [
                        'en' => 'Addition users to notify',
                        'ru' => 'Дополнительно оповещаемые пользователи',
                    ],
                'LIST_FILTER_LABEL' =>
                    [
                        'en' => 'Addition users to notify',
                        'ru' => 'Дополнительно оповещаемые пользователи',
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
                'FIELD_NAME' => 'UF_NOTIFY_TOPIC',
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
                    array(
                        'DISPLAY' => 'LIST',
                        'LIST_HEIGHT' => 5,
                        'CAPTION_NO_VALUE' => '',
                        'SHOW_NO_VALUE' => 'Y',
                    ),
                'EDIT_FORM_LABEL' =>
                    array(
                        'en' => 'Notify topic',
                        'ru' => 'Тема оповещения',
                    ),
                'LIST_COLUMN_LABEL' =>
                    array(
                        'en' => 'Notify topic',
                        'ru' => 'Тема оповещения',
                    ),
                'LIST_FILTER_LABEL' =>
                    array(
                        'en' => 'Notify topic',
                        'ru' => 'Тема оповещения',
                    ),
                'ERROR_MESSAGE' =>
                    array(
                        'en' => '',
                        'ru' => '',
                    ),
                'HELP_MESSAGE' =>
                    array(
                        'en' => '',
                        'ru' => '',
                    ),
                'ENUM_VALUES' =>
                    [
                        0 =>
                            [
                                'VALUE' => 'Дата достижения результата',
                                'DEF' => 'N',
                                'SORT' => '500',

                            ],
                        1 =>
                            [
                                'VALUE' => 'Дата достижения контрольной точки',
                                'DEF' => 'N',
                                'SORT' => '500',
                            ],
                        2 =>
                            [
                                'VALUE' => 'Последняя дата месяца',
                                'DEF' => 'N',
                                'SORT' => '500',
                            ],
                        3 =>
                            [
                                'VALUE' => 'Последняя дата квартала',
                                'DEF' => 'N',
                                'SORT' => '500',
                            ],
                        4 =>
                            [
                                'VALUE' => 'Верификация',
                                'DEF' => 'N',
                                'SORT' => '500',
                            ],
                    ],
            ],
            [
                'FIELD_NAME' => 'UF_PROJECT',
                'USER_TYPE_ID' => 'iblock_section',
                'XML_ID' => '',
                'SORT' => '100',
                'MULTIPLE' => 'Y',
                'MANDATORY' => 'N',
                'SHOW_FILTER' => 'N',
                'SHOW_IN_LIST' => 'Y',
                'EDIT_IN_LIST' => 'Y',
                'IS_SEARCHABLE' => 'N',
                'SETTINGS' =>
                    [
                        'DISPLAY' => 'LIST',
                        'LIST_HEIGHT' => 5,
                        'IBLOCK_ID' => $federalProjectIblockId,
                        'DEFAULT_VALUE' => '',
                        'ACTIVE_FILTER' => 'N',
                    ],
                'EDIT_FORM_LABEL' =>
                    [
                        'en' => 'Federal Project',
                        'ru' => 'Федеральный проект',
                    ],
                'LIST_COLUMN_LABEL' =>
                    [
                        'en' => 'Federal Project',
                        'ru' => 'Федеральный проект',
                    ],
                'LIST_FILTER_LABEL' =>
                    [
                        'en' => 'Federal Project',
                        'ru' => 'Федеральный проект',
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
                        'ROWS' => 5,
                        'REGEXP' => '',
                        'MIN_LENGTH' => 0,
                        'MAX_LENGTH' => 0,
                        'DEFAULT_VALUE' => '',
                        'PATTERN' => 'Текст уведомления',
                    ],
                'EDIT_FORM_LABEL' =>
                    [
                        'en' => 'Notify text',
                        'ru' => 'Текст уведомления',
                    ],
                'LIST_COLUMN_LABEL' =>
                    [
                        'en' => 'Notify text',
                        'ru' => 'Текст уведомления',
                    ],
                'LIST_FILTER_LABEL' =>
                    [
                        'en' => 'Notify text',
                        'ru' => 'Текст уведомления',
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
                'FIELD_NAME' => 'UF_NOTIFY_AUTHOR',
                'USER_TYPE_ID' => 'employee',
                'XML_ID' => '',
                'SORT' => '100',
                'MULTIPLE' => 'N',
                'MANDATORY' => 'N',
                'SHOW_FILTER' => 'N',
                'SHOW_IN_LIST' => 'Y',
                'EDIT_IN_LIST' => 'Y',
                'IS_SEARCHABLE' => 'N',
                'SETTINGS' => NULL,
                'EDIT_FORM_LABEL' =>
                    [
                        'en' => 'Notify author',
                        'ru' => 'Кем создано/изменено',
                    ],
                'LIST_COLUMN_LABEL' =>
                    [
                        'en' => 'Notify author',
                        'ru' => 'Кем создано/изменено',
                    ],
                'LIST_FILTER_LABEL' =>
                    [
                        'en' => 'Notify author',
                        'ru' => 'Кем создано/изменено',
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
                'FIELD_NAME' => 'UF_USER_GROUPS',
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
                        'en' => 'Notify Groups',
                        'ru' => 'Группы пользователей для рассылки',
                    ],
                'LIST_COLUMN_LABEL' =>
                    [
                        'en' => 'Notify Groups',
                        'ru' => 'Группы пользователей для рассылки',
                    ],
                'LIST_FILTER_LABEL' =>
                    [
                        'en' => 'Notify Groups',
                        'ru' => 'Группы пользователей для рассылки',
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
                                'VALUE' => 'Не направлять',
                                'DEF' => 'N',
                                'SORT' => '500',
                            ],
                        1 =>
                            [
                                'VALUE' => 'Ответственные исполнители',
                                'DEF' => 'N',
                                'SORT' => '500',
                            ],
                        2 =>
                            [
                                'VALUE' => 'Администраторы федеральных проектов',
                                'DEF' => 'N',
                                'SORT' => '500',
                            ],
                        3 =>
                            [
                                'VALUE' => 'Все',
                                'DEF' => 'N',
                                'SORT' => '500',
                            ],
                    ],
            ],
            [
                'FIELD_NAME' => 'UF_NOTIFY_IBLOCK_ID',
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
                        'en' => 'Entity iblock id',
                        'ru' => 'ID инфоблока сущности',
                    ],
                'LIST_COLUMN_LABEL' =>
                    [
                        'en' => 'Entity iblock id',
                        'ru' => 'ID инфоблока сущности',
                    ],
                'LIST_FILTER_LABEL' =>
                    [
                        'en' => 'Entity iblock id',
                        'ru' => 'ID инфоблока сущности',
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
                'FIELD_NAME' => 'UF_ADDITION_NOTIFY',
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
                        'DISPLAY' => 'CHECKBOX',
                        'LIST_HEIGHT' => 5,
                        'CAPTION_NO_VALUE' => '',
                        'SHOW_NO_VALUE' => 'Y',
                    ],
                'EDIT_FORM_LABEL' =>
                    [
                        'en' => 'Addition notify',
                        'ru' => 'Дополнительное оповещение',
                    ],
                'LIST_COLUMN_LABEL' =>
                    [
                        'en' => 'Addition notify',
                        'ru' => 'Дополнительное оповещение',
                    ],
                'LIST_FILTER_LABEL' =>
                    [
                        'en' => 'Addition notify',
                        'ru' => 'Дополнительное оповещение',
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
                                'VALUE' => 'Уведомление каждый день',
                                'DEF' => 'N',
                                'SORT' => '500',
                            ],
                    ],
            ]
        ];
    }

    public function down()
    {
        $helper = $this->getHelperManager();
        global $DB;
        $DB->StartTransaction();
        try {
            $helper->Hlblock()->deleteHlblockIfExists($this->hlBlockName);
        } catch (\Exception $ex) {
            $DB->Rollback();
            $this->outError('Ошибка отката миграции:' . $ex->getMessage());
        }
        $DB->Commit();
    }
}
