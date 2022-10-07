<?php $this->render('/portal/page/account/fields/name', Zord::array_merge($models, ['switch' => $switch])); ?>
<?php if (!ACCOUNT_EMAIL_AS_LOGIN) { ?>
<?php $this->render('/portal/page/account/fields/email', Zord::array_merge($models, ['switch' => $switch])); ?>
<?php } ?>
<?php $this->render('/portal/page/account/fields/password', Zord::array_merge($models, ['switch' => $switch])); ?>
<?php $this->render('/portal/page/account/fields/confirm', Zord::array_merge($models, ['switch' => $switch])); ?>
	