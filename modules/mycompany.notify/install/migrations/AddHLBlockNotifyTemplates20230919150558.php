<?php

namespace Sprint\Migration;


class AddHLBlockNotifyTemplates20230919150558 extends Version
{
    protected $description = "Миграция добавляет HL-блок \"Шаблоны уведомлений\"";

    protected $moduleVersion = "4.1.2";

    /**
     * @return bool|void
     * @throws Exceptions\HelperException
     */
    public function up()
    {
        $helper = $this->getHelperManager();
        $hlblockId = $helper->Hlblock()->saveHlblock(array(
            'NAME' => 'NotifyTemplates',
            'TABLE_NAME' => 'notify_templates',
            'LANG' =>
                array(
                    'ru' =>
                        array(
                            'NAME' => 'Шаблоны уведомлений',
                        ),
                    'en' =>
                        array(
                            'NAME' => 'Notify Templates',
                        ),
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
                    'SIZE' => 200,
                    'ROWS' => 20,
                    'REGEXP' => '',
                    'MIN_LENGTH' => 0,
                    'MAX_LENGTH' => 0,
                    'DEFAULT_VALUE' => '',
                    'PATTERN' => 'ВНИМАНИЕ!
Информирование о необходимости  заполнить данные по объектам #PROJECT_NAME#


1. Результаты
Результат: #RESULT_NAME#, год #RESULT_YEAR#
Заполните данные за: #RESULT_MONTH#

#CONTROL_POINT_ITEM_LIST#



 План финансирования
#FINANCE_PLAN_ITEM_LIST#

2. Показатели

#INDICATORS_LIST#

',
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
            'FIELD_NAME' => 'UF_TEMPLATE_TYPE',
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
                    'en' => 'Template type',
                    'ru' => 'Тип шаблона',
                ),
            'LIST_COLUMN_LABEL' =>
                array(
                    'en' => 'Template type',
                    'ru' => 'Тип шаблона',
                ),
            'LIST_FILTER_LABEL' =>
                array(
                    'en' => 'Template type',
                    'ru' => 'Тип шаблона',
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
                            'VALUE' => 'Шаблон для контрольных точек',
                            'DEF' => 'N',
                            'SORT' => '1',
                            'XML_ID' => '75ade3f6466b4589fe87dc01f50778f9',
                        ),
                    1 =>
                        array(
                            'VALUE' => 'Шаблон для результатов ФП',
                            'DEF' => 'N',
                            'SORT' => '2',
                            'XML_ID' => '1c64235f163c488ab5a05617cd87f38c',
                        ),
                    2 =>
                        array(
                            'VALUE' => 'Регистрация пользователя',
                            'DEF' => 'N',
                            'SORT' => '3',
                            'XML_ID' => 'b5aece951b5fb02449700692e303edc7',
                        ),
                    3 =>
                        array(
                            'VALUE' => 'Отмена регистрации пользователя',
                            'DEF' => 'N',
                            'SORT' => '4',
                            'XML_ID' => '0975179ae48ffa1946fc0166ecc839e0',
                        ),
                    4 =>
                        array(
                            'VALUE' => 'Шаблон плана финансирования',
                            'DEF' => 'N',
                            'SORT' => '5',
                            'XML_ID' => 'd9ba84e07203186c217ffa3e5981e8b2',
                        ),
                    5 =>
                        array(
                            'VALUE' => 'Шаблон для последней даты месяца',
                            'DEF' => 'N',
                            'SORT' => '6',
                            'XML_ID' => 'd9d754804a8242f335201a3d3e50bd72',
                        ),
                ),
        ));
    }

    public function down()
    {
        $helper = $this->getHelperManager();
        global $DB;
        $DB->StartTransaction();
        try {
            $helper->Hlblock()->deleteHlblockIfExists('NotifyTemplates');
        } catch (\Exception $ex) {
            $DB->Rollback();
            $this->outError('Ошибка отката миграции:' . $ex->getMessage());
        }
        $DB->Commit();
    }
}
