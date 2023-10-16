<?php

namespace Sprint\Migration;


class AddHLBlockNotifyRule20230913102542 extends Version
{
    protected $description = "Миграция добавляет HL-блок правил уведомлений";

    protected $moduleVersion = "4.1.2";

    /**
     * @return bool|void
     * @throws Exceptions\HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();
        $hlblockId = $helper->Hlblock()->saveHlblock(array(
            'NAME' => 'NotifyRules',
            'TABLE_NAME' => 'notify_rules',
            'LANG' =>
                array(
                    'ru' =>
                        array(
                            'NAME' => 'Правила уведомлений',
                        ),
                    'en' =>
                        array(
                            'NAME' => 'Notify Rules',
                        ),
                ),
        ));
        $helper->Hlblock()->saveField($hlblockId, array(
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
            'SETTINGS' =>
                array(
                    'PRECISION' => 0,
                    'SIZE' => 20,
                    'MIN_VALUE' => 0.0,
                    'MAX_VALUE' => 0.0,
                    'DEFAULT_VALUE' => '',
                ),
            'EDIT_FORM_LABEL' =>
                array(
                    'en' => 'Notify Interval',
                    'ru' => 'Интервал оповещений',
                ),
            'LIST_COLUMN_LABEL' =>
                array(
                    'en' => '',
                    'ru' => 'Интервал оповещений',
                ),
            'LIST_FILTER_LABEL' =>
                array(
                    'en' => '',
                    'ru' => 'Интервал оповещений',
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
        ));
        $helper->Hlblock()->saveField($hlblockId, array(
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
                array(
                    'en' => 'Addition users to notify',
                    'ru' => 'Дополнительно оповещаемые пользователи',
                ),
            'LIST_COLUMN_LABEL' =>
                array(
                    'en' => 'Addition users to notify',
                    'ru' => 'Дополнительно оповещаемые пользователи',
                ),
            'LIST_FILTER_LABEL' =>
                array(
                    'en' => 'Addition users to notify',
                    'ru' => 'Дополнительно оповещаемые пользователи',
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
        ));
        $helper->Hlblock()->saveField($hlblockId, array(
            'FIELD_NAME' => 'UF_NOTIFY_TOPIC',
            'USER_TYPE_ID' => 'enumeration',
            'XML_ID' => '',
            'SORT' => '100',
            'MULTIPLE' => 'N',
            'MANDATORY' => 'Y',
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
                array(
                    0 =>
                        array(
                            'VALUE' => 'Дата достижения контрольной точки',
                            'DEF' => 'Y',
                            'SORT' => '1',
                            'XML_ID' => '5afc15f53482e65ec1b5f09d20e29ccf',
                        ),
                    1 =>
                        array(
                            'VALUE' => 'Дата достижения результата',
                            'DEF' => 'N',
                            'SORT' => '2',
                            'XML_ID' => '46a8e9c89f724ff86544d9854454b56b',
                        ),
                    2 =>
                        array(
                            'VALUE' => 'Последняя дата месяца',
                            'DEF' => 'N',
                            'SORT' => '3',
                            'XML_ID' => '5729268b0dbcc8248ce7843ce1e31aec',
                        ),
                    3 =>
                        array(
                            'VALUE' => 'Последняя дата квартала',
                            'DEF' => 'N',
                            'SORT' => '4',
                            'XML_ID' => '2286ef5798cbe4f59d33334e45632e97',
                        ),
                    4 =>
                        array(
                            'VALUE' => 'Верификация',
                            'DEF' => 'N',
                            'SORT' => '5',
                            'XML_ID' => '8d83b4e57c72925d1516c9c4bfa30748',
                        ),
                    5 =>
                        array(
                            'VALUE' => 'Дата плана финансирования (по месяцам)',
                            'DEF' => 'N',
                            'SORT' => '6',
                            'XML_ID' => '98bcc11c3b971c9b3dd7a7adf3357f7f',
                        ),
                ),
        ));
        $helper->Hlblock()->saveField($hlblockId, array(
            'FIELD_NAME' => 'UF_PROJECT',
            'USER_TYPE_ID' => 'iblock_section',
            'XML_ID' => '',
            'SORT' => '100',
            'MULTIPLE' => 'Y',
            'MANDATORY' => 'Y',
            'SHOW_FILTER' => 'N',
            'SHOW_IN_LIST' => 'Y',
            'EDIT_IN_LIST' => 'Y',
            'IS_SEARCHABLE' => 'N',
            'SETTINGS' =>
                array(
                    'DISPLAY' => 'LIST',
                    'LIST_HEIGHT' => 5,
                    'IBLOCK_ID' => 34,
                    'DEFAULT_VALUE' => '',
                    'ACTIVE_FILTER' => 'N',
                ),
            'EDIT_FORM_LABEL' =>
                array(
                    'en' => 'Federal Project',
                    'ru' => 'Федеральный проект',
                ),
            'LIST_COLUMN_LABEL' =>
                array(
                    'en' => 'Federal Project',
                    'ru' => 'Федеральный проект',
                ),
            'LIST_FILTER_LABEL' =>
                array(
                    'en' => 'Federal Project',
                    'ru' => 'Федеральный проект',
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
        ));
        $helper->Hlblock()->saveField($hlblockId, array(
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
                array(
                    'SIZE' => 20,
                    'ROWS' => 5,
                    'REGEXP' => '',
                    'MIN_LENGTH' => 0,
                    'MAX_LENGTH' => 0,
                    'DEFAULT_VALUE' => '',
                    'PATTERN' => 'Текст уведомления',
                ),
            'EDIT_FORM_LABEL' =>
                array(
                    'en' => 'Notify text',
                    'ru' => 'Текст уведомления',
                ),
            'LIST_COLUMN_LABEL' =>
                array(
                    'en' => 'Notify text',
                    'ru' => 'Текст уведомления',
                ),
            'LIST_FILTER_LABEL' =>
                array(
                    'en' => 'Notify text',
                    'ru' => 'Текст уведомления',
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
        ));
        $helper->Hlblock()->saveField($hlblockId, array(
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
                array(
                    'en' => 'Notify author',
                    'ru' => 'Кем создано/изменено',
                ),
            'LIST_COLUMN_LABEL' =>
                array(
                    'en' => 'Notify author',
                    'ru' => 'Кем создано/изменено',
                ),
            'LIST_FILTER_LABEL' =>
                array(
                    'en' => 'Notify author',
                    'ru' => 'Кем создано/изменено',
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
        ));
        $helper->Hlblock()->saveField($hlblockId, array(
            'FIELD_NAME' => 'UF_USER_GROUPS',
            'USER_TYPE_ID' => 'enumeration',
            'XML_ID' => '',
            'SORT' => '100',
            'MULTIPLE' => 'N',
            'MANDATORY' => 'Y',
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
                    'en' => 'Notify Groups',
                    'ru' => 'Группы пользователей для рассылки',
                ),
            'LIST_COLUMN_LABEL' =>
                array(
                    'en' => 'Notify Groups',
                    'ru' => 'Группы пользователей для рассылки',
                ),
            'LIST_FILTER_LABEL' =>
                array(
                    'en' => 'Notify Groups',
                    'ru' => 'Группы пользователей для рассылки',
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
                array(
                    0 =>
                        array(
                            'VALUE' => 'Не направлять',
                            'DEF' => 'N',
                            'SORT' => '500',
                            'XML_ID' => '7a331e12ea5d57998f9fbba930810674',
                        ),
                    1 =>
                        array(
                            'VALUE' => 'Ответственные исполнители',
                            'DEF' => 'N',
                            'SORT' => '500',
                            'XML_ID' => 'b0c07011a303c0502c52cc8450364326',
                        ),
                    2 =>
                        array(
                            'VALUE' => 'Администраторы федеральных проектов',
                            'DEF' => 'N',
                            'SORT' => '500',
                            'XML_ID' => '2655db645ecf608e4dadcf5baad2ac6e',
                        ),
                    3 =>
                        array(
                            'VALUE' => 'Все',
                            'DEF' => 'N',
                            'SORT' => '500',
                            'XML_ID' => 'ab29b3715da153811485f15bd58b6cfa',
                        ),
                ),
        ));
        $helper->Hlblock()->saveField($hlblockId, array(
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
                array(
                    'PRECISION' => 4,
                    'SIZE' => 20,
                    'MIN_VALUE' => 0.0,
                    'MAX_VALUE' => 0.0,
                    'DEFAULT_VALUE' => '',
                ),
            'EDIT_FORM_LABEL' =>
                array(
                    'en' => 'Entity iblock id',
                    'ru' => 'ID инфоблока сущности',
                ),
            'LIST_COLUMN_LABEL' =>
                array(
                    'en' => 'Entity iblock id',
                    'ru' => 'ID инфоблока сущности',
                ),
            'LIST_FILTER_LABEL' =>
                array(
                    'en' => 'Entity iblock id',
                    'ru' => 'ID инфоблока сущности',
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
        ));
        $helper->Hlblock()->saveField($hlblockId, array(
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
                array(
                    'DISPLAY' => 'CHECKBOX',
                    'LIST_HEIGHT' => 5,
                    'CAPTION_NO_VALUE' => '',
                    'SHOW_NO_VALUE' => 'Y',
                ),
            'EDIT_FORM_LABEL' =>
                array(
                    'en' => 'Addition notify',
                    'ru' => 'Дополнительное оповещение',
                ),
            'LIST_COLUMN_LABEL' =>
                array(
                    'en' => 'Addition notify',
                    'ru' => 'Дополнительное оповещение',
                ),
            'LIST_FILTER_LABEL' =>
                array(
                    'en' => 'Addition notify',
                    'ru' => 'Дополнительное оповещение',
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
                array(
                    0 =>
                        array(
                            'VALUE' => 'Уведомление каждый день',
                            'DEF' => 'N',
                            'SORT' => '500',
                            'XML_ID' => 'e003ccccba1f376d435b11f072872479',
                        ),
                ),
        ));
        $helper->Hlblock()->saveField($hlblockId, array(
            'FIELD_NAME' => 'UF_MANAGERS_LIST',
            'USER_TYPE_ID' => 'double',
            'XML_ID' => '',
            'SORT' => '100',
            'MULTIPLE' => 'Y',
            'MANDATORY' => 'N',
            'SHOW_FILTER' => 'N',
            'SHOW_IN_LIST' => 'Y',
            'EDIT_IN_LIST' => 'Y',
            'IS_SEARCHABLE' => 'N',
            'SETTINGS' =>
                array(
                    'PRECISION' => 4,
                    'SIZE' => 20,
                    'MIN_VALUE' => 0.0,
                    'MAX_VALUE' => 0.0,
                    'DEFAULT_VALUE' => '',
                ),
            'EDIT_FORM_LABEL' =>
                array(
                    'en' => 'Managers',
                    'ru' => 'Вышестоящие руководители',
                ),
            'LIST_COLUMN_LABEL' =>
                array(
                    'en' => 'Managers',
                    'ru' => 'Вышестоящие руководители',
                ),
            'LIST_FILTER_LABEL' =>
                array(
                    'en' => 'Managers',
                    'ru' => 'Вышестоящие руководители',
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
        ));
    }

    public function down()
    {
        $helper = $this->getHelperManager();
        global $DB;
        $DB->StartTransaction();
        try {
            $helper->Hlblock()->deleteHlblockIfExists('NotifyRules');
        } catch (\Exception $ex) {
            $DB->Rollback();
            $this->outError('Ошибка отката миграции:' . $ex->getMessage());
        }
        $DB->Commit();
    }
}
