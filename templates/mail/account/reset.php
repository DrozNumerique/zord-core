		<a href="<?php echo $models['url']; ?>"><?php echo Zord::getLocale('account', $lang)->mail->reset_password->click_here.$models['login']; ?></a>
<?php $this->render('#noreply'); ?>	