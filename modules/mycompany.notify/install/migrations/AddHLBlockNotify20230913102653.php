<?php

namespace Sprint\Migration;


class AddHLBlockNotify20230913102653 extends Version
{
    protected $description = "Миграция создает HL-блок для самих уведомлений";

    protected $moduleVersion = "4.1.2";

    /**
     * @return bool|void
     * @throws Exceptions\HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();
        $hlblockId = $helper->Hlblock()->saveHlblock(array(
            'NAME' => 'Notify',
            'TABLE_NAME' => 'notify',
            'LANG' =>
                array(
                    'ru' =>
                        array(
                            'NAME' => 'Уведомления',
                        ),
                    'en' =>
                        array(
                            'NAME' => 'Notify',
                        ),
                ),
        ));
        $helper->Hlblock()->saveField($hlblockId, array(
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
                array(
                    'DISPLAY' => 'LIST',
                    'LIST_HEIGHT' => 5,
                    'CAPTION_NO_VALUE' => '',
                    'SHOW_NO_VALUE' => 'Y',
                ),
            'EDIT_FORM_LABEL' =>
                array(
                    'en' => 'Notify status',
                    'ru' => 'Статус уведомления',
                ),
            'LIST_COLUMN_LABEL' =>
                array(
                    'en' => '',
                    'ru' => 'Статус уведомления',
                ),
            'LIST_FILTER_LABEL' =>
                array(
                    'en' => '',
                    'ru' => 'Статус уведомления',
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
                            'VALUE' => 'Не прочитано',
                            'DEF' => 'Y',
                            'SORT' => '4',
                            'XML_ID' => '271108fce0c5dab2ceb9a0a709b1e82a',
                        ),
                    1 =>
                        array(
                            'VALUE' => 'Прочитано',
                            'DEF' => 'N',
                            'SORT' => '5',
                            'XML_ID' => '09fbf677b929e8ba9f02c6be3bfe6361',
                        ),
                ),
        ));
        $helper->Hlblock()->saveField($hlblockId, array(
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
                array(
                    'PRECISION' => 4,
                    'SIZE' => 20,
                    'MIN_VALUE' => 0.0,
                    'MAX_VALUE' => 0.0,
                    'DEFAULT_VALUE' => '',
                ),
            'EDIT_FORM_LABEL' =>
                array(
                    'en' => 'Notify rule id',
                    'ru' => 'ID правила уведомлений',
                ),
            'LIST_COLUMN_LABEL' =>
                array(
                    'en' => 'Notify rule id',
                    'ru' => 'ID правила уведомлений',
                ),
            'LIST_FILTER_LABEL' =>
                array(
                    'en' => 'Notify rule id',
                    'ru' => 'ID правила уведомлений',
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
                array(
                    'DEFAULT_VALUE' =>
                        array(
                            'TYPE' => 'NONE',
                            'VALUE' => '',
                        ),
                    'USE_SECOND' => 'Y',
                ),
            'EDIT_FORM_LABEL' =>
                array(
                    'en' => 'Date to notify',
                    'ru' => 'Дата для отправки',
                ),
            'LIST_COLUMN_LABEL' =>
                array(
                    'en' => 'Date to notify',
                    'ru' => 'Дата для отправки',
                ),
            'LIST_FILTER_LABEL' =>
                array(
                    'en' => 'Date to notify',
                    'ru' => 'Дата для отправки',
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
                    'ROWS' => 8,
                    'REGEXP' => '',
                    'MIN_LENGTH' => 0,
                    'MAX_LENGTH' => 0,
                    'DEFAULT_VALUE' => '',
                    'PATTERN' => '',
                ),
            'EDIT_FORM_LABEL' =>
                array(
                    'en' => 'Notify text',
                    'ru' => 'Текст для уведомления',
                ),
            'LIST_COLUMN_LABEL' =>
                array(
                    'en' => 'Notify text',
                    'ru' => 'Текст для уведомления',
                ),
            'LIST_FILTER_LABEL' =>
                array(
                    'en' => 'Notify text',
                    'ru' => 'Текст для уведомления',
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
                array(
                    'PRECISION' => 4,
                    'SIZE' => 20,
                    'MIN_VALUE' => 0.0,
                    'MAX_VALUE' => 0.0,
                    'DEFAULT_VALUE' => '',
                ),
            'EDIT_FORM_LABEL' =>
                array(
                    'en' => 'User',
                    'ru' => 'Пользователь',
                ),
            'LIST_COLUMN_LABEL' =>
                array(
                    'en' => 'User',
                    'ru' => 'Пользователь',
                ),
            'LIST_FILTER_LABEL' =>
                array(
                    'en' => 'User',
                    'ru' => 'Пользователь',
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
            'FIELD_NAME' => 'UF_NOTIFY_TYPE',
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
                    'en' => 'Notify Type',
                    'ru' => 'Тип уведомления',
                ),
            'LIST_COLUMN_LABEL' =>
                array(
                    'en' => 'Notify Type',
                    'ru' => 'Тип уведомления',
                ),
            'LIST_FILTER_LABEL' =>
                array(
                    'en' => 'Notify Type',
                    'ru' => 'Тип уведомления',
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
                            'VALUE' => 'Уведомление',
                            'DEF' => 'N',
                            'SORT' => '500',
                            'XML_ID' => 'default',
                        ),
                    1 =>
                        array(
                            'VALUE' => 'Сообщение',
                            'DEF' => 'N',
                            'SORT' => '500',
                            'XML_ID' => 'info',
                        ),
                    2 =>
                        array(
                            'VALUE' => 'Внимание',
                            'DEF' => 'N',
                            'SORT' => '500',
                            'XML_ID' => 'warning',
                        ),
                    3 =>
                        array(
                            'VALUE' => 'Тревога',
                            'DEF' => 'N',
                            'SORT' => '500',
                            'XML_ID' => 'alarm',
                        ),
                    4 =>
                        array(
                            'VALUE' => 'Ошибка',
                            'DEF' => 'N',
                            'SORT' => '500',
                            'XML_ID' => 'error',
                        ),
                ),
        ));
        $helper->Hlblock()->saveField($hlblockId, array(
            'FIELD_NAME' => 'UF_USER_EMAIL',
            'USER_TYPE_ID' => 'string',
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
                    'ROWS' => 1,
                    'REGEXP' => '',
                    'MIN_LENGTH' => 0,
                    'MAX_LENGTH' => 0,
                    'DEFAULT_VALUE' => '',
                ),
            'EDIT_FORM_LABEL' =>
                array(
                    'en' => 'User email',
                    'ru' => 'Email пользователя',
                ),
            'LIST_COLUMN_LABEL' =>
                array(
                    'en' => 'User email',
                    'ru' => 'Email пользователя',
                ),
            'LIST_FILTER_LABEL' =>
                array(
                    'en' => 'User email',
                    'ru' => 'Email пользователя',
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
            'FIELD_NAME' => 'UF_USER_LOGIN',
            'USER_TYPE_ID' => 'string',
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
                    'ROWS' => 1,
                    'REGEXP' => '',
                    'MIN_LENGTH' => 0,
                    'MAX_LENGTH' => 0,
                    'DEFAULT_VALUE' => '',
                ),
            'EDIT_FORM_LABEL' =>
                array(
                    'en' => 'User login',
                    'ru' => 'Логин пользователя',
                ),
            'LIST_COLUMN_LABEL' =>
                array(
                    'en' => 'User login',
                    'ru' => 'Логин пользователя',
                ),
            'LIST_FILTER_LABEL' =>
                array(
                    'en' => 'User login',
                    'ru' => 'Логин пользователя',
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
            $helper->Hlblock()->deleteHlblockIfExists('Notify');
        } catch (\Exception $ex) {
            $DB->Rollback();
            $this->outError('Ошибка отката миграции:' . $ex->getMessage());
        }
        $DB->Commit();
    }
}
