<?php

$dtVal = "";
if ($x===1){
  if ($REC_NO_TMP === 0){
    $dtVal= $REC_NO;
    $REC_NO_TMP = $REC_NO;
  } else {
    if ($REC_NO_TMP !== $REC_NO){
      $dtVal= $REC_NO;
      $REC_NO_TMP = $REC_NO;
    }
  }
}
if ($x===2){ if (!empty($row1Data['CMCH_CMBH_STATUS'])){ $dtVal = $row1Data['CMCH_CMBH_STATUS']; }} //2
if ($x===3){ if (!empty($row1Data['CMCH_CHECK_STATUS'])){ $dtVal = $row1Data['CMCH_CHECK_STATUS']; }} //3
if ($x===4){ if (!empty($row1Data['CMCH_ORION_ITEM_CODE'])){ $dtVal = $row1Data['CMCH_ORION_ITEM_CODE']; }} //4
if ($x===5){ if (!empty($row1Data['CMCH_MB_SPG_ORION'])){ $dtVal = $row1Data['CMCH_MB_SPG_ORION']; }} //5
if ($x===6){ if (!empty($row1Data['CMCH_MB_COSTING'])){ $dtVal = $row1Data['CMCH_MB_COSTING']; }} //6
if ($x===7){ if (!empty($row1Data['CMBH_VS_NUMBER'])){ $dtVal = $row1Data['CMBH_VS_NUMBER']; }} //7
if ($x===8){ if (!empty($row1Data['CMCH_RM_VAL'])){ $dtVal = $row1Data['CMCH_RM_VAL']; }} //8
if ($x===9){ if (!empty($row1Data['CMCH_RM_MKT'])){ $dtVal = $row1Data['CMCH_RM_MKT']; }} //9
if ($x===10){ if (!empty($row1Data['CMCH_RM_SML'])){ $dtVal = $row1Data['CMCH_RM_SML']; }} //10

if ($x===11){ if (!empty($row1Data['CMCH_NO_OF_PROCESS_CODE'])){ $dtVal = $row1Data['CMCH_NO_OF_PROCESS_CODE']; }} //11
if ($x===12){ if (!empty($row1Data['CMCH_NO_OF_PROCESS_VAL'])){ $dtVal = $row1Data['CMCH_NO_OF_PROCESS_VAL']; }} //12
if ($x===13){ if (!empty($row1Data['CMCH_THROUGH_PUT_CODE'])){ $dtVal = $row1Data['CMCH_THROUGH_PUT_CODE']; }} //13
if ($x===14){ if (!empty($row1Data['CMCH_THROUGH_PUT_VAL'])){ $dtVal = $row1Data['CMCH_THROUGH_PUT_VAL']; }} //14
if ($x===15){ if (!empty($row1Data['CMCH_CMM_MACHINE_CODE'])){ $dtVal = $row1Data['CMCH_CMM_MACHINE_CODE']; }} //15
if ($x===16){ if (!empty($row1Data['CMCH_CMM_TOT_FXD_CST'])){ $dtVal = $row1Data['CMCH_CMM_TOT_FXD_CST']; }} //16
if ($x===17){ if (!empty($row1Data['CMCH_WASTE'])){ $dtVal = $row1Data['CMCH_WASTE']; }} //17
if ($x===18){ if (!empty($row1Data['CMCH_QUALITY_LOSS'])){ $dtVal = $row1Data['CMCH_QUALITY_LOSS']; }} //18
if ($x===19){ if (!empty($row1Data['CMCH_EFFICIENCY'])){ $dtVal = $row1Data['CMCH_EFFICIENCY']; }} //19
if ($x===20){ if (!empty($row1Data['CMCH_DEV_EXPENSE'])){ $dtVal = $row1Data['CMCH_DEV_EXPENSE']; }} //20

