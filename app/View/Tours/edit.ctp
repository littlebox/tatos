<div class="portlet light bordered form-fit">
	<div class="portlet-title">
		<div class="caption">
			<i class="icon-plus font-blue-hoki"></i>
			<span class="caption-subject font-blue-hoki bold uppercase"><?= __('Add')?></span>
			<span class="caption-helper"><?= __('Tours')?></span>
		</div>
	</div>
	<div class="portlet-body form">
		<!-- BEGIN FORM-->
		<?php echo $this->Form->create('Tour', array(
			'enctype' => 'multipart/form-data',
			'inputDefaults' => array(
				'format' => array('before','label','between','input','error','after'),
				'autocomplete' => 'off',
				'div' => array(
					'class' => 'form-group',
				),
				'label' => array(
					'class' => 'control-label col-md-3'
				),
				'class' => 'form-control',
				'between' => '<div class="col-md-9">',
				'after' => '</div>',
				'error' => array('attributes' => array(
					'class' => 'help-block',
					'wrap' => 'span',
					))
			),
			'class' => 'form-horizontal form-bordered form-row-stripped',
			'id' => 'tour-add-edit-form',
		)); ?>
			<div class="form-body">

				<?php
					echo $this->Form->input('name', array('placeholder' => 'Ej: Descubriendo Varietales'));
					echo $this->Form->input('length', array('type' => 'text', 'class' => 'timepicker timepicker-length form-control', 'placeholder' => '--:--'));
					echo $this->Form->input('quota', array('placeholder' => '25'));
					echo $this->Form->input('price', array('placeholder' => '150'));
					echo $this->Form->input('Language', array('multiple' => 'checkbox', 'class' => ''));
					echo $this->Form->input('Day', array('multiple' => 'checkbox', 'class' => ''));
				?>
					<div class="form-group">
						<label for="TimeTime" class="control-label col-md-3">Time</label>
						<div class="col-md-9">
							<div id="all-timepickers-div">
								<div class="timepicker-div" style="display:none;">
									<div class="col-md-10" style="padding:0px;">
										<input name="data[Time][Time][]" autocomplete="off" class="timepicker timepicker-time form-control" placeholder="--:--" style="margin-bottom:10px;" type="text" id="TimeTime">
									</div>
									<div class="col-md-2">
										<button type="button" class="btn red remove-time-button"><?= __('Remove') ?> <i class="fa fa-trash-o"></i></button>
									</div>
								</div>
								<?php foreach($this->request->data['Time'] as $time):?>
									<div class="timepicker-div">
										<div class="col-md-10" style="padding:0px;">
											<input name="data[Time][Time][]" autocomplete="off" class="timepicker timepicker-time form-control" placeholder="--:--" style="margin-bottom:10px;" type="text" id="TimeTime" value="<?= $time['hour'];?>">
										</div>
										<div class="col-md-2">
											<button type="button" class="btn red remove-time-button"><?= __('Remove') ?> <i class="fa fa-trash-o"></i></button>
										</div>
									</div>
								<?php endforeach;?>
							</div>
							<div class="col-md-12" style="padding:0px;">
								<button type="button" class="btn green" id="add-time-button"><?= __('Add Time') ?> <i class="fa fa-plus"></i></button>
							</div>
						</div>
					</div>
				<?php
					echo $this->Form->input('description', array('placeholder' => 'Ej: Viví una experiencia inolvidable en tu visita por la bodega. Los visitantes degustarán vino desde un tanque de fermentación y desde una barrica de roble llegando hasta nuestra cava tradicional, donde serán protagonistas al elegir y descorchar su propia botella de vino en estiba.'));
				?>

			</div>

			<div class="form-actions">
				<div class="row">
					<div class="col-md-offset-10 col-md-2">
						<?php
							echo $this->Form->Button(__('Cancel'),array(
								'div' => false,
								'class' => 'btn default',
								'type' => 'button'
							));
							echo $this->Form->Button(__('Save'),array(
								'div' => false,
								'class' => 'btn green',
								'type' => 'submit'
							));

						?>
					</div>
				</div>
			</div>
		<?= $this->Form->end(); ?>
		<!-- END FORM-->
	</div>
</div>

<?php $this->append('pageStyles'); ?>
	<?= $this->Html->css('/plugins/bootstrap-fileinput/bootstrap-fileinput');?>
	<?= $this->Html->css('/plugins/bootstrap-switch/css/bootstrap-switch.min');?>
	<?= $this->Html->css('/plugins/jquery-tags-input/jquery.tagsinput');?>
	<?= $this->Html->css('/plugins/jcrop/css/jquery.Jcrop.min');?>
	<?= $this->Html->css('/plugins/bootstrap-timepicker/css/bootstrap-timepicker.min');?>
<?php $this->end(); ?>

<?php $this->append('pagePlugins'); ?>
	<?= $this->Html->script('/plugins/jquery-validation/js/jquery.validate.min');?>
	<?= $this->Html->script('/plugins/jquery-validation/js/additional-methods.min');?>
	<?= $this->Html->script('/plugins/bootstrap-fileinput/bootstrap-fileinput');?>
	<?= $this->Html->script('/plugins/jcrop/js/jquery.color.js');?>
	<?= $this->Html->script('/plugins/jcrop/js/jquery.Jcrop.min.js');?>
	<?= $this->Html->script('/plugins/bootstrap-timepicker/js/bootstrap-timepicker.min');?>
<?php $this->end(); ?>

<?php $this->append('pageScripts'); ?>
	<?= $this->Html->script('global-setups');?>
	<?= $this->Html->script('tours-add-edit.js');?>
	<script>
		jQuery(document).ready(function() {
			TourAddEdit.init();
		});
	</script>
<?php $this->end(); ?>