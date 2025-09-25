<?php

namespace MyCompany\WebService\VS\Gisgmp;

class ParserResponseGisGmp{
    static function incomeInfo($item){
        $quittance = simplexml_import_dom($item);
        $attr = (array)$quittance->attributes();
        $attr = $attr['@attributes'];
        $props = [];
        foreach($attr as $key => $attrItem){
            $props['IncomeInfo_'.$key] = $attrItem;
        }

        return $props;
    }

    static function budgetIndex($item){
        $quittance = simplexml_import_dom($item);
        $attr = (array)$quittance->attributes();
        $attr = $attr['@attributes'];
        $props = [];
        foreach($attr as $key => $attrItem){
            $props['BudgetIndex_'.$key] = $attrItem;
        }

        return $props;
    }

    static function orgAccount($item){
        $quittance = simplexml_import_dom($item);
        $attr = (array)$quittance->attributes();
        $attr = $attr['@attributes'];
        $props = [];
        foreach($attr as $key => $attrItem){
            $props['OrgAccount_'.$key] = $attrItem;
        }

        return $props;
    }


    static function payee($item){
        $quittance = simplexml_import_dom($item);
        $attr = (array)$quittance->attributes();
        $attr = $attr['@attributes'];
        $props = [];
        foreach($attr as $key => $attrItem){
            $props['Payee_'.$key] = $attrItem;
        }

        return $props;
    }

    static function payer($item){
        $payer = simplexml_import_dom($item);
        $attr = (array)$payer->attributes();
        $attr = $attr['@attributes'];
        $props = [];
        foreach($attr as $key => $attrItem){
            $props['Payer_'.$key] = $attrItem;
        }

        return $props;
    }

    static function bank($item){
        $bank = simplexml_import_dom($item);
        $attr = (array)$bank->attributes();
        $attr = $attr['@attributes'];
        $props = [];
        foreach($attr as $key => $attrItem){
            $props['Bank_'.$key] = $attrItem;
        }

        return $props;
    }

    static function quittance($item){
        $quittance = simplexml_import_dom($item);
        $attr = (array)$quittance->attributes();
        $attr = $attr['@attributes'];
        $props = [];
        foreach($attr as $key => $attrItem){
            $props['Quittance_'.$key] = $attrItem;
        }

        return $props;
    }

    static function quittanceByPropertyCode($item, $propertyCode)
    {
        $quittance = simplexml_import_dom($item);
        $attr = (array)$quittance->attributes();
        $attr = $attr['@attributes'];
        $props = [];
        $propPart = explode('_', $propertyCode)[1];
        $props = $attr[$propPart];

        return $props;
    }

    static function paymentInfoByPropertyCode($item, $propertyCode)
    {
        $payment = simplexml_import_dom($item);
        $attr = (array)$payment->attributes();
        $attr = $attr['@attributes'];
        $props = [];
        $propPart = explode('_', $propertyCode)[1];
        $props = $attr[$propPart];

        return $props;
    }

    static function paymentInfo($item, $namespaces){
        $payment = simplexml_import_dom($item);
        $attr = (array)$payment->attributes();
        $attr = $attr['@attributes'];
        $props = [];
        foreach($attr as $key => $attrItem){
            $props['PaymentInfo_'.$key] = $attrItem;
        }

        if (!empty($item->pmntPaymentOrg->orgBank))
            $props = array_merge(
                $props,
                self::bank(
                    $item->pmntPaymentOrg->orgBank
                )
            );

        $props = array_merge(
            $props, 
            self::payer(
                $item->pmntPayer
            )
        );

        $props = array_merge(
            $props, 
            self::payee(
                $item->orgPayee
            )
        );

        $props = array_merge(
            $props, 
            self::orgAccount(
                $item->orgPayee->comOrgAccount
            )
        );

        $props = array_merge(
            $props, 
            self::bank(
                $item->orgPayee->comOrgAccount->comBank
            )
        );

        $props = array_merge(
            $props, 
            self::budgetIndex(
                $item->pmntBudgetIndex
            )
        );

        $props = array_merge(
            $props, 
            self::incomeInfo(
                $item->IncomeInfo
            )
        );

        $props['ChangeStatusInfo_Meaning'] = (string)$item->comChangeStatusInfo->comMeaning;

        return $props;
    }

    static function importProtocol($item){
        $quittance = simplexml_import_dom($item);
        $attr = (array)$quittance->attributes();
        $attr = $attr['@attributes'];
        $props = [];
        foreach($attr as $key => $attrItem){
            $props['ImportProtocol_'.$key] = $attrItem;
        }

        return $props;
    }    
}
