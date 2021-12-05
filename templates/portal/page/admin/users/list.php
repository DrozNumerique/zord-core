				<div align="center">
<?php if (isset($models['error'])) { ?>
           			<div class="admin-panel-warning"><?php echo $models['error']; ?></div>
           			<div class="admin-panel-warning"><?php echo $locale->tab->users->mail->to.' : '.$models['account']; ?></div>
           			<div class="admin-panel-warning"><a href="<?php echo $models['activation']; ?>"><?php echo $locale->tab->users->mail->activation; ?></a></div>
           			<br/>
           			<br/>
<?php } ?>
<?php $this->render('/portal/widget/admin/lookup'); ?>
<?php $this->render('/portal/widget/admin/pagination'); ?>
<?php $this->render('items'); ?>
   					</ul>
				</div>