if ($x===21){ if (!empty($row1Data['CMCH_PACKING'])){ $dtVal = $row1Data['CMCH_PACKING']; }} //21
if ($x===22){ if (!empty($row1Data['CMCH_NET_PROD_PER_DAY'])){ $dtVal = $row1Data['CMCH_NET_PROD_PER_DAY']; }} //22
if ($x===23){ if (!empty($row1Data['CMCH_WASTE_VALUATION'])){ $dtVal = $row1Data['CMCH_WASTE_VALUATION']; }} //23
if ($x===24){ if (!empty($row1Data['CMCH_FIXED_COST_FINAL'])){ $dtVal = $row1Data['CMCH_FIXED_COST_FINAL']; }} //24
if ($x===25){ if (!empty($row1Data['CMCH_COST_OTHERS'])){ $dtVal = $row1Data['CMCH_COST_OTHERS']; }} //25
if ($x===26){ if (!empty($row1Data['CMCH_CONV_COST_V'])){ $dtVal = $row1Data['CMCH_CONV_COST_V']; }} //26
if ($x===27){ if (!empty($row1Data['CMCH_COST_VALUATION'])){ $dtVal = $row1Data['CMCH_COST_VALUATION']; }} //27
if ($x===28){ if (!empty($row1Data['CMCH_COST_MARKETING'])){ $dtVal = $row1Data['CMCH_COST_MARKETING']; }} //28
if ($x===29){ if (!empty($row1Data['CMCH_COST_SIMULATION'])){ $dtVal = $row1Data['CMCH_COST_SIMULATION']; }} //29
if ($x===30){ if (!empty($row1Data['CMCI_CMBI_SEC_NO'])){ $dtVal = $row1Data['CMCI_CMBI_SEC_NO']; }} //30

if ($x===31){ if (!empty($row1Data['CMCI_CMBI_CODE'])){ $dtVal = $row1Data['CMCI_CMBI_CODE']; }} //31
if ($x===32){ if (!empty($row1Data['CMCI_CMBI_COLOURANT'])){ $dtVal = $row1Data['CMCI_CMBI_COLOURANT']; }} //32
if ($x===33){ if (!empty($row1Data['CMCI_CMBI_COMPOSITION'])){ $dtVal = $row1Data['CMCI_CMBI_COMPOSITION']; }} //33
if ($x===34){ if (!empty($row1Data['CMCI_RM_VAL_1'])){ $dtVal = $row1Data['CMCI_RM_VAL_1']; }} //34
if ($x===35){ if (!empty($row1Data['CMCI_RM_MKT_1'])){ $dtVal = $row1Data['CMCI_RM_MKT_1']; }} //35
if ($x===36){ if (!empty($row1Data['CMCI_RM_SML_1'])){ $dtVal = $row1Data['CMCI_RM_SML_1']; }} //36
if ($x===37){ if (!empty($row1Data['CMCI_RM_VAL_2'])){ $dtVal = $row1Data['CMCI_RM_VAL_2']; }} //37
if ($x===38){ if (!empty($row1Data['CMCI_RM_MKT_2'])){ $dtVal = $row1Data['CMCI_RM_MKT_2']; }} //38
if ($x===39){ if (!empty($row1Data['CMCI_RM_SML_2'])){ $dtVal = $row1Data['CMCI_RM_SML_2']; }} //39
if ($x===40){ if (!empty($row1Data['CMCI_CMBH_SYS_ID'])){ $dtVal = $row1Data['CMCI_CMBH_SYS_ID']; }} //40

if ($x===41){ if (!empty($row1Data['CMBH_D_F'])){ $dtVal = $row1Data['CMBH_D_F']; }} //41
if ($x===42){ if (!empty($row1Data['CMBH_RUN_LDR_PRSN'])){ $dtVal = $row1Data['CMBH_RUN_LDR_PRSN']; }} //42
if ($x===43){ if (!empty($row1Data['CMBH_FINAL_PRODUCT'])){ $dtVal = $row1Data['CMBH_FINAL_PRODUCT']; }} //43
if ($x===44){ if (!empty($row1Data['CMBH_LDR_PRSN'])){ $dtVal = $row1Data['CMBH_LDR_PRSN']; }} //44

?>
