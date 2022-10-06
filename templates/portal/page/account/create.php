<?php if (ACCOUNT_EMAIL_AS_LOGIN) { ?>
<?php $this->render('/portal/page/account/fields/email', Zord::array_merge($models, ['switch' => $switch])); ?>
<?php } else { ?>
<?php $this->render('/portal/page/account/fields/login', Zord::array_merge($models, ['switch' => $switch])); ?>
<?php } ?>
<?php $this->render('/portal/page/account/fields/name', Zord::array_merge($models, ['switch' => $switch])); ?>
<?php if (!ACCOUNT_EMAIL_AS_LOGIN) { ?>
<?php $this->render('/portal/page/account/fields/email', Zord::array_merge($models, ['switch' => $switch])); ?>
<?php } ?>
	