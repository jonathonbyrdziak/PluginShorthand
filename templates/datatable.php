<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
$unique = \Shorthand\create_guid();
?>
<script>
jQuery(document).ready(function(){
	var table = jQuery('#m<?php echo $unique ?>').dataTable({
		data: <?php echo json_encode($data) ?>,
		columns: [
			<?php foreach ((array)$headers as $c => $p): ?>
			{ data : '<?php echo $c ?>' },
			<?php endforeach ?>
		],
		tableTools: {
			sSwfPath: "<?php echo \Shorthand\dir_to_url(dirname(__dir__)) ?>/js/dataTable/TableTools/swf/copy_csv_xls_pdf.swf", 
		},
		dom: 'T<"clear">lfrtip'
	});
	//new jQuery.fn.dataTable.FixedHeader( table );
	jQuery('.DTTT_button').addClass('button');
});
</script>
<div class="datatable_wrapper">
	<table id="m<?php echo $unique ?>" class="display">
	    <thead>
		<tr>
		    <?php foreach($headers as $p): ?>
		    <th><?php echo $p ?></th>
		    <?php endforeach ?>
		</tr>
	    </thead>
	    <tbody>
		<tr>
		<?php foreach ((array)$headers as $c): ?>
		    <td></td>
		<?php endforeach ?>
		</tr>
	    </tbody>
	</table>
	<br class="clear"/>
</div>