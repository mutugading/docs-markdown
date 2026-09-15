<?php
$byType = "Y"; $byShade = ""; $byCust = ""; $byShadeMulti = "";
?>
<tr>
    <td>Left No<br>Product</td><!-- 1 -->
  <?php if ($byShadeMulti === "Y") { ?>
  	<td >
  		Shade Name
  	</td>
  <?php } ?>
  		<td >
  			1)<br>No
  		</td>
  <?php if ($byType === "Y") { ?>
  		<td >
  			2)<br>Customer
  		</td>
  		<td  >
  			Product Name
  		</td>
  		<td >
  			 3)<br>Shade Name
  		</td>
  <?php } else if ($byShade === "Y") { ?>
  		<td >
  			2)<br>Type
  		</td>
  		<td >
  			2)<br>Customer
  		</td>
  		<td  >
  			Product Name
  		</td>
  <?php } else if ($byCust === "Y") { ?>
  		<td >
  			2)<br>Type
  		</td>
  		<td  >
  			Product Name
  		</td>
  		<td >
  			3)<br>Shade Name
  		</td>
  <?php } ?>


  <td>
    4)<br>Name
  </td><!-- 4 -->
  <td>
    5)<br>M/C 
  </td><!-- 5 -->

  <td>
    6)<br><?php echo "V4=>6<12 Dom Cost"; ?>
  </td><!-- 6 -->
  <td>
    7)<br><font color="blue">R.M</font> / Chip Rate
  </td><!-- 7 -->
  <td>
<?php
  if ($CYL_TYPE_S==="SUPERBA"){
    echo "8)<br>SP cost";
  } else {
    echo "8)<br>MB cost";
  }
?>
</td><!-- 8 -->
  <td>
    9)<br>
<?php
  if ($CYL_TYPE_S==="SUPERBA"){
    echo "Conv-SP Cost</br>";
  } else {
    echo "Conv-Mb Cost</br>";
  }
?>
    "6-7-8"
  </td><!-- 9 -->
  <td>
    10)<br>Eff
  </td><!-- 10 -->
  <td>
    11)<br>Speed
  </td><!-- 11 -->
  <td>
    12)<br>"V1 < 1.5"
  </td><!-- 12 -->
  <td>
    13)<br>"V2 => 1.5 < 3"
  </td><!-- 13 -->
  <td>
    14)<br>"V3 => 3 < 6"
  </td><!-- 14 -->

  <td>
    15)<br>"V5 < 12"
  </td><!-- 15 -->
  <td>
    16)<br>Denier
  </td><!-- 16 -->
  <td>
    17)<br>Filament
  </td><!-- 17 -->
  <td>
    18)<br>Intermingling
  </td><!-- 18 -->
  <td>
    19)<br>Heatset
  </td><!-- 19 -->
  <td>
    20)<br>Cross Section
  </td><!-- 20 -->
  <td>
    21)<br>Lusture
  </td><!-- 21 -->
  <td>
    <?php
      if ($CYL_TYPE_S==="SUPERBA"){
        echo "22)<br>SP Name";
      } else {
        echo "22)<br>MB Name";
      }
    ?>
  </td><!-- 22 -->
  <td>
    23)<br>Chip
  </td><!-- 23 -->
  <td>
    24)<br>Packing type
  </td><!-- 24 -->
  <td>
    25)<br>Bobbin weight
  </td><!-- 25 -->
  <td>
    26)<br>no of Bobbins
  </td><!-- 26 -->
  <td>
    27)<br>Dozing
  </td><!-- 27 -->
  <td>
    28)<br>MB Rate
  </td><!-- 28 -->
  <td>
    29)<br>Change Over Loss
  </td><!-- 29 -->
  <td>
    30)<br>Quality Loss
  </td><!-- 30 -->
  <td>
    31)<br>Intermigle Cost
  </td><!-- 31 -->
  <td>
    32)<br>Fixed Cost
  </td><!-- 32 -->
  <td>
    33)<br>Del-Pack cost
  </td><!-- 33 -->
  <td>
    34)<br>Final Ex Factory Cost
  </td><!-- 34 -->
  <td>
    35)<br>Fowarding
  </td><!-- 35 -->
  <td>
    36)<br>DTY Prod
  </td><!-- 36 -->
</tr>
